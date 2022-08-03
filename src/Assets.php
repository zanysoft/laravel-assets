<?php

namespace ZanySoft\LaravelAssets;

use DirectoryIterator;
use Illuminate\Support\Str;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ZanySoft\LaravelAssets\Providers\CSS;
use ZanySoft\LaravelAssets\Providers\IMG;
use ZanySoft\LaravelAssets\Providers\JS;
use Exception;

class Assets
{
    /**
     * @var array
     */
    private $files = [];
    /**
     * @var array
     */
    private $paths = [];
    /**
     * @var string
     */
    private $name = '';
    /**
     * @var string
     */
    private $environment = '';
    /**
     * @var string
     */
    private $storage = '';
    /**
     * @var integer
     */
    private $newer = 0;
    /**
     * @var array
     */
    protected $scripts = [];
    /**
     * @var array
     */
    protected $styles = [];
    /**
     * @var array
     */
    private $config = [];
    /**
     * @var object
     */
    private $provider;
    /**
     * @var object;
     */
    private static $instance;

    /**
     * @param array $config
     * @return object
     */
    public static function getInstance(array $config)
    {
        return static::$instance ?: (static::$instance = new self($config));
    }

    /**
     * @param array $config
     * @return this
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->environment = app()->environment();

        $this->setConfig($config);
    }

    /**
     * @param array $config
     * @return this
     * @throws Exception
     */
    public function setConfig(array $config)
    {
        $config = array_merge($this->config, $config);

        $config['public_path'] = $config['public_path'] ?? public_path();

        if (!isset($config['cache_folder'])) {
            throw new Exception(sprintf('Missing option %s', 'cache_folder'));
        }

        if (!isset($config['check_timestamps'])) {
            throw new Exception(sprintf('Missing option %s', 'check_timestamps'));
        }

        if (!isset($config['css_minify'])) {
            throw new Exception(sprintf('Missing option %s', 'css_minify'));
        }

        if (!isset($config['js_minify'])) {
            throw new Exception(sprintf('Missing option %s', 'js_minify'));
        }

        if (!isset($config['environment']) || empty($config['environment'])) {
            $config['environment'] = app()->environment();
        }

        if (!isset($config['asset']) || empty($config['asset'])) {
            $config['asset'] = asset('/');
        }

        $this->config = $config;

		$this->config['enabled'] = $this->isEnable();
        return $this;
    }

    /**
     * @param mixed $files
     * @param string $name
     * @param array $attributes
     * @return this
	 * @throws \Exception
     */
    public function js($files, $name, array $attributes = [])
    {
        $this->provider = new Js([
            'asset' => $this->config['asset'],
            'minify' => $this->config['js_minify'],
            'attributes' => $attributes,
            'config' => $this->config
        ]);
        return $this->load('js', $files, $name);
    }

    /**
     * @param mixed $dir
     * @param string $name
     * @param boolean $recursive
     * @param array $attributes
     * @return this
     */
    public function jsDir($dir, $name, $recursive = false, array $attributes = [])
    {
        $this->provider = new JS([
            'asset' => $this->config['asset'],
            'minify' => $this->config['js_minify'],
            'attributes' => $attributes,
            'config' => $this->config
        ]);

        return $this->load('js', $this->scanDir('js', $dir, $recursive), $name);
    }

    /**
     * @param mixed $files
     * @param string $name
     * @param array $attributes
     * @return this
     */
    public function css($files, $name, array $attributes = [])
    {
        $this->provider = new CSS([
            'asset' => $this->config['asset'],
            'minify' => $this->config['css_minify'],
            'attributes' => $attributes,
            'config' => $this->config
        ]);

        return $this->load('css', $files, $name);
    }

    /**
     * @param mixed $dir
     * @param string $name
     * @param boolean $recursive
     * @param array $attributes
     * @return this
     */
    public function cssDir($dir, $name, $recursive = false, array $attributes = [])
    {
        $this->provider = new CSS([
            'asset' => $this->config['asset'],
            'minify' => $this->config['css_minify'],
            'attributes' => $attributes,
            'config' => $this->config
        ]);

        return $this->load('css', $this->scanDir('css', $dir, $recursive), $name);
    }

