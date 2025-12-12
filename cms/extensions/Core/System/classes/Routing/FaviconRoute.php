<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class FaviconRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#^favicon.ico|apple-touch-icon.png|android-chrome-192x192.png|android-chrome-256x256.png|android-chrome-512x512.png|browserconfig.xml|favicon-16x16.png|favicon-96x96.png|favicon-32x32.png|mstile-150x150.png|safari-pinned-tab.svg|site\.webmanifest$#i';
    }

    /**
     *
     */
    public function performRouting(
        \DI\Container $container,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        // Obtain filename
        $filename = $this->getRequest()->getRequestTarget();

        // Fetch extensions
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1
            ]
        ]);

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath() . 'resources/public/favicon/' . $filename;

            if (file_exists($path)) {

                preg_match('#\.([a-z]{3,})$#', $path, $match);

                $contentTypes = [
                    'png' => 'image/png',
                    'ico' => 'image/vnd.microsoft.icon',
                    'xml' => 'application/xml; charset=UTF-8',
                    'webmanifest' => 'application/manifest+json',
                    'svg' => 'image/svg+xml'
                ];

                http_response_code(200);
                header('Content-Type: ' . $contentTypes[$match[1]]);
                readfile($path);
                exit;
            }
        }

        d($filename);
    }
}
