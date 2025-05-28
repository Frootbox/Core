<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Blocks\Form1;

class Block extends \Frootbox\Persistence\Content\Blocks\Block
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
     * @return array|null[]
     */
    public function onBeforeRendering(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): array
    {
        if (empty($this->getConfig('formId'))) {
            return [
                'form' => null,
            ];
        }

        try {
            // Fetch form
            $form = $formsRepository->fetchById($this->getConfig('formId'));
        }
        catch (\Exception $e) {
            $form = null;
        }

        return [
            'form' => $form,
        ];
    }
}
