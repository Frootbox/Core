<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\DeveloperTools;

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
    public function cleanupAction (
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Persistence\Content\Elements\Repositories\Texts $textsElements,
        \Frootbox\Persistence\Repositories\ContentElements $contentElements
    ) {


        function convertUtf8 ( $string ) {

            $xtext = str_replace('â€ž', '„', $string);
            $xtext = str_replace('â€œ', '“', $xtext);
            $xtext = str_replace('â€“', '–', $xtext);

            $xtext = str_replace('Ã¤', 'ä', $xtext);

            $xtext = str_replace('Ã–', 'Ö', $xtext);
            $xtext = str_replace('Ã¶', 'ö', $xtext);


            $xtext = str_replace('Ãœ', 'Ü', $xtext);
            $xtext = str_replace('Ã¼', 'ü', $xtext);
            $xtext = str_replace('uÌˆ', 'ü', $xtext);

            $xtext = str_replace('ÃŸ', 'ß', $xtext);

            return $xtext;
        }

        $result = $texts->fetch();

        foreach ($result as $text) {


            if (substr($text->getDataRaw('uid'), 0, 58) == 'Frootbox-Persistence-Content-Repositories-ContentElements:') {

                $uid = 'Frootbox-Persistence-Content-Elements-Repositories-Texts:' . substr($text->getDataRaw('uid'), 58);


                $text->setUid($uid);
                $text->save();
            }

            $xtext = $text->getText();
            $xtext = convertUtf8($xtext);

            $text->setText($xtext);
            $text->save();
        }


        $result = $contentElements->fetch();

        foreach ($result as $element) {

            if ($element->getDataRaw('className') == 'Frootbox\Persistence\Content\Text') {

                $element->setModel('Frootbox\Persistence\Content\Elements\Repositories\Texts');
                $element->setTable('content_elements');

                $element->setClassName('Frootbox\Persistence\Content\Elements\Text');
                $element->save();
            }
            continue;

            $title = convertUtf8($element->getTitle());

            try {

                $element->setTitle($title);
                $element->save();
            }
            catch ( \Exception $e ) {

                d($e);
            }
        }

        die("OKK");
    }
    
    /** 
     * 
     */
    public function indexAction (
        \Frootbox\Admin\View $view,
        \Frootbox\Admin\Persistence\Repositories\Apps $apps
    )
    {

        // Fetch apps
        $result = $apps->fetch([
            'where' => [
                'menuId' => 'Frootbox/Ext/Core/Development'
            ]
        ]);

        $view->set('apps', $result);


        return self::response();
    }    
}