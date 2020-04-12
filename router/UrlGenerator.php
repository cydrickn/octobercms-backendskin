<?php

namespace Cyd293\BackendSkin\Router;

use Backend\Classes\Skin as AbstractSkin;
use October\Rain\Router\UrlGenerator as Base;
use Cyd293\BackendSkin\Classes\Skin;

class UrlGenerator extends Base
{
    public function asset($path, $secure = null)
    {
        if (!starts_with($path, '/themes') && !starts_with($path, '/backendskins')) {
            $publicSkinAssetPath = $this->getActiveSkin()->publicSkinPath  . '/views/' . ltrim($path, '/');
            $skinAssetPath = $this->getActiveSkin()->skinPath  . '/views/' . ltrim($path, '/');
            if (file_exists($skinAssetPath)) {
                $path = $publicSkinAssetPath;
            }
        }
        return parent::asset($path, $secure);
    }

    /**
     * @return \Cyd293\BackendSkin\Skin\BackendSkin
     */
    private function getActiveSkin()
    {
        return AbstractSkin::getActive();
    }
}
