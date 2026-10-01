<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert Sichtbarkeit von Dateien.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): void
    {
        foreach ($formsRepository->fetch() as $form) {

            // Uploads belong to the form even if a submission's log data is damaged.
            $files = $filesRepository->fetch([
                'where' => [
                    'uid' => $form->getUid('fileuploads'),
                ],
            ]);

            foreach ($files as $file) {

                $file->setIsPrivate(1);
                $file->save();
            }
        }
    }
}
