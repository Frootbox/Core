<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Alias extends \Frootbox\Persistence\RowModels\ConfigurableRow
{
    protected $table = 'aliases';
    protected $model = Repositories\Aliases::class;

    protected $isIndexableForInternalSearch = true;

    /**
     * @return \Frootbox\Db\Row
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getItem()
    {
        $repo = $this->getDb()->getRepository($this->getItemModel());

        return $repo->fetchById($this->getItemId());
    }

    /**
     * @return string|null
     */
    public function getItemModel(): ?string
    {
        return $this->data['itemModel'];
    }

    /**
     * @return Page
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getPage(): \Frootbox\Persistence\Page
    {
        $pagesRepository = new \Frootbox\Persistence\Repositories\Pages($this->getDb());

        return $pagesRepository->fetchById($this->getPageId());
    }

    /**
     * @param AbstractPlugin $contentElement
     * @param \Frootbox\Http\Get $get
     * @return array
     */
    public function getPayloadData(
        \Frootbox\Persistence\AbstractPlugin $contentElement,
        \Frootbox\Http\Get $get
    ): array
    {
        $payload = new \Frootbox\Payload;
        $injectedGetData = [];

        try {

            $data = $get->getData();

            unset($data[session_name()]);

            // Validate payload
            if (!empty($data)) {
                $payload->validate($data);
            }

            $injectedGetData = $get->get('p') ?? [ ];
        }
        catch ( \Frootbox\Exceptions\InputInvalid $e ) {
            // d($e);
        }

        $payloadData = !empty($this->data['payload']) ? json_decode($this->data['payload'], true) : [ ];

        if (!empty($payloadData['plugin'][$contentElement->getId()])) {
            $payloadData = $payloadData['plugin'][$contentElement->getId()];
        }

        unset($payloadData['plugin']);

        if (!empty($injectedGetData[$contentElement->getId()])) {
            $payloadData = array_replace_recursive($payloadData ?? [], $injectedGetData[$contentElement->getId()]);
        }

        return $payloadData ?? [];
    }

    /**
     * @return \Frootbox\Db\Result
     */
    public function getSameTargetsByItem(): \Frootbox\Db\Result
    {
        return $this->getModel()->fetch([
            'where' => [
                'itemId' => $this->getItemId(),
                'itemModel' => $this->getItemModel(),
                'language' => $this->getLanguage(),
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->data['section'] ?? 'index';
    }

    /**
     * @return string
     */
    public function getUri():string
    {
        return SERVER_PATH_PROTOCOL . $this->getAlias();
    }

    /**
     * @return array
     */
    public function getVirtualDirectory(): array
    {
        return $this->data['virtualDirectory'] ?? [ ];
    }

    /**
     * @return bool
     */
    public function getIsIndexableForInternalSearch(): bool
    {
        return $this->isIndexableForInternalSearch;
    }

    /**
     * @param $aliasUri
     * @return void
     */
    public function setAlias($aliasUri): void
    {
        $this->data['alias'] = $aliasUri;
        $this->changed['alias'] = true;
    }

    /**
     * @param bool $isIndexableForInternalSearch
     * @return void
     */
    public function setIsIndexableForInternalSearch(bool $isIndexableForInternalSearch): void
    {
        $this->isIndexableForInternalSearch = $isIndexableForInternalSearch;
    }

    /**
     * @param $itemId
     * @return void
     */
    public function setItemId($itemId): void
    {
        $this->data['itemId'] = $itemId;
        $this->changed['itemId'] = true;
    }

    /**
     * @param string $itemModel
     * @return void
     */
    public function setItemModel(string $itemModel): void
    {
        $this->data['itemModel'] = $itemModel;
        $this->changed['itemModel'] = true;
    }
}
