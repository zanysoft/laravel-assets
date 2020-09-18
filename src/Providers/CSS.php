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

        //$contents = preg_replace('/(url\([\'"]?)/', '$1' . $this->settings['asset'] . dirname($public) . '/', $contents);
        $contents = $this->replaceUrl($contents, $this->settings['asset'] . dirname($public) . '/');

        //$contents = "/* Code merged from: " . str_replace([public_path(), '\\'], ['', '/'], $file) . " */\r\n" . $contents;

        return $contents . "\r\n";
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

        $attributes = $this->settings['attributes'];
        $attributes['href'] = $this->path($this->settings['asset'] . $file);
        $attributes['rel'] = 'stylesheet';

        return '<link ' . $this->attributes($attributes) . ' />' . PHP_EOL;
    }
}
