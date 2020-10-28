<?php

namespace App\Custom\Queue;

use Aws\Sqs\SqsClient;
use InvalidArgumentException;
use Illuminate\Queue\SqsQueue;
use App\Custom\Queue\Support\Str;
use App\Custom\Queue\Support\Arr;
use App\Custom\Queue\Contracts\Queue\Duplicator;
use Illuminate\Contracts\Container\BindingResolutionException;


class SqsFifoQueue extends SqsQueue
{
    /**
     * The queue name suffix.
     *
     * @var string
     */
    protected $suffix;

    /**
     * The message group id of the fifo pipe in the queue.
     *
     * @var string
     */
    protected $group;

    /**
     * The driver to generate the deduplication id for the message.
     *
     * @var string
     */
    protected $duplicator;

    /**
     * The flag to check if this queue is setup for delay.
     *
     * @var bool
     */
    protected $allowDelay;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param SqsClient $sqs
     * @param string $default
     * @param string $prefix
     * @param string $suffix
     * @param string $group
     * @param string $duplicator
     * @param bool $allowDelay
     *
     */
    public function __construct(SqsClient $sqs, string $default, $prefix = '', $suffix = '', $group = '', $duplicator = '', $allowDelay = false)
    {
        parent::__construct($sqs, $default, $prefix);

        $this->suffix = $suffix;
        $this->group = $group;
        $this->duplicator = $duplicator;
        $this->allowDelay = $allowDelay;
    }

    /**
     * Set the underlying SQS instance.
     *
     * @param SqsClient $sqs
     *
     * @return SqsFifoQueue
     */
    public function setSqs(SqsClient $sqs)
    {
        $this->sqs = $sqs;

        return $this;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $message = [
            'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload, 'MessageGroupId' => $this->getMeta($payload, 'group', $this->group),
        ];

        if (($deduplication = $this->getDeduplicationId($payload, $queue)) !== false) {
            $message['MessageDeduplicationId'] = $deduplication;
        }

        $response = $this->sqs->sendMessage($message);

        return $response->get('MessageId');
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * SQS FIFO queues do not allow per-message delays, but the queue itself
     * can be configured to delay the message. If this queue is setup for
     * delayed messages, push the job to the queue instead of throwing.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($this->allowDelay) {
            return $this->push($job, $data, $queue);
        }

        throw new BadMethodCallException('FIFO queues do not support per-message delays.');
    }

    /**
     * Get the queue or return the default.
     *
     * Laravel 7.x added support for a suffix, mainly to support Laravel Vapor.
     * Since SQS FIFO queues must end in ".fifo", supporting a suffix config
     * on these queues must be customized to work with the existing suffix.
     *
     * Additionally, this will provide support for the suffix config for older
     * versions of Laravel, in case anyone wants to use it.
     *
     * @param  string|null  $queue
     *
     * @return string
     */
    public function getQueue($queue)
    {
        $queue = $queue ?: $this->default;

        // Prefix support was not added until Laravel 5.1. Don't support a
        // suffix on versions that don't even support a prefix.
        if (!property_exists($this, 'prefix')) {
            return $queue;
        }

        // Strip off the .fifo suffix to prepare for the config suffix.
        $queue = Str::beforeLast($queue, '.fifo');

        // Modify the queue name as needed and re-add the ".fifo" suffix.
        return (filter_var($queue, FILTER_VALIDATE_URL) === false
                ? rtrim($this->prefix, '/').'/'.Str::finish($queue, $this->suffix)
                : $queue).'.fifo';
    }

