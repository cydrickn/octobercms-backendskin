<?php

namespace Cyd293\BackendSkin\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use System\Classes\SettingsManager;

class BackendSkin extends Controller
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Cyd293.BackendSkin', 'settings');
    }

    public function getThemeOptions($value, $formData)
    {
        return ['octobercms' => 'October CMS Default Backend Skin'];
    }
}
