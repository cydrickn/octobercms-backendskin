<?php

namespace Cyd293\BackendSkin\Models;

use Cyd293\BackendSkin\Classes\Skin;
use Model;
use System\Behaviors\SettingsModel;

class Settings extends Model
{
    public $implement = [SettingsModel::class];
    public $settingsCode = 'cyd293_backendskin_settings';
    public $settingsFields = 'fields.yaml';

    public function getThemeOptions($value, $formData)
    {
        return array_merge([
            '--frontend--' => 'Force same with Active FrontEnd Theme',
            'octobercms' => 'October CMS',
        ], Skin::getSkins());
    }
}
