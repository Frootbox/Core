<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Admin\Configuration;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxModalImportAction(

    ): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository,
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noQuestionsDetailPage' => $post->get('noQuestionsDetailPage'),
        ]);

        $this->plugin->save();

        // Update entities
        $result = $questionsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $entity) {

            $entity->addConfig([
                'noQuestionsDetailPage' => $post->get('noQuestionsDetailPage'),
            ]);

            $entity->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    public function exportAction(
        \Frootbox\Persistence\Content\Repositories\Texts $textRepository,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository,
    ): never
    {
        $exportData = [
            'questions' => [],
        ];

        $result = $questionsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $question) {

            $questionData = $question->getData();

            $questionData['textTeaser'] = $textRepository->fetchByUid($question->getUid('teaser'))?->getText();
            $exportData['questions'][] = $questionData;


        }

        $filename = $this->plugin->getPage()->getTitle() . '.json';

        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode($exportData, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Persistence\Content\Repositories\Texts $textRepository
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository
     * @return \Frootbox\Admin\Controller\Response
     */
    public function importAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Content\Repositories\Texts $textRepository,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository,
    ): Response
    {
        $importData = json_decode($post->get('Json'), true);

        // Clean questions
        $result = $questionsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $result->map('delete');

        $connections['questions'] = [];

        foreach ($importData['questions'] as $questionData) {

            $oldId = $questionData['id'];

            $textTeaser = $questionData['textTeaser'];
            $textTeaser = str_replace('\n', '', $textTeaser);

            // Clean data
            unset($questionData['id'], $questionData['alias'], $questionData['textTeaser']);

            // Compose question
            $questionData['pluginId'] = $this->plugin->getId();
            $questionData['pageId'] = $this->plugin->getPageId();

            // Persist question
            $newQuestion = new \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Question($questionData);
            $newQuestion->addConfig([
                'oldId' => $oldId,
            ]);
            $questionsRepository->persist($newQuestion);
            $newQuestion->save();

            $connections['questions'][$oldId] = $newQuestion;

            // Persist texts
            $text = new \Frootbox\Persistence\Content\Text([
                'userId' => USER_ID,
                'uid' => $newQuestion->getUid('teaser'),
                'text' => $textTeaser,
            ]);

            $textRepository->persist($text);
        }

        return self::getResponse();
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }

    public function repairAction(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions $questionsRepository,
    ): Response
    {
        // Re-write "see also"
        $result = $questionsRepository->fetch([
            'where' => [

            ],
        ]);

        $result2 = $questionsRepository->fetch([
            'where' => [

            ],
        ]);

        foreach ($result as $question) {

            if (empty($question->getConfig('seeAlso'))) {
                continue;
            }

            $seeAlso = $question->getConfig('seeAlso');
            $question->unsetConfig('seeAlso');

            $newSeeAlso = [];

            foreach ($seeAlso as $oldId) {

                $result2->rewind();

                foreach ($result2 as $seeAlsoQuestion) {

                    if ($seeAlsoQuestion->getConfig('oldId') != $oldId) {
                        continue;
                    }

                    $newSeeAlso[$seeAlsoQuestion->getId()] = $seeAlsoQuestion->getId();

                    break;
                }
            }

            $question->addConfig([
                'seeAlso' => $newSeeAlso,
            ]);
            $question->save();
        }

        d("OK");

        return self::getResponse();
    }
}
