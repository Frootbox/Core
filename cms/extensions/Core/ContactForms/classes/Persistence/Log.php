<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence;

class Log extends \Frootbox\Persistence\AbstractLog
{
    use \Frootbox\Persistence\Traits\Uid;

    /**
     *
     */
    public function delete()
    {
        // Cleanup files
        foreach ($this->logData['formData'] as $section) {

            foreach ($section['fields'] as $fieldData) {

                if ($fieldData['type'] == 'Files') {

                    $files = $this->getFiles($fieldData);

                    foreach ($files as $file) {
                        $file->delete();
                    }
                }
            }
        }

        parent::delete();
    }

    /**
     *
     */
    public function getFiles(array $fieldData): array
    {
        if (empty($fieldData['value'])) {
            return [];
        }

        $filesRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Files::class);

        $list = [];

        foreach ($fieldData['value'] as $fileId) {

            if (is_array($fileId)) {
                $fileId = $fileId['id'];
            }

            try {
                $list[] = $filesRepository->fetchById($fileId);
            }
            catch ( \Exception $e ) {

            }
        }

        return $list;
    }

    /**
     *
     */
    public function getForm(): \Frootbox\Ext\Core\ContactForms\Persistence\Form
    {
        // Fetch form
        $formsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms::class);
        $form = $formsRepository->fetchById($this->getParentId());

        return $form;
    }

    /**
     *
     */
    public function getSender(): ?array
    {
        if (empty($this->logData['formData'])) {
            return null;
        }

        $sender = [];

        foreach ($this->logData['formData'] as $sectionData) {

            foreach ($sectionData['fields'] as $field) {

                if (preg_match('#name#i', $field['title'])) {
                    $sender['name'] = $field['value'];
                }
                elseif ($field['type'] == 'Email' or preg_match('#e\-mail#i', $field['title'])) {
                    $sender['email'] = $field['value'];
                }
            }
        }

        if (empty($sender)) {
            return null;
        }

        return $sender;
    }
}
