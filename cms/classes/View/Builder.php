<?php
/**
 *
 */

namespace Frootbox\View;

class Builder
{
    protected ?string $baseFile;

    /**
     *
     */
    public function __construct(
        public \Frootbox\Db\Db $db,
        public \Frootbox\View\Engines\Interfaces\Engine $view,
    ) {}

    /**
     *
     */
    public function render(array $payload = null): string
    {
        $viewFile = $this->baseFile;

        // Check if view file is overloaded
        if (preg_match('#^\/(.*?)\/([a-z0-9]+)\/([a-z0-9]+)\/classes\/Plugins\/([a-z0-9]+)\/(.*?)\/builder\/(.*?)$#i', $this->baseFile, $match)) {

            $extensionRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Extensions::class);
            $extensions = $extensionRepository->fetch([
                'where' => [
                    'isactive' => 1,
                ],
            ]);

            foreach ($extensions as $extension) {

                $controller = $extension->getExtensionController();

                $overrideFile = $controller->getPath() . 'plugins/' . $match[2] . '/' . $match[3] . '/' . $match[4] . '/resources/private/builder/' . $match[6];

                if (file_exists($overrideFile)) {
                    $viewFile = $overrideFile;
                }
            }
        }

        return $this->view->render($viewFile, $payload);
    }

    /**
     *
     */
    public function setBaseFile(string $baseFile): void
    {
        $this->baseFile = $baseFile;
    }
}