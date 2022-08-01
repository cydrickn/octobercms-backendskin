<?php

namespace Cyd293\BackendSkin;

use Cyd293\BackendSkin\Router\UrlGenerator;
use October\Rain\Html\UrlServiceProvider;

class RoutingServiceProvider extends UrlServiceProvider
{
    public function register()
    {
        $this->registerUrlGenerator();
        parent::register();
    }

    protected function registerUrlGenerator()
    {
        $this->app->extend('url', function ($service, $app) {
            $routes = $app['router']->getRoutes();
            $url = new UrlGenerator(
                $routes,
                $app->rebinding(
                    'request',
                    $this->requestRebinder()
                )
            );

            $url->setSessionResolver(function () {
                return $this->app['session'] ?? null;
            });

            $url->setKeyResolver(function () {
                return $this->app->make('config')->get('app.key');
            });

            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    protected function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }

}
