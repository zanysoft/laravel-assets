<?php

namespace ZanySoft\LaravelAssets\Providers;

use Intervention\Image\Facades\Image;

class IMG extends ProviderBase implements ProviderInterface
{
    /**
     * @param string $file
     * @return boolean
     */
    public function isImage($file)
    {
        $valid = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return in_array($ext, $valid, true);
    }

    /**
     * @param string $file
     * @return string
     */
    public function check($file)
    {
        return ($this->isImage($file) && is_file($file)) ? $file : $this->fake();
    }

    /**
     * @return string
     */
    public function fake()
    {
        if (empty($this->settings['fake'])) {
            return false;
        }

        return realpath(__DIR__ . '/../assets/' . rand(1, 8) . '.jpg');
    }

    /**
     * @param string $file
     * @param string $public
     * @return string
     */
    public function pack($file, $public)
    {
        if (!($file = $this->check($file))) {
            return;
        }

        $image = Image::make($file);

        $quality = $this->settings['quality'] ?? null;

        $transform = $this->settings['transform'] ?? null;
        if (!empty($transform)) {
            list($width, $height) = array_pad(explode('x', $transform), 2, null);
            if ($width || $height) {
                $image->resize($width, $height);
            }
        }

        return $image->stream($image->extension, $quality ? $quality : 60);
    }

    /**
     * @param string $file
     * @return string
     */
    public function tag($file)
    {
        if (empty($file)) {
            return '';
        }

        $attributes = $this->settings['attributes'];
        $file = is_array($file) ? $file[0] : $file;

        if (empty($attributes)) {
            return $this->path($this->settings['asset'] . $file);
        }

        $attributes['src'] = $this->path($this->settings['asset'] . $file);

        return '<img ' . $this->attributes($attributes) . ' />' . PHP_EOL;
    }
}
