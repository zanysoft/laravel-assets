<?php

namespace ZanySoft\LaravelAssets\Providers;

use ZanySoft\LaravelAssets\Assets;
use ZanySoft\LaravelAssets\Processors\CSSmin;

class CSS extends ProviderBase implements ProviderInterface
{
    /**
     * @param string $file
     * @param string $public
     * @return string
     */
    public function pack($file, $public)
    {
        if (!Assets::isRemote($file) && !is_file($file)) {
            return sprintf("/* File %s not exists */", $file);
        }

        $contents = file_get_contents($file);
        $contents = trim($contents, "\r\n\t ");

        if ($this->settings['minify']) {
            $contents = (new CSSmin())->run($contents);
        }

        $contents = $this->replaceUrl($contents, $this->settings['asset'] . dirname($public) . '/');

        $contents = preg_replace('/\n\r+/', "", $contents);

        return trim($contents) . ' ';
    }

    protected function replaceUrl($content, $domain)
    {
        $domain = str_replace('//www.', '//', $domain);

        $rep['/url[\s]*\([\s]*"[\s]*(?!https?:\/\/)(?!data:)(?!#)/i'] = 'url("' . $domain;
        $rep["/url[\s]*\([\s]*'[\s]*(?!https?:\/\/)(?!data:)(?!#)/i"] = "url('" . $domain;
        $rep["/url[\s]*\([\s]*(?!'|\")(?!https?:\/\/)(?!data:)(?!#)/i"] = "url(" . $domain;

        $content = preg_replace(array_keys($rep), array_values($rep), $content);

        return $content;
    }

    /**
     * @param mixed $file
     * @return string
     */
    public function tag($file)
    {
        if (is_array($file)) {
            return $this->tags($file);
        }

        $enabled = $this->settings['config']['enabled'] ?? false;

        $attributes = $this->settings['attributes'];

        $preload = false;
        if (isset($attributes['preload'])) {
            $preload = $attributes['preload'];
            unset($attributes['preload']);
        }

        $rel = 'stylesheet';
        if (isset($attributes['rel']) && $attributes['rel']) {
            $rel = $attributes['rel'];
        }

        $href = $this->path($this->settings['asset'] . $file);

        $attributes['href'] = $href;
        $attributes['rel'] = $rel;
        if ($rel == 'preload') {
            $attributes['as '] = 'style';
        }

        $html = '<link ' . $this->attributes($attributes) . ' />' . PHP_EOL;
        if ($preload && $enabled && $rel != 'preload') {
            $html = '<link href="' . $href . '" rel="preload" as="style" />' . PHP_EOL . $html;
        }

        return $html;
    }
}
