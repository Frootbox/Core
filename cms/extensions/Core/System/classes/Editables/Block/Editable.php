<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\Block;

class Editable extends \Frootbox\AbstractEditable implements \Frootbox\Ext\Core\System\Editables\EditableInterface
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function initEditing(
        string $html,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): string
    {
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('[data-blocks][data-uid]')->each(function ( $element ) use (&$html, $view) {

            $uid = $element->getAttribute('data-uid');

            // Fetch editor
            $viewFile = $this->getPath() . 'Admin/resources/private/views/Navbar.html.twig';
            $editorHtml = $view->render($viewFile, [
                'label' => $element->getAttribute('data-label'),
                'uid' => $uid,
            ]);

            $element->removeAttribute('data-uid');
            $element->removeAttribute('data-blocks');

            $elementHtml = (string) $element;

            $element->setInnerHtml('<div data-blocks data-ignore="' . $element->getAttribute('data-ignore') . '" data-restrict="' . $element->getAttribute('data-restrict') . '" data-uid="' . $uid . '">' . $editorHtml . PHP_EOL . '<div class="blocks-content">' . $elementHtml . '</div></div>');
        });

        return $crawler->saveHTML();
    }

    /**
     *
     */
    public function parse (
        $html,
        \Frootbox\Config\Config $config,
        \DI\Container $container,
    ): string
    {

        // Parse blocks
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $blocks = $container->get(\Frootbox\Persistence\Content\Repositories\Blocks::class);

        $loops = [];

        $crawler->filter('[data-blocks][data-uid]')->each(function ($element) use ($blocks, $config, $container, &$loops) {

            $loop = 0;

            $uid = $element->getAttribute('data-uid');

            $result = $blocks->fetch([
                'where' => [
                    'uid' => $uid,
                    new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
                ],
                'order' => [ 'orderId DESC' ]
            ]);

            $html = (string) null;

            $injectedVariables = !empty($element->getAttribute('data-inject')) ? json_decode($element->getAttribute('data-inject'), true) : [];

            if (empty($injectedVariables)) {
                $injectedVariables = [];
            }

            $caged = $element->getAttribute('data-caged');

            $wasCalledFirst = false;

            foreach ($result as $block) {

                ++$loop;

                if (!defined('EDITING')) {
                    if (!empty($block->getConfig('skipLanguages.' . GLOBAL_LANGUAGE))) {
                        continue;
                    }
                }

                if (!isset($loops[$uid])) {
                    $loops[$uid] = 0;
                }

                $injectedVariables['loopId'] =  ++$loops[$uid];

                if ($wasCalledFirst) {
                    $block->setWasCalledFirst(true);
                }

                if ($caged) {
                    $block->setIsCaged(true);
                }

                $html .= $container->call([ $block, 'renderHtml' ], [
                    'injectedVariables' => $injectedVariables,
                ]);

                $wasCalledFirst = $block->getWasCalledFirst();

                if (defined('EDITING')) {
                    $html .= '<p style="margin: 15px 0; text-align: center;"><a data-predecessor="' . $block->getId() . '" data-uid="' . $uid . '" class="block-compose" style="display: inline-block; padding: 2px 7px; color: #FFF; background: #CCC; border-radius: 4px; font-size: 12px;" href=""><i class="far fa-plus"></i> Block hier hinzufügen</a></p>';
                }
            }

            $element->setInnerHtml($html);

        });

        $html = $crawler->saveHTML();

        return $html;
    }
}
