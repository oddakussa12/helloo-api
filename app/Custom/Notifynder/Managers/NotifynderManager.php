<?php

namespace App\Custom\Notifynder\Managers;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Str;
use App\Custom\Notifynder\Builder\Builder;
use Fenos\Notifynder\Contracts\SenderManagerContract;
use Fenos\Notifynder\Contracts\NotifynderManagerContract;

/**
 * Class NotifynderManager.
 */
class NotifynderManager implements NotifynderManagerContract
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var SenderManagerContract
     */
    protected $sender;

    /**
     * NotifynderManager constructor.
     * @param SenderManagerContract $sender
     */
    public function __construct(SenderManagerContract $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @param string|int|\Fenos\Notifynder\Models\NotificationCategory $category
     * @return $this
     */
    public function category($category)
    {
        $this->builder(true);
        $this->builder->category($category);

        return $this;
    }

    /**
     * @param array|\Traversable $data
     * @param Closure $callback
     * @return $this
     */
    public function loop($data, Closure $callback)
    {
        $this->builder(true);
        $this->builder->loop($data, $callback);

        return $this;
    }

    /**
     * @param bool $force
     * @return Builder
     */
    public function builder($force = false)
    {
        if (is_null($this->builder) || $force) {
            $this->builder = new Builder();
        }

        return $this->builder;
    }

    /**
     * @return bool
     */
    public function send()
    {
        $sent = $this->sender->send($this->builder->getNotifications());
        $this->reset();

        return $sent;
    }

    /**
     * @return SenderManagerContract
     */
    public function sender()
    {
        return $this->sender;
    }

    protected function reset()
    {
        $this->builder = null;
    }

    /**
     * @param string $name
     * @param Closure $sender
     * @return bool
     */
    public function extend($name, Closure $sender)
    {
        return (bool) $this->sender->extend($name, $sender);
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this|bool
     * @throws BadMethodCallException
     * @throws \Fenos\Notifynder\Exceptions\UnvalidNotificationException
     */
    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, 'send')) {
            if($name=='sendWithJpush')
            {
                $sent = $this->sender->sendWithCustomSender($name, [$this->builder]);
            }else{
                $sent = $this->sender->sendWithCustomSender($name, $this->builder->getNotifications());
            }
            $this->reset();

            return (bool) $sent;
        }

        if ($this->builder instanceof Builder && method_exists($this->builder, $name)) {
            $result = call_user_func_array([$this->builder, $name], $arguments);
            if (Str::startsWith($name, 'get')) {
                return $result;
            }

            return $this;
        }

        $error = "The method [$name] doesn't exist in the class ".self::class;
        throw new BadMethodCallException($error);
    }
}
