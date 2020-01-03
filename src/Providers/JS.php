<?php
namespace ZanySoft\LaravelAssets\Providers;

use ZanySoft\LaravelAssets\Assets;
use ZanySoft\LaravelAssets\Processors\JSMin;

class JS extends ProviderBase implements ProviderInterface
{
    /**
     * @param  string $file
     * @param  string $public
     * @return string
     */
    public function pack($file, $public)
    {
        if (!Assets::isRemote($file) && !is_file($file)) {
            return sprintf('/* File %s not exists */', $file);
        }

        $contents = file_get_contents($file);
        $contents = trim($contents, "\r\n\t ");

        if ($this->settings['minify']) {
            $contents = JSMin::minify($contents);
        }

        $contents = "/* Code merged from: " . str_replace([public_path(),'\\'], ['','/'], $file) . " */\r\n" . $contents;

        return rtrim($contents, ";") . ";\r\n\r\n";
    }

    /**
     * @param  mixed $file
     * @return string
     */
    public function tag($file)
    {
        if (is_array($file)) {
            return $this->tags($file);
        }

        $attributes = $this->settings['attributes'];
        $attributes['src'] = $this->path($this->settings['asset'] . $file);

        return '<script ' . $this->attributes($attributes) . '></script>' . PHP_EOL;
    }
}
