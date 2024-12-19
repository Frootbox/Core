<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence;

class Teaser extends \Frootbox\Persistence\AbstractAsset implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    protected $model = Repositories\Teasers::class;

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);
        return $aliases['index'] ?? [];
    }

    /**
     *
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (
            !empty($this->config['redirect']['target']) or
            !empty($this->config['redirect']['intern']) or
            !empty($this->config['redirect']['pageId']) or
            !empty($this->config['linkageDeactivated']) or
            !empty($this->config['noSelfPage'])
        )
        {
            // Remove unnecessery alias
            if (!empty($this->getAlias())) {

                $this->removeAlias();

                $this->setAlias(null);
                $this->save();
            }

            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showTeaser',
                'teaserId' => $this->getId()
            ])
        ]);
    }

    /**
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (
            !empty($this->config['redirect']['target']) or
            !empty($this->config['redirect']['intern']) or
            !empty($this->config['redirect']['pageId']) or
            !empty($this->config['linkageDeactivated']) or
            !empty($this->config['noSelfPage'])
        )
        {
            // Remove unnecessery alias
            if (!empty($this->getAlias())) {

                $this->removeAlias();

                $this->setAlias(null);
                $this->save();
            }

            return [];
        }

        if (!empty($this->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($this->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                $list['index'][] = new \Frootbox\Persistence\Alias([
                    'language' => $language,
                    'pageId' => $this->getPageId(),
                    'virtualDirectory' => [
                        $title,
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showTeaser',
                        'teaserId' => $this->getId()
                    ]),
                ]);
            }

            return $list;
        }
        else {

            return [
                'index' => [ new \Frootbox\Persistence\Alias([
                    'pageId' => $this->getPageId(),
                    'language' => $this->getLanguage() ?? GLOBAL_LANGUAGE,
                    'virtualDirectory' => [
                        $this->getTitle()
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showTeaser',
                        'teaserId' => $this->getId()
                    ]),
                ]) ],
            ];
        }
    }

    /**
     * @deprecated
     */
    public function getUrl(): string
    {
        return $this->getUri();
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->config['redirect']['target'])) {
            return $this->config['redirect']['target'];
        }

        if (!empty($this->config['redirect']['pageId'])) {
            return 'fbx://page:' . $this->config['redirect']['pageId'];
        }

        if (!empty($this->config['redirect']['email'])) {

            $url = 'mailto:' . $this->config['redirect']['email'];

            if (!empty($this->config['redirect']['emailSubject'])) {
                $url .= '?subject=' . $this->config['redirect']['emailSubject'];
            }

            return $url;
        }

        if (!empty($this->config['redirect']['article']['id'])) {

            // Fetch article
            $articleRepository = $this->db->getRepository(\Frootbox\Ext\Core\News\Persistence\Repositories\Articles::class);
            $article = $articleRepository->fetchById($this->config['redirect']['article']['id']);

            return $article->getUri();
        }

        if (!empty($this->config['redirect']['intern'])) {

            $base = SERVER_PATH;

            if (defined('EDITING')) {
                $base .= 'edit/';
            }

            return $base . $this->config['redirect']['intern'];
        }

        if (!empty($this->getAlias(options: [ 'skipForceLanguage' => true ]))) {

            return parent::getUri($options);
        }

        return '#';
    }

    /**
     *
     */
    public function hasCustomTarget(): bool
    {
        return (!empty($this->config['redirect']['intern']));
    }

    /**
     * @return bool
     */
    public function hasUri(): bool
    {
        $uri = $this->getUri();

        return !empty($uri) and $uri != '#';
    }

    /**
     *
     */
    public function isLinkDeactivated(): bool
    {
        return (!empty($this->getConfig('linkageDeactivated')) or $this->getUri() == '#');
    }
}
