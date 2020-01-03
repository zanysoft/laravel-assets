# Laravel >= 5 Assets

[![Build Status](https://travis-ci.org/zanysoft/laravel-assets.svg?branch=master)](https://travis-ci.org/zanysoft/laravel-assets)
[![Latest Stable Version](https://poser.pugx.org/laravel/assets/v/stable.png)](https://packagist.org/packages/laravel/assets)
[![Total Downloads](https://poser.pugx.org/laravel/assets/downloads.png)](https://packagist.org/packages/laravel/assets)
[![License](https://poser.pugx.org/laravel/assets/license.png)](https://packagist.org/packages/laravel/assets)

Inspired by: https://github.com/ceesvanegmond/minify

With this package you can pack and minify your existing css and javascript files. This process can be a little tough, this package simplies this process and automates it.

## Installation

Begin by installing this package through Composer.

```js
{
    "require": {
        "zanysoft/laravel-assets": "master-dev"
    }
}
```

### Laravel installation

```php

// config/app.php

'providers' => [
    '...',
    'ZanySoft\LaravelAssets\AssetsServiceProvider',
];

'aliases' => [
    '...',
    'Assets'    => 'ZanySoft\LaravelAssets\Facade',
];
```

Publish the config file:

```
php artisan vendor:publish
```

Now you have a ```Assets``` facade available.

#### CSS

```php
// resources/views/hello.blade.php

<html>
    <head>
        // Pack a simple file
        {{ Assets::css('/css/main.css', '/storage/cache/css/main.css') }}

        // Pack a simple file using cache_folder option as storage folder to packed file
        {{ Assets::css('/css/main.css', 'css/main.css') }}

        // Packing multiple files
        {{ Assets::css(['/css/main.css', '/css/bootstrap.css'], '/storage/cache/css/styles.css') }}

        // Packing multiple files using cache_folder option as storage folder to packed file
        {{ Assets::css(['/css/main.css', '/css/bootstrap.css'], 'css/styles.css') }}

        // Packing multiple files with autonaming based
        {{ Assets::css(['/css/main.css', '/css/bootstrap.css'], '/storage/cache/css/') }}

        // pack and combine all css files in given folder
        {{ Assets::cssDir('/css/', '/storage/cache/css/all.css') }}

        // pack and combine all css files in given folder using cache_folder option as storage folder to packed file
        {{ Assets::cssDir('/css/', 'css/all.css') }}

        // Packing multiple folders
        {{ Assets::cssDir(['/css/', '/theme/'], '/storage/cache/css/all.css') }}

        // Packing multiple folders with recursive search
        {{ Assets::cssDir(['/css/', '/theme/'], '/storage/cache/css/all.css', true) }}

        // Packing multiple folders with recursive search and autonaming
        {{ Assets::cssDir(['/css/', '/theme/'], '/storage/cache/css/', true) }}

        // Packing multiple folders with recursive search and autonaming using cache_folder option as storage folder to packed file
        {{ Assets::cssDir(['/css/', '/theme/'], 'css/', true) }}
    </head>
</html>
```

CSS `url()` values will be converted to absolute path to avoid file references problems.

#### Javascript

```php
// resources/views/hello.blade.php

<html>
    <body>
    ...
        // Pack a simple file
        {{ Assets::js('/js/main.js', '/storage/cache/js/main.js') }}

        // Pack a simple file using cache_folder option as storage folder to packed file
        {{ Assets::js('/js/main.js', 'js/main.js') }}

        // Packing multiple files
        {{ Assets::js(['/js/main.js', '/js/bootstrap.js'], '/storage/cache/js/styles.js') }}

        // Packing multiple files using cache_folder option as storage folder to packed file
        {{ Assets::js(['/js/main.js', '/js/bootstrap.js'], 'js/styles.js') }}

        // Packing multiple files with autonaming based
        {{ Assets::js(['/js/main.js', '/js/bootstrap.js'], '/storage/cache/js/') }}

        // pack and combine all js files in given folder
        {{ Assets::jsDir('/js/', '/storage/cache/js/all.js') }}

        // pack and combine all js files in given folder using cache_folder option as storage folder to packed file
        {{ Assets::jsDir('/js/', 'js/all.js') }}

        // Packing multiple folders
        {{ Assets::jsDir(['/js/', '/theme/'], '/storage/cache/js/all.js') }}

        // Packing multiple folders with recursive search
        {{ Assets::jsDir(['/js/', '/theme/'], '/storage/cache/js/all.js', true) }}

        // Packing multiple folders with recursive search and autonaming
        {{ Assets::jsDir(['/js/', '/theme/'], '/storage/cache/js/', true) }}

        // Packing multiple folders with recursive search and autonaming using cache_folder option as storage folder to packed file
        {{ Assets::jsDir(['/js/', '/theme/'], 'js/', true) }}
    </body>
</html>
```

### Config

```php
return array(

    /*
    |--------------------------------------------------------------------------
    | App environments to not pack
    |--------------------------------------------------------------------------
    |
    | These environments will not be minified and all individual files are
    | returned
    |
    */

    'ignore_environments' => ['local'],

    /*
    |--------------------------------------------------------------------------
    | Base folder to store packed files
    |--------------------------------------------------------------------------
    |
    | If you are using relative paths to second paramenter in css and js
    | commands, this files will be created with this folder as base.
    |
    | This folder in relative to 'public_path' value
    |
    */

    'cache_folder' => '/storage/cache/',

    /*
    |--------------------------------------------------------------------------
    | Check if some file to pack have a recent timestamp
    |--------------------------------------------------------------------------
    |
    | Compare current packed file with all files to pack. If exists one more
    | recent than packed file, will be packed again with a new autogenerated
    | name.
    |
    */

    'check_timestamps' => true,

    /*
    |--------------------------------------------------------------------------
    | Check if you want minify css files or only pack them together
    |--------------------------------------------------------------------------
    |
    | You can check this option if you want to join and minify all css files or
    | only join files
    |
    */

    'css_minify' => true,

    /*
    |--------------------------------------------------------------------------
    | Check if you want minify js files or only pack them together
    |--------------------------------------------------------------------------
    |
    | You can check this option if you want to join and minify all js files or
    | only join files
    |
    */

    'js_minify' => true,
);
```

If you set the `'check_timestamps'` option, a timestamp value will be added to final filename.
