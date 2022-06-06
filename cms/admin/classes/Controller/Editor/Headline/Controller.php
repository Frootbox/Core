<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-07-28
 */

namespace Frootbox\Admin\Controller\Editor\Headline;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxSwitch(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true
        ]);

        $text->addConfig([
            'isVisible' => ($text->getConfig('isVisible') === false)
        ]);

        $text->save();

        return self::getResponse('json', 200, [
            'headline' => [
                'visible' => $text->getConfig('isVisible')
            ]
        ]);
    }
}
