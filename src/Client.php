<?php

namespace Twine\Raven;

use Exception;
use Raven_Client;
use Illuminate\Support\Arr;
use Illuminate\Queue\QueueManager;

class Client extends Raven_Client
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Illuminate\Queue\QueueManager|null
     */
    protected $queue;

    /**
     * Stores the latest event id.
     *
     * @var string
     */
    protected $eventId;

    /**
     * @param array $config,
     * @param \Illuminate\Queue\QueueManager $queue
     * @param string|null $env
     */
    public function __construct(array $config, QueueManager $queue = null, $env = null)
    {
        $this->config = $config;
        $this->queue = $queue;

        // merge env into options if set
        $options = array_replace_recursive(
            [
                'tags' => [
                    'environment' => $env,
                    'logger' => 'raven-php',
                ],
            ],
            Arr::get($config, 'options', [])
        );

        parent::__construct(Arr::get($config, 'dsn', ''), $options);
    }

    /**
     * Get the last stored event id.
     *
     * @return string 
     */
    public function getLastEventId()
    {
        return $this->eventId;
    }

    /**
     * {@inheritdoc}
     */
    public function send(&$data)
    {
        $this->eventId = Arr::get($data, 'event_id');

        // send error now if queue not set
        if (is_null($this->queue)) {
            return $this->sendError($data);
        }

        // put the job into the queue
        // Sync connection will sent directly
        // if failed to add job to queue send it now
        try {
            $this->queue
                ->connection(Arr::get($this->config, 'queue.connection'))
                ->push(
                    Job::class,
                    $data,
                    Arr::get($this->config, 'queue.name')
                );
        } catch (Exception $e) {
            return $this->sendError($data);
        }

        return;
    }

    /**
     * Send the error to sentry without queue.
     *
     * @return void
     */
    public function sendError($data)
    {
        return parent::send($data);
    }
}
