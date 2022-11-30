<?php
/**
 *
 */

namespace Frootbox\Mail;

class EnvelopeInky extends Envelope
{
    protected $cssFiles = [];

    /**
     *
     */
    public function addCssFile(string $filePath): void
    {
        $this->cssFiles[] = $filePath;
    }

    /**
     *
     */
    public function parseHtmlBody(): void
    {
        $source = $this->bodyHtml;

        // Persist sources
        $source = preg_replace_callback('#src="(.*?)"#', function($match) {

            $file = $match[1];
            $md5 = md5_file($file);

            if (!file_exists($file)) {
                return '<!-- FILE MISSING -->';
            }

            $dir = FILES_DIR . 'cache/mail/';

            if (!file_exists($dir)) {
                $cachekeep = new \Frootbox\Filesystem\File($dir . '.cachekeep');
                $cachekeep->write();

                $htaccess = new \Frootbox\Filesystem\File($dir . '.htaccess');
                $htaccess->setSource('RewriteEngine off' . PHP_EOL);
                $htaccess->write();
            }

            $dir = $dir . substr($md5, 0, 2) . '/';

            if (!file_exists($dir)) {
                $oldumask = umask(0);
                mkdir($dir, 0777, true);
                umask($oldumask);
            }

            $target = $dir . $md5 . '_' . basename($file);

            copy($file, $target);

            $src = PUBLIC_CACHE_DIR . str_replace(FILES_DIR, '', $target);

            return 'src="' . $src . '"';
        }, $source);


        $inky = new \Frogbob\InkyPHP\InkyPHP();

        $source = $inky->releaseTheKraken($source);

        $cssFiles = $this->cssFiles;
        $cssFiles[] = CORE_DIR . 'cms/resources/private/mail/css/foundation-emails.css';

        $css = (string) null;

        foreach ($cssFiles as $cssFile) {
            $css .= file_get_contents($cssFile) . PHP_EOL . PHP_EOL;
        }

        $cssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $source = $cssToInlineStyles->convert($source, $css);

        $this->bodyHtml = $source;
    }
}
