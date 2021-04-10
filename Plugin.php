<?php

namespace Cyd293\BackendSkin;

use Backend\Classes\Skin as AbstractSkin;
use Backend\Classes\WidgetBase;
use Backend;
use Config;
use Cyd293\BackendSkin\Listener\PluginEventSubscriber;
use Cyd293\BackendSkin\Models\Settings;
use Cyd293\BackendSkin\Router\UrlGenerator;
use Cyd293\BackendSkin\Skin\BackendSkin;
use Event;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public $elevated = true;

    public function boot()
    {
        $this->app->instance('path.backendskins', $this->backendSkinPaths());
        Config::set('cms.backendSkin', BackendSkin::class);
        Event::subscribe(new PluginEventSubscriber());
        WidgetBase::extendableExtendCallback(function (WidgetBase $widget) {
            $origViewPath = $widget->guessViewPath();
            $newViewPath = str_replace(base_path(), '', $origViewPath);
            $newViewPath = $this->getActiveSkin()->skinPath . '/views/' . $newViewPath . '/partials';
            $widget->addViewPath($newViewPath);
        });
    }

    public function backendSkinPaths()
    {
        return base_path() . '/backendskins';
    }

    public function registerComponents()
    {
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'cyd293.backendskin::lang.settings.label',
                'description' => 'cyd293.backendskin::lang.settings.description',
                'category'    => 'system::lang.system.categories.cms',
                'class'       => Settings::class,
                'icon'        => 'icon-picture-o',
                'order'       => 500,
            ],
        ];
    }

    public function register()
    {
        $this->app->register(RoutingServiceProvider::class);
        $this->registerConsoleCommand('cyd293.backendskin', Console\SetSkinCommand::class);
    }

    /**
     * @return AbstractSkin
     */
    private function getActiveSkin()
    {
        return AbstractSkin::getActive();
    }
}
