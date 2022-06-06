<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-07-28
 */

namespace Frootbox\Admin\Controller\Editor\Text;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxUpdate(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgetsRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        // Obtain payload
        $payload = $post->getData();
        $toDelete = [];

        foreach ($payload as $uid => $textString) {

            // Strip empty paragraphs
            $textString = trim($textString);
            $textString = preg_replace('#<p>\s*<p>#', '<p>', $textString);
            $textString = preg_replace('#</p>\s*</p>#', '</p>', $textString);
            $textString = preg_replace('#<p>\s*</p>#', '', $textString);

            $textString = preg_replace('#href=\"www\.#', 'href="https://www.', $textString);

            // Fetch text
            $text = $texts->fetchByUid($uid, [ 'createOnMiss' => true ]);

            // Clean widgets
            $textString = preg_replace('#<figure(.*?)data-id="([0-9]{1,})"(.*?)>(.*?)</figure>#is', '<figure data-id="\\2"></figure>', $textString);

            // Force widgets to current text uid
            if (preg_match_all('#<figure data-id="([0-9]+)">#', $textString, $matches)) {

                foreach ($matches[1] as $widgetId) {

                    try {
                        $widget = $widgetsRepository->fetchById($widgetId);
                    }
                    catch ( \Exception $e ) {
                        continue;
                    }

                    $widget->setTextUid($uid);
                    $widget->save();
                }
            }


            // xxx
            $textString = preg_replace('#href="' . SERVER_PATH_PROTOCOL . 'edit\/#', 'href="', $textString);

            $textString = str_replace('href="' . SERVER_PATH_PROTOCOL, 'href="', $textString);

            // xxx
            // $textString = str_replace('href="' . SERVER_PATH_PROTOCOL, 'href="', $textString);

            // Kill texts if whole body is empty
            if (empty($textString) or preg_match('#^<p>\s*</p>$#is', $textString)) {
                $toDelete[] = $text;
            }


            // Save text
            $text->setText($textString);
            $text->save();
        }

        foreach ($toDelete as $text) {
            $text->delete();
        }

        return self::getResponse('json', 200);
    }
}
