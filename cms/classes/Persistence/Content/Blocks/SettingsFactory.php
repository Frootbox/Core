<?php
/**
 *
 */

namespace Frootbox\Persistence\Content\Blocks;


class SettingsFactory
{
    /**
     *
     */
    public function __construct(
        protected \DI\Container $container,
    )
    {}

    public function getFromBlock(\Frootbox\Persistence\Content\Blocks\Block $block): array
    {
        // Fetch extensions
        $extensionRepository = $this->container->get(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions = $extensionRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $baseViewFile = null;
        $overrideViewFile = null;

        foreach ($extensions as $extension) {

            $viewFile = $extension->getExtensionController()->getPath() . 'classes/Blocks/' . $block->getBlockId() . '/Block.html.twig';

            if (!file_exists($viewFile)) {
                continue;
            }

            if ($extension->getExtensionId() == $block->getExtensionId() and $extension->getVendorId() == $block->getVendorId()) {
                $baseViewFile = $viewFile;
            }

            $xstring = 'override: ' . $block->getVendorId() . '/' . $block->getExtensionId() . '/' . $block->getBlockId();

            if (strpos(file_get_contents($viewFile), $xstring) !== false) {
                $overrideViewFile = $viewFile;
            }

            if (!empty($baseViewFile) and !empty($overrideViewFile)) {
                break;
            }
        }

        $viewFile = $overrideViewFile ?? $baseViewFile;
        $source = file_get_contents($viewFile);

        if (!preg_match('#\{\# config\s*(.*?)\s*\/config \#\}#s', $source, $match)) {
            return [];
        }

        // Parse yaml config
        $config = \Symfony\Component\Yaml\Yaml::parse($match[1]);

        return $config['settings'] ?? [];
    }
}
