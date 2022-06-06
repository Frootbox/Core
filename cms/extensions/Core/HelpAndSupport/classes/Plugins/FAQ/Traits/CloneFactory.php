<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Traits;

trait CloneFactory
{
    /**
     *
     */
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor
    ): void
    {
        $cloningMachine = $container->get(\Frootbox\CloningMachine::class);

        // Fetch questions
        $questionsRepository = $container->get(\Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence\Repositories\Questions::class);
        $result = $questionsRepository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId()
            ]
        ]);

        foreach ($result as $question) {

            $newQuestion = clone $question;
            $newQuestion->setPageId($this->getPageId());
            $newQuestion->setPluginId($this->getId());

            $newQuestion = $questionsRepository->insert($newQuestion);

            $cloningMachine->cloneContentsForElement($newQuestion, $question->getUidBase());
        }
    }
}