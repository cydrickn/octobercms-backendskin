<?php

namespace Cyd293\BackendSkin;

use Cyd293\BackendSkin\Router\UrlGenerator;
use Illuminate\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerUrlGenerator();
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
                return $this->app['session'];
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