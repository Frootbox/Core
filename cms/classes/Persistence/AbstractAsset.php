<?php 
/**
 * 
 */

namespace Frootbox\Persistence;

abstract class AbstractAsset extends \Frootbox\Persistence\AbstractRow
{
    use Traits\Alias;
    use Traits\Uid;
    use Traits\Visibility;
    use Traits\Config;

    protected $table = 'assets';

    /**
     * @param $section
     * @param $language
     * @param array|null $options
     * @return string|null
     */
    public function getAlias(string $section = 'index', $language = null, array $options = null): ?string
    {
        $aliases = !empty($this->data['aliases']) ? json_decode($this->data['aliases'], true) : [];

        if ($language === null) {
            $language = GLOBAL_LANGUAGE;
        }

        if (MULTI_LANGUAGE and !empty($aliases[$section][$language])) {
            return $aliases[$section][$language];
        }

        if ((MULTI_LANGUAGE or empty($this->data['alias'])) and !empty($aliases[$section][DEFAULT_LANGUAGE])) {
            $alias = $aliases[$section][DEFAULT_LANGUAGE];
        }
        else {
            $alias = $this->data['alias'] ?? null;
        }

        if (empty($options['skipForceLanguage']) and MULTI_LANGUAGE and GLOBAL_LANGUAGE != DEFAULT_LANGUAGE) {
            $alias .= '?forceLanguage=' . GLOBAL_LANGUAGE;
        }

        return $alias;
    }

    /**
     *
     */
    public function getPage(): ?\Frootbox\Persistence\Page
    {
        if (empty($this->getPageId())) {
            return null;
        }

        $pagesRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
        $page = $pagesRepository->fetchById($this->getPageId());

        return $page;
    }

    /**
     *
     */
    public function getPlugin(): ?\Frootbox\Persistence\AbstractPlugin
    {
        if (empty($this->getPluginId())) {
            return null;
        }

        $pluginsRepository = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $plugin = $pluginsRepository->fetchById($this->getPluginId());

        return $plugin;
    }

    /**
     *
     */
    public function getSiblingNext(array $parameters = null): ?self
    {

        if (empty($parameters['order'])) {
            $parameters['order'] = 'Manual';
        }

        switch($parameters['order']) {

            default:
            case 'Manual':
                $orderBy = 'orderId DESC';
                $orderCondition = new \Frootbox\Db\Conditions\Less('orderId', $this->getOrderId());
                break;

            case 'DateDesc':
                $orderBy = 'date DESC';
                $orderCondition = new \Frootbox\Db\Conditions\Less('date', $this->getDate());
                break;

        }

        $repository = $this->getRepository();

        $row = $repository->fetchOne([
            'where' => [
                'pluginId' => $this->getPluginId(),
                $orderCondition,
                new \Frootbox\Db\Conditions\NotEqual('id', $this->getId()),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
            'order' => [ $orderBy ],
        ]);

        return $row;
    }

    /**
     *
     */
    public function getSiblings(array $parameters = null): \Frootbox\Db\Result
    {
        if (empty($parameters['order'])) {
            $parameters['order'] = 'Manual';
        }

        switch($parameters['order']) {

            default:
            case 'Manual':
                $orderBy = 'orderId DESC';
                break;

            case 'DateDesc':
                $orderBy = 'date DESC';
                break;

        }

        $repository = $this->getRepository();

        $result = $repository->fetch([
            'where' => [
                'pluginId' => $this->getPluginId(),
                new \Frootbox\Db\Conditions\NotEqual('id', $this->getId()),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
            'order' => [ $orderBy ],
        ]);

        return $result;
    }


    /**
     *
     */
    public function getTitle($language = null): ?string
    {
        if (!MULTI_LANGUAGE) {
            return parent::getTitle();
        }

        if ($language === null) {
            $language = GLOBAL_LANGUAGE;
        }

        return (!empty($this->getConfig('titles')[$language]) ? $this->getConfig('titles')[$language] : parent::getTitle());
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

    /**
     *
     */
    public function getUser(): ?\Frootbox\Persistence\User
    {
        if (empty($this->getUserId())) {
            return null;
        }

        // Fetch user
        $userRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Users::class);

        return $userRepository->fetchById($this->getUserId());
    }

    /**
     * Check if asset is visible to user
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return ($this->getVisibility() >= (IS_EDITOR ? 1 : 2));
    }
}
