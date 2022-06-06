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
     *
     */
    public function getItem()
    {
        $repo = $this->getDb()->getRepository($this->getItemModel());

        return $repo->fetchById($this->getItemId());
    }

    /**
     *
     */
    public function getItemModel(): ?string
    {
        return $this->data['itemModel'];
    }

    /**
     *
     */
    public function getPage(): \Frootbox\Persistence\Page
    {
        $pagesRepository = new \Frootbox\Persistence\Repositories\Pages($this->getDb());

        return $pagesRepository->fetchById($this->getPageId());
    }

    /**
     *
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
     *
     */
    public function getUri():string
    {
        return SERVER_PATH_PROTOCOL . $this->getAlias();
    }

    /**
     *
     */
    public function getVirtualDirectory(): array
    {
        return $this->data['virtualDirectory'] ?? [ ];
    }

    /**
     *
     */
    public function getIsIndexableForInternalSearch(): bool
    {
        return $this->isIndexableForInternalSearch;
    }

    /**
     *
     */
    public function setAlias($aliasUri): void
    {
        $this->data['alias'] = $aliasUri;
        $this->changed['alias'] = true;
    }

    /**
     *
     */
    public function setIsIndexableForInternalSearch(bool $isIndexableForInternalSearch): void
    {
        $this->isIndexableForInternalSearch = $isIndexableForInternalSearch;
    }

    /**
     *
     */
    public function setItemId($itemId): void
    {
        $this->data['itemId'] = $itemId;
        $this->changed['itemId'] = true;
    }

    /**
     *
     */
    public function setItemModel(string $itemModel): void
    {
        $this->data['itemModel'] = $itemModel;
        $this->changed['itemModel'] = true;
    }
}
