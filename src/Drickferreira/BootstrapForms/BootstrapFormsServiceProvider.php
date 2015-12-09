<?php namespace Drickferreira\BootstrapForms;

use Collective\Html\HtmlServiceProvider as IlluminateHtmlServiceProvider;

class BootstrapFormsServiceProvider extends IlluminateHtmlServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;
        if (version_compare($app::VERSION, '5.1') < 0) {
            $this->package('drickferreira/bootstrap-forms');
        }

        $this->publishes([
        __DIR__.'/../../../twbs/bootstrap/dist/' => public_path('assets'),
        ], 'public');

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerFormBuilder()
    {
        $this->app->bindShared('form', function ($app) {
            $form = new FormBuilder($app['html'], $app['url'],
                $app['session.store']->getToken());

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
        return array();
    }


}
