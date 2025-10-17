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
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): void
    {
        foreach ($formsRepository->fetch() as $form) {

            // Fetch logs
            $result = $logsRepository->fetch([
                'where' => [
                    'parentId' => $form->getId(),
                ],
                'order' => [
                    'date DESC',
                ]
            ]);


            foreach ($result as $log) {

                $groups = $log->getLogData()['formData'];

                foreach ($groups as $group) {

                    foreach ($group['fields'] as $field) {

                        if ($field['type'] != 'Files') {
                            continue;
                        }

                        $files = $log->getFiles($field);

                        foreach ($files as $file) {

                            $file->setIsPrivate(1);
                            $file->save();
                        }
                    }
                }
            }
        }




    }

}
