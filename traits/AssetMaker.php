<?php namespace System\Traits;

use App;
use Url;
use Html;
use File;
use Event;
use Backend;
use System\Models\PluginVersion;
use System\Classes\CombineAssets;
use Backend\Classes\Skin as AbstractSkin;

/**
 * AssetMaker Trait
 * Adds asset based methods to a class
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 * @license proprietary (this file only)
 * @link https://octobercms.com/eula
 */
trait AssetMaker
{
    /**
     * @var array Collection of assets to display in the layout.
     */
    protected $assets = ['js' => [], 'css' => [], 'rss' => []];

    /**
     * @var array Collection of combined and prioritized assets.
     */
    protected $assetBundles = ['js' => [], 'css' => []];

    /**
     * @var string assetPath specifies a public or relative path to the asset directory.
     */
    public $assetPath;

    /**
     * @var string assetLocalPath specifies a local path to the asset directory for the combiner.
     */
    public $assetLocalPath;

    /**
     * flushAssets disables the use, and subequent broadcast, of assets. This is useful
     * to call during an AJAX request to speed things up. This method works
     * by specifically targeting the hasAssetsDefined method.
     * @return void
     */
    public function flushAssets()
    {
        $this->assets = ['js' => [], 'css' => [], 'rss' => []];
        $this->assetBundles = ['js' => [], 'css' => []];
    }

    /**
     * Outputs `<link>` and `<script>` tags to load assets previously added with addJs and addCss method calls
     * @param string $type Return an asset collection of a given type (css, rss, js) or null for all.
     * @return string
     */
    public function makeAssets($type = null)
    {
        if ($type != null) {
            $type = strtolower($type);
        }

        // Prevent duplicates
        $this->removeDuplicates();

        $result = null;

        // StyleSheet
        if ($type == null || $type == 'css') {
            foreach ($this->assets['css'] as $asset) {
                if ($attributes = $this->renderAssetAttributes('css', $asset)) {
                    $result .= "<link {$attributes} />" . PHP_EOL;
                }
            }

            foreach ($this->combineBundledAssets('css') as $asset) {
                if ($attributes = $this->renderAssetAttributes('css', $asset)) {
                    $result .= "<link {$attributes} />" . PHP_EOL;
                }
            }
        }

        // RSS Feed
        if ($type == null || $type == 'rss') {
            foreach ($this->assets['rss'] as $asset) {
                if ($attributes = $this->renderAssetAttributes('rss', $asset)) {
                    $result .= "<link {$attributes} />" . PHP_EOL;
                }
            }
        }

        // JavaScript
        if ($type == null || $type == 'js') {
            foreach ($this->assets['js'] as $asset) {
                if ($attributes = $this->renderAssetAttributes('js', $asset)) {
                    $result .= "<script {$attributes}></script>" . PHP_EOL;
                }
            }

            foreach ($this->combineBundledAssets('js') as $asset) {
                if ($attributes = $this->renderAssetAttributes('js', $asset)) {
                    $result .= "<script {$attributes}></script>" . PHP_EOL;
                }
            }
        }

        return $result;
    }

    /**
     * addJs includes a JavaScript asset to the asset list
     */
    public function addJs($name, $attributes = [])
    {
        if (is_array($name)) {
            $name = $this->combineAssets($name, $this->getLocalPath($this->assetPath));
        }

        $jsPath = $this->getAssetPath($name);

        if (isset($this->controller)) {
            $this->controller->addJs($jsPath, $attributes);
        }

        // Attributes can be a scaler when used as a build reference
        if (is_scalar($attributes)) {
            $attributes = ['build' => $attributes];
        }

        $jsPath = $this->getAssetScheme($jsPath);

        $this->assets['js'][] = ['path' => $jsPath, 'attributes' => $attributes];
    }

    /**
     * addJsBundle includes a JS asset to the bundled combiner stream
     */
    public function addJsBundle(string $name, $attributes = [])
    {
        $jsPath = $this->getAssetPath($name);

        if (isset($this->controller)) {
            $this->controller->addJsBundle($jsPath, $attributes);
        }

        // Attributes can be a scaler when used as a build reference
        if (is_scalar($attributes)) {
            $attributes = ['build' => $attributes];
        }

        $this->assetBundles['js'][] = ['path' => $jsPath, 'attributes' => $attributes];
    }

