<?php
/**
 *
 */

namespace Frootbox;

class TranslatorFactory
{
    protected $db;
    protected $config;

    /**
     *
     */
    public function __construct (
        \Frootbox\Db\Db $db,
        \Frootbox\Config\Config $config
    ) {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     *
     */
    private function getTranslationFiles($language): array
    {
        // Obtain default language stack
        $stack = $this->config->get('i18n.defaults') ? $this->config->get('i18n.defaults')->getData() : [ ];

        foreach ($stack as $index => $xlang) {

            if ($xlang == $language) {
                unset($stack[$index]);
                break;
            }
        }

        // Put desired language ontop to stack
        $stack = array_reverse($stack);
        $stack[] = $language;

        // Fetch extensions
        $extensionsRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1
            ]
        ]);

        // Fetch plugins
        $contentElementsRepository = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $contentElements = $contentElementsRepository->fetchByQuery('SELECT className FROM content_elements WHERE type = "Plugin" GROUP BY className');

        // Gather language files
        $paths = [ ];

        foreach ($stack as $language) {

            // Gather paths
            $paths[] = [
                'file' => CORE_DIR . 'cms/resources/private/language/' . $language . '.php',
                'scope' => null
            ];


            // Gather language files from extensions
            foreach ($extensions as $extension) {


                try {
                    $controller = $extension->getExtensionController();

                    $paths[] = [
                        'file' => $controller->getPath() . 'resources/private/language/' . $language . '.php',
                        'scope' => substr(substr(get_class($controller), 0, -20), 13)
                    ];
                }
                catch ( \Exception $e) {

                }

            }

            // Gather language files from plugins
            foreach ($contentElements as $plugin) {

                $paths[] = [
                    'file' => $plugin->getPath() . 'resources/private/language/' . $language . '.php',
                    'scope' => substr(substr(get_class($plugin), 0, -7), 13)
                ];

                $additionalFiles = $plugin->getAdditionalLanguageFiles($language);

                if (!empty($additionalFiles)) {

                    foreach ($additionalFiles as $file) {

                        $paths[] = [
                            'file' => $file['file'],
                            'scope' => $file['scope'],
                        ];
                    }
                }
            }
        }

        return $paths;
    }

    /**
     *
     */
    public function get($language): \Frootbox\Translation\Translator
    {
        // Look for cache hit
        $cacheFile = FILES_DIR . 'cache/language/' . $language . '.php';

        // Load translator from cache
        if (file_exists($cacheFile)) {
            $data = require $cacheFile;

            $translator = new \Frootbox\Translation\Translator;
            $translator->setData($data);

            return $translator;
        }

        // Obtain language files
        $files = $this->getTranslationFiles($language);

        $translator = new \Frootbox\Translation\Translator;

        foreach ($files as $path) {

            if (!file_exists($path['file'])) {
                continue;
            }

            $scope = !empty($path['scope']) ? str_replace('\\', '.', $path['scope']) : (string) null;

            $translator->addResource($path['file'], $scope);
        }

        $source = '<?php return ' . var_export($translator->getData(), true) . ';';

        $file = new \Frootbox\Filesystem\File($cacheFile);
        $file->setSource($source);
        $file->write();

        return $translator;
    }
}
