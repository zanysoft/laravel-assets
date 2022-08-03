<?php
if (!function_exists('add_css')) {
    function add_css()
    {
        \Assets::css(func_get_args());
    }
}

if (!function_exists('add_css_dir')) {
    function add_css_dir()
    {
        \Assets::cssDir(func_get_args());
    }
}

if (!function_exists('add_js')) {
    function add_js()
    {
        \Assets::js(func_get_args());
    }
}

if (!function_exists('add_js_dir')) {
    function add_js_dir()
    {
        \Assets::jsDir(func_get_args());
    }
}
