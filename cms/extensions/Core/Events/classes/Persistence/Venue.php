<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Persistence;

class Venue extends \Frootbox\Persistence\AbstractLocation implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Alias;

    protected $model = Repositories\Venues::class;

    /**
     * Generate addresses alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                'veranstaltungsorte',
                $this->getTitle()
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showVenue',
                'venueId' => $this->getId()
            ])
        ]);
    }

    /**
     *
     */
    public function getEvents(): \Frootbox\Db\Result
    {
        $sql = 'SELECT
            e.*,
            l.id as locationId,
            l.title as locationTitle,
            l.street as locationStreet,
            l.streetNumber as locationStreetNumber,
            l.zipcode as locationZipcode,
            l.city as locationCity,
            l.phone as locationPhone,
            l.email as locationEmail,
            l.config as locationConfig,
            l.alias as locationAlias
        FROM
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND  
            e.pluginId = ' . $this->getPluginId() . ' AND
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" AND            
            (
                e.dateStart >= "' . date('Y-m-d H:i:s') . '" OR
                (
                    e.dateStart <= "' . date('Y-m-d H:i:s') . '" AND
                    e.dateEnd >= "' . date('Y-m-d H:i:s') . '"
                )
            )            
        ORDER BY
            e.dateStart ASC';

        $eventRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Persistence\Repositories\Events::class);

        return $eventRepository->fetchByQuery($sql);
    }

    /**
     * @return array
     */
    public function getNewAliases(): array
    {

    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'];
    }

    /**
     *
     */
    public function getTitle($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? parent::getTitle();
    }

    /**
     *
     */
    public function getTitleWithoutFallback($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? null;
    }
}