    /**
     * Get the deduplication id for the given driver.
     *
     * @param string $payload
     * @param string $queue
     * @return string|bool
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    protected function getDeduplicationId(string $payload, string $queue)
    {
        $driver = $this->getMeta($payload, 'duplicator', $this->duplicator);

        if (empty($driver)) {
            return false;
        }

        if ($this->container->bound($key = 'queue.sqs-fifo.duplicator.'.$driver)) {
            $duplicator = $this->container->make($key);

            if ($duplicator instanceof Duplicator) {
                return $duplicator->generate($payload, $queue);
            }

            throw new InvalidArgumentException(sprintf('Deduplication method [%s] must resolve to a %s implementation.', $driver, Deduplicator::class));
        }

        throw new InvalidArgumentException(sprintf('Unsupported deduplication method [%s].', $driver));
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  mixed  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     *
     * @return string
     *
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Queue\InvalidPayloadException
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = parent::createPayload($job, $data, $queue);

        if (!is_object($job)) {
            return $payload;
        }

        // Laravel 5.4 reworked payload generate. If the parent class has
        // the `createPayloadArray` method, it has already been called
        // through the parent call to the "createPayload" method.
        if (method_exists(get_parent_class($this), 'createPayloadArray')) {
            return $payload;
        }

        // Laravel < 5.0 doesn't support pushing job instances onto the queue.
        // We must regenerate the payload using just the class name, instead
        // of the job instance, so the queue worker can handle the job.
        if (!class_exists(CallQueuedHandler::class)) {
            $payload = parent::createPayload(get_class($job), $data, $queue);
        }

        // Laravel <= 5.3 has the `setMeta` method. This is the method
        // used to add meta data to the payload generated by the
        // parent call to `createPayload` above.
        if (method_exists($this, 'setMeta')) {
            return $this->appendPayload($payload, $job);
        }

        // If neither of the above methods exist, we must be on a version
        // of Laravel that is not currently supported.
        throw new LogicException('"createPayloadArray" and "setMeta" methods both missing. This version of Laravel not supported.');
    }

    /**
     * Append meta data to the payload for Laravel <= 5.3.
     *
     * @param string $payload
     * @param mixed $job
     *
     * @return string
     */
    protected function appendPayload(string $payload, $job)
    {
        $meta = $this->getMetaPayload($job);

        if (array_key_exists('group', $meta)) {
            $payload = $this->setMeta($payload, 'group', $meta['group']);
        }

        if (array_key_exists('duplicator', $meta)) {
            $payload = $this->setMeta($payload, 'duplicator', $meta['duplicator']);
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  mixed  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     *
     * @return array
     */
    protected function createPayloadArray($job, $data = '', $queue = null)
    {
        return array_merge(
            parent::createPayloadArray($job, $data, $queue),
            $this->getMetaPayload($job)
        );
    }

    /**
     * Get the meta data to add to the payload.
     *
     * @param  mixed  $job
     *
     * @return array
     */
    protected function getMetaPayload($job)
    {
        if (!is_object($job)) {
            return [];
        }

        if ($job instanceof SendQueuedNotifications) {
            // The notification property was not made public until 5.4.12. To
            // support 5.3.0 - 5.4.11, we use reflection.
            $queueable = $this->getRestrictedValue($job, 'notification');
        } elseif ($job instanceof SendQueuedMailable) {
            // The mailable property was not made public until 5.4.12. To
            // support 5.3.0 - 5.4.11, we use reflection.
            $queueable = $this->getRestrictedValue($job, 'mailable');
        } else {
            $queueable = $job;
        }

        return array_filter(
            [
                'group' => isset($queueable->messageGroupId) ? $queueable->messageGroupId : null,
                'duplicator' => isset($queueable->duplicator) ? $queueable->duplicator : null,
            ],
            function ($value) {
                return $value !== null;
            }
        );
    }

    /**
     * Get additional meta from a payload string.
     *
     * @param string $payload
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getMeta(string $payload, string $key, $default = null)
    {
        $payload = json_decode($payload, true);

        return Arr::get($payload, $key, $default);
    }

    /**
     * Use reflection to get the value of a restricted (private/protected)
     * property on an object.
     *
     * @param  object  $object
     * @param  string  $property
     *
     * @return mixed
     */
    protected function getRestrictedValue($object, $property)
    {
        $reflectionProperty = new ReflectionProperty($object, $property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
