<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Forms\Persistence;

class Log extends \Frootbox\Persistence\AbstractLog
{
    use \Frootbox\Persistence\Traits\Uid;

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
            $list[] = $filesRepository->fetchById($fileId);
        }

        return $list;
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
                elseif (preg_match('#e\-mail#i', $field['title'])) {
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