    /**
     * @param string $file
     * @param string $transform
     * @param string $new
     * @param array $attributes
     * @return this
     * @throws Exception
     */
    public function img($file, string $transform = null, string $new_name = '', array $attributes = [])
    {
        if (!is_string($file)) {
            throw new Exception('img function only supports strings');
        }

        $this->provider = new IMG([
            'asset' => $this->config['asset'],
            'fake' => $this->config['images_fake'],
            'transform' => $transform,
            'quality' => (isset($this->config['quality']) ? $this->config['quality'] : null),
            'attributes' => $attributes,
            'config' => $this->config
        ]);

        if (!$this->provider->check($this->path('public', $file))) {
            $this->file = false;

            return $this;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $new_name = trim(str_replace('\\', '/', $new_name), '/.');
        if ($new_name && strpos(basename($new_name), '.')) {
            $ext = trim(substr(basename($new_name), strrpos(basename($new_name), '.')), '.') ?: $ext;
        }

        $name = $this->getImgName($file, $new_name, $transform);

        return $this->load($ext, $file, $name);
    }

    /**
     * @param $file
     * @param $transform
     * @return string
     */
    private function getImgName($file, $new_name, $transform)
    {
        $name = preg_replace('/[^a-z0-9\-\_\.]/', '', strtolower(basename($file)));
        $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($transform) {
        if (preg_match('/^[^0-9]+([0-9]+),([0-9]+).*$/', $transform)) {
            $transform = preg_replace('/^[^0-9]+([0-9]+),([0-9]+).*$/', '$1x$2', $transform);;
        } elseif (preg_match('/^[^,]+,([0-9]+).*$/', $transform)) {
            $transform = preg_replace('/^[^,]+,([0-9]+).*$/', '$1', $transform);;
        } else {
            $transform = preg_replace('/^([^,]+).*$/', '$1', $transform);
        }
        }

        if ($new_name) {
            $new_name = str_replace('\\', '/', strtolower(trim($new_name, '/')));
            $has_slash = strpos($new_name, '/');
            $has_dot = strpos(basename($new_name), '.');

            if (!$has_slash && !$has_dot) {
                $new_name = 'images/' . substr(md5($file . $transform), 0, 3) . '/' . $new_name . '.' . $type;
            } elseif (!$has_dot) {
                $new_name = $new_name . '/' . ($transform ? $transform . '_' : '') . $name;
            }
        }

        return $new_name ? $new_name : 'images/' . substr(md5($file . $transform), 0, 3) . '/' . ($transform ? $transform . '_' : '') . $name;
    }

    /**
     * @param string $ext
     * @param string $dir
     * @param boolean $recursive
     * @return array
     * @throws Exception
     */
    private function scanDir($ext, $dir, $recursive = false)
    {
        $files = [];

        if (is_array($dir)) {
            foreach ($dir as $each) {
                $files = array_merge($files, $this->scanDir($ext, $each, $recursive));
            }
            return $files;
        }

        $dir = $this->path('public', $dir);

        if (!is_dir($dir)) {
            throw new Exception(sprintf('Folder %s not exists', $dir));
        }

        if ($recursive) {
            $Iterator = new RecursiveDirectoryIterator($dir);
            $Iterator = new RegexIterator(new RecursiveIteratorIterator($Iterator), '/\.' . $ext . '$/i', RegexIterator::MATCH);
        } else {
            $Iterator = new DirectoryIterator($dir);
            $Iterator = new RegexIterator(new IteratorIterator($Iterator), '/\.' . $ext . '$/i', RegexIterator::MATCH);
        }

        $public = $this->path('public');

        foreach ($Iterator as $file) {
            $files[] = str_replace($public, '', $file->getPathname());
        }

        return $files;
    }

    /**
     * @param string $type
     * @param $files
     * @param string $name
     * @return this
     * @throws Exception
     */
    public function load($type, $files, $name)
    {
        $files = is_array($files) ? $files : [$files];
        foreach ($files as $k => $v) {
            if($v) {
                if (Str::startsWith($v, asset(''))) {
                    $files[$k] = str_replace(asset(''), '', $v);
                }
            }
        }

        $this->files = $files;

        if (strpos($name, '/') !== 0) {
            $name = $this->path('', $this->config['cache_folder'] . '/' . $name);
        }

        if (preg_match('/\.' . $type . '$/i', $name)) {
            $this->storage = dirname($name) . '/';
            $this->name = basename($name);
        } else {
            $this->storage = $this->path('', $name . '/');
            $this->name = md5(implode('', $this->files)) . '.' . $type;
        }

        if ($this->isEnable() && $this->files && ($this->config['check_timestamps'] === true)) {
            $times = array_map(function ($file) {
                if (Str::contains($file, '?')) {
                    $file = Str::before($file, '?');
                }
                if (!static::isRemote($file) && is_file($file = $this->path('public', $file))) {
                    return filemtime($file);
                }
            }, $this->files);

            $this->newer = max($times);

            if (empty($this->newer)) {
                $this->newer = time();
            }
            //$this->name = $this->newer . '-' . $this->name;
        }

        $this->file = $this->path('public', $this->storage . $this->name);

        return $this->process();
    }

    /**
     * @param string $name
     * @param string $location
     * @return string
     * @throws Exception
     */
    public function path($name, $location = '')
    {
        if (preg_match('#^https?://#', $location)) {
            return $location;
        }

        if ($name === '') {
            $path = '';
        } elseif ($name === 'public') {
            $path = $this->config['public_path'];
        } else {
            throw new Exception(sprintf('This path does not exists %s', $name));
        }

        return preg_replace('#(^|[^:])//+#', '$1/', $path . '/' . $location);
    }

    /**
     * @return this
     * @throws Exception
     */
    private function process()
    {
        if ($this->useCache()) {
            return $this;
        }

        $this->checkDir(dirname($this->file));

        if (is_dir($this->file)) {
            throw new Exception(sprintf('File %s can not be created because folder exist with same name.', $this->file));
        }

        if (!($fp = @fopen($this->file, 'c'))) {
            throw new Exception(sprintf('File %s can not be created', $this->file));
        }

        foreach ($this->files as $file) {
            if (Str::contains($file, '?')) {
                $file = Str::before($file, '?');
            }
            fwrite($fp, $this->provider->pack($this->path('public', $file), $file));
        }

        fclose($fp);

        return $this;
    }

    /**
     * @return boolean
     */
    private function useCache()
    {
        if (!$this->isEnable()) {
            return true;
        }

        if (!is_file($this->file)) {
            return false;
        }

        if ($this->config['check_timestamps'] === false) {
            return true;
        }
        return ($this->newer < filemtime($this->file));
    }

    /**
     * @param string $dir
     * @return boolean
     * @throws Exception
     */
    private function checkDir($dir)
    {
        if (is_dir($dir)) {
            return true;
        }

        if (!@mkdir($dir, 0755, true)) {
            throw new Exception(sprintf('Folder %s can not be created', $dir));
        }
    }

    /**
     * Add scripts to current module.
     *
     * @param array $assets
     * @return $this
     */
    public function addScripts($assets)
    {
        $this->scripts = array_merge($this->scripts, (array)$assets);
        return $this;
    }

    /**
     * Add Css to current module.
     *
     * @param string[] $assets
     * @return $this
     */
    public function addStyles($assets)
    {
        $this->styles = array_merge($this->styles, (array)$assets);
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (!$this->isEnable()) {
            $list = $this->files;
        } else {
            $list = $this->storage . $this->name;
            if ($this->config['check_timestamps']) {
                if ($this->newer) {
                    $list .= '?v=' . $this->newer;
                } elseif ($this->config['check_timestamps']) {
                    $list .= '?=' . time();
                }
            }
        }

        $this->files = [];
        $this->storage = '';
        $this->name = '';

        return $this->provider->tag($list);
    }

    /**
     * @return string
     */
    public function links()
    {
        if (!$this->isEnable()) {
            $list = $this->files;
        } else {
            $list = $this->storage . $this->name;
            if ($this->config['check_timestamps']) {
                if ($this->newer) {
                    $list .= '?v=' . $this->newer;
                } elseif ($this->config['check_timestamps']) {
                    $list .= '?=' . time();
                }
            }
        }

        $this->files = [];
        $this->storage = '';
        $this->name = '';

        return $this->provider->links($list);
    }


    /**
     * @return boolean
     */
    protected function isEnable()
    {
        $is_enable = $this->config['enable_cache'] ?? false;

        $ignore_environments = $this->config['ignore_environments'] ?? [];

        if (!is_array($ignore_environments)) {
            $ignore_environments = array_map('trim', explode(',', $ignore_environments));
        }

        $is_local = in_array($this->config['environment'], $ignore_environments, true);

        return $is_enable && !$is_local;

    }

    /**
     * @return boolean
     */
    protected function isLocal()
    {
        $ignore_environments = $this->config['ignore_environments'] ?? [];

        if (!is_array($ignore_environments)) {
            $ignore_environments = array_map('trim', explode(',', $ignore_environments));
        }

        return ($this->force['current'] === false) && in_array($this->config['environment'], $ignore_environments, true);
    }

    public static function isRemote($file)
    {
        return preg_match('#https?://#', $file);
    }

    /**
     * @return boolean
     */
    public function getFilePublic()
    {
        return $this->storage . $this->name;
    }

    /**
     * @return boolean
     */
    public function getFilePath()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
