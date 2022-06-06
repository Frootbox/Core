<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields;

abstract class AbstractField extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Fields::class;
    protected $value = null;
    protected $isSkippedInLog = false;

    /**
     *
     */
    public function getGroup(): \Frootbox\Ext\Core\ContactForms\Persistence\Group
    {
        // Fetch group
        $groupsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups::class);
        $group = $groupsRepository->fetchById($this->getParentId());

        return $group;
    }

    /**
     * @deprecated
     */
    public function getMandatory(): bool
    {
        return !empty($this->getConfig('mandatory'));
    }

    /**
     *
     */
    abstract public function getPath(): string;

    /**
     *
     */
    public function getType(): string
    {
        return $this->getConfig('type');
    }

    /**
     *
     */
    public function getTypeTitle(): ?string
    {
        $languageFile = $this->getPath() . '/resources/private/language/de-DE.php';

        $data = require($languageFile);

        return $data['Field.Title'] ?? $this->getConfig('type');
    }

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getValue(): string
    {
        return (string) $this->value;
    }

    /**
     *
     */
    public function getValueDisplay(): string
    {
        return (string) $this->value;
    }

    /**
     *
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     *
     */
    public function isRequired(): bool
    {
        return !empty($this->getConfig('mandatory'));
    }

    /**
     *
     */
    public function isSkippedInLog(): bool
    {
        return $this->isSkippedInLog;
    }

    /**
     *
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * Update field form post data
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void{

    }
}
