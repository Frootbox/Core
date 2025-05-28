<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\System\Apps\ConfigurationValues;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\ConfigurationValues $configurationValues,
    ): Response
    {
        // Compose config value
        $configValue = new \Frootbox\Persistence\ConfigurationValue([
            'configKey' => $post->get('Key'),
        ]);

        $configurationValues->persist($configValue);
    }

    public function ajaxModalComposeAction(

    ): Response
    {
        return self::getResponse('html');
    }

    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\ConfigurationValues $configurationValues,
    ): Response
    {
        foreach ($post->get('Values') as $configId => $value) {

            /**
             *
             */
            $configValue = $configurationValues->fetchById($configId);
            $configValue->setConfigValue($value);
            $configValue->save();
        }

        // Generate cache file source
        $values = $configurationValues->fetch();
        $data = [];

        foreach ($values as $configValue) {
            $data[$configValue->getConfigKey()] = $configValue->getConfigValue();
        }

        $source = '<?php ' . PHP_EOL . 'return ' . var_export($data, true) . ';' . PHP_EOL;

        // Write cache file
        $cacheFilePath = FILES_DIR . 'cache/system/configValues.php';
        $file = new \Frootbox\Filesystem\File($cacheFilePath);
        $file->setSource($source);
        $file->write();
    }

    /** 
     * 
     */
    public function indexAction (
        \Frootbox\Persistence\Repositories\ConfigurationValues $configurationValues,
    ): Response
    {
        $values = $configurationValues->fetch();

        return self::getResponse(
            body: [
                'ConfigurationValues' => $values,
            ],
        );
    }    
}
