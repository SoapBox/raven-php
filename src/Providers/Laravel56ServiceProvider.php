<?php

namespace Twine\Raven\Providers;

use Illuminate\Log\Logger as IlluminateLogger;
use Twine\Raven\Logger;

class Laravel56ServiceProvider extends AbstractServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = $this->getPath();

        $this->publishes([$path => config_path('raven.php')], 'config');
        $this->mergeConfigFrom($path, 'raven');

        if (empty($this->app['config']['raven.dsn'])) {
            return;
        }

        $handler = $this->getHandler();

        $this->app['log']->pushHandler($handler);
    }

    /**
     * Register the logger instance in the container.
     *
     * @return void
     */
    protected function registerLogger()
    {
        $logger = new Logger(
            $this->app['log']->getName(),
            $this->app['log']->getHandlers(),
            $this->app['log']->getProcessors()
        );

        $this->app->instance('log', new IlluminateLogger(
            $logger,
            $this->app['log']->getEventDispatcher()
        ));
    }
}