    /**
     * addCss includes a StyleSheet asset to the asset list
     */
    public function addCss($name, $attributes = [])
    {
        if (is_array($name)) {
            $name = $this->combineAssets($name, $this->getLocalPath($this->assetPath));
        }

        $cssPath = $this->getAssetPath($name);

        if (isset($this->controller)) {
            $this->controller->addCss($cssPath, $attributes);
        }

        // Attributes can be a scaler when used as a build reference
        if (is_scalar($attributes)) {
            $attributes = ['build' => $attributes];
        }

        $cssPath = $this->getAssetScheme($cssPath);

        $this->assets['css'][] = ['path' => $cssPath, 'attributes' => $attributes];
    }

    /**
     * addCssBundle includes a CSS asset to the bundled combiner stream
     */
    public function addCssBundle(string $name, $attributes = [])
    {
        $cssPath = $this->getAssetPath($name);

        if (isset($this->controller)) {
            $this->controller->addCssBundle($cssPath, $attributes);
        }

        // Attributes can be a scaler when used as a build reference
        if (is_scalar($attributes)) {
            $attributes = ['build' => $attributes];
        }

        $this->assetBundles['css'][] = ['path' => $cssPath, 'attributes' => $attributes];
    }

    /**
     * addRss adds an RSS link asset to the asset list. Call $this->makeAssets()
     * in a view to output corresponding markup.
     */
    public function addRss($name, $attributes = [])
    {
        $rssPath = $this->getAssetPath($name);

        if (isset($this->controller)) {
            $this->controller->addRss($rssPath, $attributes);
        }

        if (is_string($attributes)) {
            $attributes = ['build' => $attributes];
        }

        $rssPath = $this->getAssetScheme($rssPath);

        $this->assets['rss'][] = ['path' => $rssPath, 'attributes' => $attributes];
    }

    /**
     * combineAssets runs asset paths through the Asset Combiner
     */
    public function combineAssets(array $assets, $localPath = ''): string
    {
        if (empty($assets)) {
            return '';
        }

        $assetPath = $localPath ?: $this->assetLocalPath;

        return Url::to(CombineAssets::combine($assets, $assetPath));
    }

    /**
     * Returns an array of all registered asset paths.
     * @return array
     */
    public function getAssetPaths()
    {
        $this->removeDuplicates();

        $assets = [];

        foreach ($this->assets as $type => $collection) {
            $assets[$type] = [];
            foreach ($collection as $asset) {
                $assets[$type][] = $this->getAssetEntryBuildPath($asset);
            }
        }

        foreach (['js', 'css'] as $bundleType) {
            foreach ($this->combineBundledAssets($bundleType) as $asset) {
                $assets[$bundleType][] = $this->getAssetEntryBuildPath($asset);
            }
        }

        return $assets;
    }

    /**
     * getAssetPath locates a file based on it's definition. If the file starts with
     * a forward slash, it will be returned in context of the application public path,
     * otherwise it will be returned in context of the asset path.
     * @param string $fileName File to load.
     * @param string $assetPath Explicitly define an asset path.
     * @return string Relative path to the asset file.
     */
    public function getAssetPath($fileName, $assetPath = null)
    {
        if (starts_with($fileName, ['//', 'http://', 'https://'])) {
            return $fileName;
        }

        if (!$assetPath) {
            $assetPath = $this->assetPath;
        }

        if (substr($fileName, 0, 1) == '/' || $assetPath === null) {
            return $fileName;
        }

        $path = $assetPath . '/' . $fileName;
        $publicSkinAssetPath = $this->getActiveSkin()->publicSkinPath  . '/views/' . ltrim($path, '/');
        $skinAssetPath = $this->getActiveSkin()->skinPath  . '/views/' . ltrim($path, '/');
        if (file_exists($skinAssetPath)) {
            $path = $publicSkinAssetPath;
        }

        return $assetPath . '/' . $fileName;
    }

    /**
     * hasAssetsDefined returns true if assets any have been added
     */
    public function hasAssetsDefined(): bool
    {
        return count($this->assets, COUNT_RECURSIVE) > 3 ||
            count($this->assetBundles, COUNT_RECURSIVE) > 2;
    }

    /**
     * Internal helper, attaches a build code to an asset path
     * @param  array $asset Stored asset array
     * @return string
     */
    protected function getAssetEntryBuildPath($asset)
    {
        $path = $asset['path'];
        if (isset($asset['attributes']['build'])) {
            $build = $asset['attributes']['build'];

            if (!App::runningInBackend()) {
                $build = '';
            }
            elseif ($build === 'core') {
                $build = 'v' . Backend::assetVersion();
            }
            elseif ($pluginVersion = PluginVersion::getVersion($build)) {
                $build = 'v' . $pluginVersion;
            }
            else {
                $build = '';
            }

            if (strlen($build)) {
                $path .= '?' . $build;
            }
        }

        return $path;
    }

