<?php

namespace App\Custom\Queue\Bus;

trait SqsFifoQueueable
{
    /**
     * The message group id the job should be sent to.
     *
     * @var string
     */
    public $messageGroupId;

    /**
     * The deduplication method to use for the job.
     *
     * @var string
     */
    public $deduplicate;

    /**
     * Set the desired message group id for the job.
     *
     * @param string $messageGroupId
     *
     * @return $this
     */
    public function onMessageGroup(string $messageGroupId)
    {
        $this->messageGroupId = $messageGroupId;

        return $this;
    }

    /**
     * Set the desired deduplication method for the job.
     *
     * @param string $deduplicate
     *
     * @return $this
     */
    public function withDeduplicate(string $deduplicate)
    {
        $this->deduplicate = $deduplicate;

        return $this;
    }

    /**
     * Remove the deduplication method from the job.
     *
     * @return $this
     */
    public function withoutDeduplicate()
    {
        return $this->withDeduplicate('');
    }
}