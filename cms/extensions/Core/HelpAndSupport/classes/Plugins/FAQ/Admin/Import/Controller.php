<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Import;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction (

    ): Response
    {
        $url = 'https://moebel-luebbering.de/service/pflegehinweise/allgemeines/';

        $source = file_get_contents($url);
        $source = str_replace('&nbsp;', ' ', $source);

        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($source);

        $questions = [];

        $crawler->filter('.Allgemeines h2.p1')->each(function ( $element ) use (&$questions) {

            $question = [
                'title' => (string) $element->text()
            ];

        });


        return self::getResponse();
    }
}