<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References;

use Frootbox\View\Response;
use Frootbox\View\ResponseRedirect;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    protected $publicActions = [
        'index',
        'showReference',
        'request',
    ];

    protected $isContainerPlugin = true;
    protected $icon = 'fas fa-images';

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getOrdering(): string
    {
        return $this->getConfig('order') ?? 'Manual';
    }

    /**
     *
     */
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor
    ): void
    {
        $cloningMachine = $container->get(\Frootbox\CloningMachine::class);

        $repository = $container->get(\Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References::class);
        $rows = $repository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId(),
            ],
        ]);

        foreach ($rows as $row) {

            $newRow = $row->duplicate();
            $newRow->setPluginId($this->getId());
            $newRow->setPageId($this->getPage()->getId());
            $newRow->setAlias(null);
            $newRow->save();

            $cloningMachine->cloneContentsForElement($newRow, $row->getUidBase());
        }
    }

    /**
     * Cleanup before deleting plugin
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): void
    {
        // Fetch events
        $result = $referencesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $result->map('delete');
    }

    /**
     *
     */
    public function getAvailableTags(array $parameters = null): \Frootbox\Db\Result
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Tags::class);

        $payload = [
            'className' => \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Reference::class,
        ];

        $sql = 'SELECT 
            COUNT(t.id) as count, 
            t.tag as tag
        FROM
            tags t,
            assets a
        WHERE
            t.itemClass = :className AND
            a.className = t.itemClass AND
            a.id = t.itemId AND
            a.visibility >= ' . (IS_EDITOR ? 1 : 2) . '       
            ';

        if (!empty($parameters['exclude'])) {

            $sql .= ' AND t.tag NOT IN ( ';
            $comma = '';

            foreach ($parameters['exclude'] as $index => $tag) {
                $sql .= $comma . ':tag_' . $index;
                $comma = ', ';

                $payload['tag_' . $index] = $tag;
            }

            $sql .= ' ) ';
        }

        $sql .= ' GROUP BY
            t.tag
        ORDER BY        
            t.tag DESC';

        $result = $tagsRepository->fetchByQuery($sql, $payload);

        return $result;
    }

    /**
     *
     */
    public function getAvailableYears()
    {
        $sql = 'SELECT 
            COUNT(id) as count, 
            DATE_FORMAT(dateStart, "%Y") as year
        FROM
            assets
        WHERE
            className = :class AND
            dateStart IS NOT NULL AND
            visibility >= ' . (IS_EDITOR ? 1 : 2) . '
        GROUP BY
            year
        ORDER BY
            year DESC';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':class', \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Reference::class);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     *
     */
    public function getFields(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch fields
        $result = $fieldsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getReferences(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        int $limit = 1024,
        array $parameters = null,
    ): \Frootbox\Db\Result
    {
        if (!empty($parameters['order'])) {
            $order = $parameters['order'];
        }
        else {

            switch ($this->getConfig('order')) {
                case 'DateDesc':
                    $order = [ 'date DESC' ];
                    break;

                case 'DateAsc':
                    $order = [ 'date ASC' ];
                    break;

                case 'Manual':
                default:
                    $order = [ 'orderId DESC' ];
                    break;
            }
        }

        // Fetch references
        $references = $referencesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', IS_EDITOR ? 1 : 2),
            ],
            'order' => $order,
            'limit' => $parameters['limit'] ?? $limit,
        ]);

        return $references;
    }

    /**
     *
     */
    public function getReferencesByTag(
        string $tag,
        array $parameters = null,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch references
        $references = $referencesRepository->fetchByTag($tag, $parameters);

        return $references;
    }

    /**
     *
     */
    public function getReferencesByTags(
        array $tags,
        array $parameters = null,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch references
        $references = $referencesRepository->fetchByTags($tags, $parameters);

        return $references;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {

    }

    /**
     *
     */
    public function jumpNextAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        $minVisibility = $session->isLoggedIn() ? 1 : 2;

        // Fetch reference
        $reference = $referencesRepository->fetchOne([
            'where' => [
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', $minVisibility),
                new \Frootbox\Db\Conditions\Less('orderId', $this->getAttribute('orderId'))
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        if (empty($reference)) {

            $reference = $referencesRepository->fetchOne([
                'order' => ['orderId DESC']
            ]);
        }

        return new ResponseRedirect($reference->getUri());
    }

    /**
     *
     */
    public function requestAction(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): Response
    {
        // Fetch reference
        $reference = $referencesRepository->fetchById($this->getAttribute('referenceId'));

        if ($reference->getVisibility() < (IS_EDITOR ? 1 : 2)) {
            throw new Exception('Nicht gefunden.');
        }

        return new Response([
            'reference' => $reference,
        ]);
    }

    /**
     *
     */
    public function showReferenceAction(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): Response
    {
        // Fetch reference
        $reference = $referencesRepository->fetchById($this->getAttribute('referenceId'));
        $reference->validateVisibility();

        return new Response([
            'reference' => $reference,
        ]);
    }
}
