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
        $twbsPath = 'vendor/twbs/bootstrap/dist';
        $this->publishes([$twbsPath => public_path('assets')]);

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
