<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Persistence;

class Contact extends \Frootbox\Persistence\AbstractPerson
{
    use \Frootbox\Persistence\Traits\Alias;

    protected $model = Repositories\Contacts::class;

    /**
     *
     */
    public function getConnConfig(): ?array
    {
        return json_decode($this->data['connConfig'], true);
    }

    /**
     * Generate contacts alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noPersonsDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getFirstName() . ' ' . $this->getLastName(),
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showContact',
                'contactId' => $this->getId(),
            ]),
        ]);
    }
}
