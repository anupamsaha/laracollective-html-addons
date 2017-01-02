<?php

namespace AnupamSaha\LaravelExtra\Html;

use Collective\Html\HtmlServiceProvider as BaseHtmlServiceProvider;

class HtmlServiceProvider extends BaseHtmlServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration files
        $this->publishes(
            [
            __DIR__ . '/../config/htmlprotector.php' => config_path('htmlprotector.php')
            ], 'config'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHtmlBuilder();

        $this->registerFormBuilder();

        $this->app->alias('html', AnupamSaha\LaravelExtra\Html\HtmlBuilder::class);
        $this->app->alias('form', AnupamSaha\LaravelExtra\Html\FormBuilder::class);
    }

    /**
     * Register the HTML builder instance.
     *
     * @return void
     */
    protected function registerHtmlBuilder()
    {
        $this->app->singleton('html', function ($app) {
            return new HtmlBuilder($app['url']);
        });
    }

    /**
     * Register the form builder instance.
     *
     * @return void
     */
    protected function registerFormBuilder()
    {
        $this->app->singleton('form', function ($app) {
            $form = new FormBuilder($app['html'], $app['url'], $app['session.store']->getToken());

            return $form->setSessionStore($app['session.store']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['html', 'form', AnupamSaha\LaravelExtra\Html\HtmlBuilder::class, AnupamSaha\LaravelExtra\Html\FormBuilder::class];
    }
}
