<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait Config
{
    protected $config = [ ];
    
    /**
     *
     */
    public function getConfig($configpath = null)
    {
        if ($configpath === null) {
            return $this->config;
        }

        $request = explode('.', $configpath);

        $config = $this->config;

        foreach ($request as $segment) {

            if (!isset($config[$segment]) or $config[$segment] === null) {
                return null;
            }

            if (is_array($config[$segment])) {
                $config = $config[$segment];
                continue;
            }

            return $config[$segment];
        }

        return $config;
    }

    /**
     *
     */
    public function addConfig(array $config): \Frootbox\Db\Row
    {
        $this->config = array_replace_recursive($this->config ?? [], $config);
        $this->data['config'] = json_encode($this->config);

        $this->changed['config'] = true;

        return $this;
    }

    /**
     *
     */
    public function unsetConfig($configpath)
    {
        $request = explode('.', $configpath);
        $config = &$this->config;

        $loops = count($request);
        $loop = 0;

        foreach ($request as $segment) {

            if (++$loop == $loops) {

                unset($config[$segment]);
                break;
            }

            $config = &$config[$segment];
        }

        $this->changed['config'] = true;
        $this->data['config'] = json_encode($this->config);

        return $this;
    }
}
