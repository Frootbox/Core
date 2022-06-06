<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Page\Partials\ListSockets;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     *
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config
    )
    {
        if (empty($config->get('layoutRootFolder'))) {
            throw new \Frootbox\Exceptions\RuntimeError('LayoutRootFolder is not set.');
        }

        // Fetch page
        $page = $pages->fetchById($this->getData('pageId'));
        
        $view->set('page', $page);

        // Get page layout source
        $layoutFile = $config->get('layoutRootFolder') . $page->getLayout();

        $files = [
            [
                'type' => 'layout',
                'path' => $layoutFile
            ]
        ];

        $source = file_get_contents($layoutFile);

        if (preg_match('#{% extends "(.*?)" %}#i', $source, $match)) {

            $files[] = [
                'type' => 'page',
                'path' => $config->get('pageRootFolder') . $match[1]
            ];
        }

        $list = [ ];

        foreach ($files as $file) {

            if (!file_exists($file['path'])) {
                continue;
            }

            $source = file_get_contents($file['path']);

            preg_match_all('#<div.*?data-socket="(.*?)".*?></div>#', $source, $matches);

            foreach ($matches[1] as $index => $socketName) {

                $tagLine = $matches[0][$index];

                $soc = [
                    'socket' => $socketName
                ];

                preg_match_all('#data-([a-z]{1,})="(.*?)"#i', $tagLine, $attrMatches);

                // Extract sockets attributes: <div data-xxx="yyyy"></div>
                foreach ($attrMatches[1] as $index => $attribute) {
                    $soc['attributes'][$attribute] = (string) $attrMatches[2][$index];
                }

                preg_match_all('#data-([a-z]{1,})[\s>]{1}#i', $tagLine, $attrMatches);

                // Extract sockets attributes: <div data-xxx></div>
                foreach ($attrMatches[1] as $index => $attribute) {
                    $soc['attributes'][$attribute] = true;
                }

                // Fetch elements
                $sql = 'SELECT
                    e.*
                FROM
                    pages p,
                    content_elements e
                WHERE
                    e.socket = "' . $soc['socket'] . '" AND            
                    (
                        ( e.pageId = ' . $page->getId() . ' AND e.pageId = p.id ) OR
                        ( e.pageId = p.id AND e.inheritance = "Inherited" AND p.lft < ' . $page->getLft() . ' AND p.rgt > ' . $page->getRgt() . ')    
                    )
                ORDER BY
                    orderId DESC';

                $soc['elements'] = $contentElements->fetchByQuery($sql);
                            
                $list[$file['type']][] = $soc;
            }
        }

        $view->set('sockets', $list);
    }
}
