<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Apps\Translations;

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

    /**
     *
     */
    public function exportAction(
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): Response
    {
        $list = [];

        $loop = 0;

        foreach ($textsRepository->fetch() as $text) {

            if (preg_match('#^<figure data-id="([0-9]+)"></figure>$#', $text->getText())) {
                continue;
            }

            $source = $text->getText();
            $source = html_entity_decode($source);

            if (empty($text->getUidRaw())) {
                continue;
            }

            $entity = $text->getEntityByUid();

            $type = 'Unbekannt';
            $page = null;

            if ($entity !== null) {

                switch (get_class($entity)) {

                    case \Frootbox\Persistence\Content\Block::class:
                        $page = $entity->getEntityByUid();
                        break;

                    case \Frootbox\Persistence\File::class:

                        break;

                    default:
                        if (empty($entity->getPageId())) {
                            d($entity);
                        }

                        $page = $entity->getPage();
                        break;
                }
            }

            if (!$page) {
                $key = str_pad('x', 4, '0', STR_PAD_LEFT) . '-' . str_pad(++$loop, 4, '0', STR_PAD_LEFT);
            }
            else {
                $key = str_pad($page->getId(), 4, '0', STR_PAD_LEFT) . '-' . str_pad(++$loop, 4, '0', STR_PAD_LEFT);
            }

            $list[$key] = [
                'uid' => $text->getUidRaw(),
                'language' => $text->getLanguage(),
                'type' => $type,
                'url' => ($page ? $page->getUri([ 'absolute' => true ]) : null),
                'text' => $source,
                'new' => '',
            ];
        }

        ksort($list);

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Type: text/x-csv');
        header("Content-Disposition: attachment;filename=export.csv");

        $df = fopen("php://output", 'w');

        fputcsv($df, [ 'UID', 'Quell-Sprache', 'Typ', 'URL', 'Text original', 'Text übersetzt']);

        foreach ($list as $row) {
            fputcsv($df, $row);
        }
        fclose($df);


        die;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new Response();
    }
}
