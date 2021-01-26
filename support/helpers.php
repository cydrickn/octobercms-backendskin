<?php

if (!function_exists('backendskins_path')) {
    /**
     * Get the path to the themes folder.
     *
     * @param  string  $path
     * @return string
     */
    function backendskins_path($path = '')
    {
        return app('path.backendskins').($path ? '/'.$path : $path);
    }
}
