<?php

namespace ZanySoft\LaravelAssets\Providers;

use ZanySoft\LaravelAssets\Assets;
use ZanySoft\LaravelAssets\Processors\JSMin;

class JS extends ProviderBase implements ProviderInterface
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
            $contents = JSMin::minify($contents);
        }

        $contents = $this->removeNewlines($contents);
        $contents = preg_replace('/(?<!\\\\)\/\*(.*?)\*(?<!\\\\)\//Ss', '', $contents);

        return rtrim($contents, ";") . ";\n";
    }

    /**
     * @param $buffer
     * @return string|string[]|null
     */
    public function removeNewlines($buffer)
    {
        # remove extra or unneccessary new line from javascript
        $buffer = preg_replace('/([;])\s+/', '$1', $buffer);
        $buffer = preg_replace('/([}])\s+(else)/', '$1else', $buffer);
        $buffer = preg_replace('/([}])\s+(var)/', '$1;var', $buffer);
        $buffer = preg_replace('/([{};])\s+(\$)/', '$1\$', $buffer);

        return $buffer;
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

        $rel = false;
        if (isset($attributes['rel']) && $attributes['rel']) {
            $rel = $attributes['rel'];
            if ($rel == 'preload') {
                $attributes['as '] = 'script';
            }
        }

        $src = $this->path($this->settings['asset'] . $file);

        $html = '<script src="' . $src . '" ' . $this->attributes($attributes) . '></script>' . PHP_EOL;

        if ($preload && $enabled && $rel != 'preload') {
            $html = '<link href="' . $src . '" rel="preload" as="script"></script>' . PHP_EOL . $html;
        }

        return $html;
    }
}
