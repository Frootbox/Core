<?php
/**
 *
 */

namespace Frootbox;

class ConfigStatics
{
    protected $path;
    protected $statics;
    protected $config;

    /**
     *
     */
    public function __construct ( Config\Config $config )
    {
        $this->config = $config;

        $this->path = $this->config->get('filesRootFolder') . 'config/general.php';

        if (file_exists($this->path)) {
            $data = require $this->path;

            $this->statics = is_array($data) ? $data : [ ];
        }
        else {
            $this->statics = [ ];
        }
    }

    /**
     *
     */
    public function addConfig(array $config): ConfigStatics
    {
        $this->statics = array_replace_recursive($this->statics, $config);

        return $this;
    }

    /**
     *
     */
    public function getCacheRevision(): int
    {
        if (!empty($cacheRevision = $this->config->get('statics.cache.revision'))) {
            return $cacheRevision;
        }

        // Set cache revision
        $this->addConfig([
            'statics' => [
                'cache' => [
                    'revision' => 1
                ]
            ]
        ]);

        // Write config
        $this->write();

        return 1;
    }

    /**
     *
     */
    public function getSigningToken(): string
    {
        if (!empty($this->statics['statics']['signing']['token'])) {
            return $this->statics['statics']['signing']['token'];
        }

        // Generate token
        $token = md5(microtime(true));

        // Set token
        $this->addConfig([
            'statics' => [
                'signing' => [
                    'token' => $token
                ]
            ]
        ]);

        // Write config
        $this->write();

        return $token;
    }

    /**
     *
     */
    public function unsetConfig($configpath): void
    {
        $request = explode('.', $configpath);
        $config = &$this->statics;

        $loops = count($request);
        $loop = 0;

        foreach ($request as $segment) {

            if (++$loop == $loops) {

                unset($config[$segment]);
                break;
            }

            $config = &$config[$segment];
        }
    }

    /**
     *
     */
    public function write(): ConfigStatics
    {
        $source = '<?php return ' . var_export($this->statics, true) . ';';

        $file = new \Frootbox\Filesystem\File($this->path);
        $file->setSource($source);
        $file->write();

        return $this;
    }
}