    /**
     * getAssetScheme is an internal helper to get the asset scheme.
     */
    protected function getAssetScheme(string $asset): string
    {
        if (starts_with($asset, ['//', 'http://', 'https://'])) {
            return $asset;
        }

        if (substr($asset, 0, 1) == '/') {
            $asset = Url::asset($asset);
        }

        return $asset;
    }

    /**
     * removeDuplicates removes duplicate assets from the entire collection.
     */
    protected function removeDuplicates()
    {
        $removeFunc = function($group) {
            foreach ($group as &$collection) {
                $pathCache = [];
                foreach ($collection as $key => $asset) {
                    if (!$path = array_get($asset, 'path')) {
                        continue;
                    }

                    if (isset($pathCache[$path])) {
                        array_forget($collection, $key);
                        continue;
                    }

                    $pathCache[$path] = true;
                }
            }

            return $group;
        };

        $this->assets = $removeFunc($this->assets);
        $this->assetBundles = $removeFunc($this->assetBundles);
    }

    /**
     * getLocalPath converts a relative path to a local path
     */
    protected function getLocalPath(string $relativePath): string
    {
        $relativePath = File::symbolizePath($relativePath);

        if (!starts_with($relativePath, [base_path()])) {
            $relativePath = base_path($relativePath);
        }

        return $relativePath;
    }

    /**
     * renderAssetAttributes takes an asset definition and returns the necessary HTML output
     */
    protected function renderAssetAttributes(string $type, array $asset): string
    {
        if (!$path = $this->getAssetEntryBuildPath($asset)) {
            return '';
        }

        // Internal attributes to be purged
        // - build: the unique build code for cache busting
        $reserved = ['build'];
        $userAttrs = array_except(array_get($asset, 'attributes', []), $reserved);

        /**
         * @event system.assets.beforeAddAsset
         * Provides an opportunity to inspect or modify an asset.
         *
         * The parameters provided are:
         * string `$type`: The type of the asset being added
         * string `$path`: The path to the asset being added
         * array `$attributes`: The array of attributes for the asset being added.
         *
         * All the parameters are provided by reference for modification.
         * This event is also a halting event, so returning false will prevent the
         * current asset from being added. Note that duplicates are filtered out
         * before the event is fired.
         *
         * Example usage:
         *
         *     Event::listen('system.assets.beforeAddAsset', function (string $type, string $path, array $attributes) {
         *         if (in_array($path, $blockedAssets)) {
         *             return false;
         *         }
         *     });
         *
         * Or
         *
         *     $this->bindEvent('assets.beforeAddAsset', function (string $type, string $path, array $attributes) {
         *         $attributes['special_cdn_flag'] = false;
         *     });
         *
         */
        if (
            (method_exists($this, 'fireEvent') && ($this->fireEvent('assets.beforeAddAsset', [&$type, &$path, &$userAttrs], true) === false)) ||
            (Event::fire('system.assets.beforeAddAsset', [&$type, &$path, &$userAttrs], true) === false)
        ) {
            return '';
        }

        // Determine final attributes
        $attrs = [];
        if ($type === 'css') {
            $attrs['rel'] = 'stylesheet';
            $attrs['href'] = $path;
        }
        elseif ($type === 'js') {
            $attrs['src'] = $path;
        }
        elseif ($type === 'rss') {
            $attrs['rel'] = 'alternate';
            $attrs['href'] = $path;
            $attrs['title'] = 'RSS';
            $attrs['type'] = 'application/rss+xml';
        }

        // Generate HTML attribute string
        return trim(Html::attributes(array_merge($attrs, $userAttrs)));
    }

    /**
     * combineBundledAssets spins over every bundle definition and combines them to an asset
     */
    protected function combineBundledAssets($type): array
    {
        $assets = [];
        $bundles = [];

        // Split bundles in to builds
        foreach ($this->assetBundles[$type] as $asset) {
            $build = $asset['build'] ?? 'core';
            $bundles[$build][] = $asset;
        }

        // Combine all asset paths and defined attributes
        foreach ($bundles as $build => $bundle) {
            $paths = [];
            $attributes = [];
            foreach ($bundle as $asset) {
                $paths[] = $this->getLocalPath($asset['path'] ?? '');
                $attributes += $asset['attributes'] ?? [];
            }

            $assets[] = ['path' => $this->combineAssets($paths), 'attributes' => $attributes];
        }

        return $assets;
    }

    /**
     * @return AbstractSkin
     */
    private function getActiveSkin()
    {
        return AbstractSkin::getActive();
    }
}
