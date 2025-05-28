<?php 
/**
 * 
 */

namespace Frootbox;

class Thumbnail extends GenericObject
{
    protected $path;
    protected $cacheFile;
    protected $type;
    protected $extension;
    
    protected $width;
    protected $height;
    protected $crop;
    protected $rotation = 0;

    protected $cropmode = null;
    protected $forceCacheFile = false;

    protected $config = null;

    /**
     * 
     */
    public function __construct(
        array $options,
        \Frootbox\Config\ConfigAccess $config = null
    )
    {
        if ($config !== null) {
            $this->config = $config;
        }

        if (!empty($options['path'])) {
            $this->path = $options['path'];
            
            $x = explode('.', $options['path']);
            $this->extension = array_pop($x);
        }
        
        if (!empty($options['width'])) {
            $this->width = $options['width'];
        }

        if (!empty($options['height'])) {
            $this->height = $options['height'];
        }

        if (!empty($options['crop'])) {
            $this->crop = $options['crop'];
        }

        if (!empty($params['forceCacheFile'])) {
            $this->forceCacheFile = true;
        }

        if (empty($options['type'])) {
            $x = explode('.', $options['path']);

            $this->type = $this->extension;

            if (IS_WEBP and $this->type != 'svg') {
                $this->type = 'webp';
            }
        }
        else {
            $this->type = $options['type'];
        }

        if (!empty($options['rotation'])) {
            $this->rotation = $options['rotation'];
        }

        if (!empty($options['extension'])) {
            $this->extension = $options['extension'];
        }
    }

    /**
     *
     */
    public function detectOrientation(): mixed
    {
        $prefix = $this->config->imagemagick->path ?? '/opt/local/bin/convert';

        $command = $prefix . ' ' . $this->getPath() . ' -format %[orientation] info:';

        $orientation = shell_exec($command);

        return $orientation;
    }

    /**
     *
     */
    public function exists(): bool
    {
        return file_exists(FILES_DIR . $this->path) or file_exists($this->path);
    }

    /**
     * 
     */
    public function getCacheFilePath(): string
    {
        if ($this->type == 'svg') {
           // return $this->getPath();
        }
        
        $path = FILES_DIR . 'cache/images/';

        if (empty($this->crop['x']) or empty($this->crop['y'])) {
            $this->crop = null;
        }

        $data = [
            (int) $this->width,
            (int) $this->height,
            $this->crop,
            (int) $this->rotation,
            $this->path,
        ];

        $fileName = md5(serialize($data)) . '.' . $this->type;

        $path .= $fileName[0] . '/' . $fileName[1] . '/' . substr($fileName, 2);

        if (!empty($_SERVER['HTTP_ACCEPT']) and strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false and !empty($this->config->webp) and $this->type != 'svg') {
            // $path .= '.webp';
        }

        return $path;
    }

    /**
     *
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }
    
    /**
     * 
     */
    public function getPath(): string
    {
        if (file_exists($this->path)) {
            return $this->path;
        }

        return FILES_DIR . $this->path;
    }

    /**
     * Render thumbnail
     */
    public function render(): Thumbnail
    {
        // Get cachefilename
        $this->cacheFile = $this->getCacheFilePath();
        $this->cacheFile = str_replace('/', DIRECTORY_SEPARATOR, $this->cacheFile);

        if (!file_exists($dir = dirname($this->cacheFile))) {
            $directory = new \Frootbox\Filesystem\Directory($dir);
            $directory->make();
        }

        if ($this->extension == 'svg') {

            if (!file_exists($this->cacheFile)) {
                copy(FILES_DIR . $this->path, $this->cacheFile);
            }

            return $this;
        }


        // $imageData = getimagesize($this->getPath());

        $physicalFile = $this->getPath() . '[0]';
        $physicalFile = str_replace('/', DIRECTORY_SEPARATOR, $physicalFile);

        // $prefix = '/usr/bin/';
        $prefix = $this->config->imagemagick->path ?? '/opt/local/bin/convert';

        if ($this->type == 'gif') {

            $command = $prefix . ' ' . $this->getPath() . ' -coalesce ' . $this->cacheFile . '.tmp';

            `$command`;

            $physicalFile = $this->cacheFile . '.tmp';
        }

        if ($this->width == 'original') {
            $this->width = null;
        }

        if ($this->height == 'original') {
            $this->height = null;
        }

        $resize = (string) null;
        $precmd = (string) null;
        $postcmd = (string) null;

        if ($this->rotation != 0) {
            $precmd .= ' -rotate ' . $this->rotation;
        }

        if (!empty($this->width) and !empty($this->height)) {


            if ($this->crop == 'none') {

                $resize = ' -resize ' . $this->width . 'x' . $this->height;
            }
            else {

                $resize = ' -resize ' . $this->width . 'x' . $this->height . '^ -quality 100 ';
                $postcmd = ' -gravity center -crop ' . $this->width . 'x' . $this->height . '+0+0';
            }

        }
        elseif (!empty($this->width)) {
            $resize = ' -resize ' . $this->width . ' -quality 100 ';
        }
        elseif (!empty($this->height)) {
            $resize = ' -resize x' . $this->height . ' -quality 100 ';
        }

        if (!empty($this->crop) and $this->crop != 'none') {
            $precmd = ' -crop ' . round($this->crop['width']) . 'x' . round($this->crop['height']) . '+' . round($this->crop['x']) . '+' . round($this->crop['y']);
        }

        // $command = $prefix . ' ' . $physicalFile . '  -strip ' . $precmd . ' ' . $resize . ' ' . $postcmd . ' ' . $this->cacheFile . '.webp';
        $command = $prefix . ' "' . $physicalFile . '"  -strip ' . $precmd . ' ' . $resize . ' ' . $postcmd . ' ' . $this->cacheFile;

        `$command`;

        if ($this->type != 'svg') {
           // file_put_contents($this->cacheFile, rand(00000, 99999), FILE_APPEND);
        }

        return $this;
    }

    /**
     *
     */
    public function setRotation(int $rotation): void
    {
        $this->rotation = $rotation;
    }
}
