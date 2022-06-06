<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\FAQ\Persistence;

class Question extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Http\Traits\UrlSanitize;

    protected $model = Repositories\Questions::class;

    /**
     * Generate questions alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noQuestionsDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showQuestion',
                'questionId' => $this->getId()
            ])
        ]);
    }

    /**
     *
     */
    public function getSeeAlso(): array
    {
        $list = [];

        if (empty($this->getConfig('seeAlso'))) {
            return $list;
        }

        $questionsRepository = $this->getModel();

        foreach ($this->getConfig('seeAlso') as $questionId) {
            $list[] = $questionsRepository->fetchById($questionId);
        }

        return $list;
    }
}
