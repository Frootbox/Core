<?php
/**
 * Class Alias
 */

namespace Frootbox\Persistence\Traits;

trait Alias
{
    use \Frootbox\Http\Traits\UrlSanitize;

    abstract protected function getDb ( ): \Frootbox\Db\Db;
    abstract protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias;


    /**
     *
     */
    protected function generateAlias()
    {
        // Update alias
        $alias = $this->getNewAlias();

        if (empty($alias)) {
            return null;
        }

        $alias->setItemModel($this->getModelClass());
        $alias->setItemId($this->getId());

        // Get target page
        if (empty($alias->getPageId())) {
            throw new \Frootbox\Exceptions\ParameterMissing('Missing parameter "pageId".');
        }

        return $alias;
    }

    /**
     *
     */
    protected function generateAliases()
    {
        // Update alias
        $sections = $this->getNewAliases();

        foreach ($sections as $section => $aliases) {

            foreach ($aliases as $alias) {

                $alias->setItemModel($this->getModelClass());
                $alias->setItemId($this->getId());
                $alias->setSection($section);

                // Get target page
                if (empty($alias->getPageId())) {
                    throw new \Frootbox\Exceptions\ParameterMissing('Missing parameter "pageId".');
                }
            }
        }

        return $aliases ?? [];
    }

    /**
     *
     */
    protected function generateAliasPayload(array $payload)
    {
        if (!empty($this->getPluginId())) {

            $payload = [
                'plugin' => [
                    $this->getPluginId() => $payload
                ]
            ];
        }

        return $payload;
    }

    public function getAliasObject(): ?\Frootbox\Persistence\Alias
    {
        $aliases = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Aliases::class);
        $row = $aliases->fetchOne([
            'where' => [
                'itemId' => $this->getId(),
                'itemModel' => $this->getModelClass(),
                'status' => 200,
            ]
        ]);

        return $row;
    }

    /**
     * Fetch all active and inactive aliases
     *
     * @return \Frootbox\Db\Result
     */
    public function getAliases(): \Frootbox\Db\Result
    {
        // Fetch aliases
        $aliases = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Aliases::class);
        $headers = $aliases->fetch([
            'where' => [
                'itemId' => $this->getId(),
                'itemModel' => $this->getModelClass()
            ]
        ]);

        return $headers;
    }

    /**
     *
     */
    public function getUri(array $options = null): ?string
    {

        // Set base path
        $base = (empty($options['absolute']) ? SERVER_PATH : SERVER_PATH_PROTOCOL);
        // $base = (empty($options['absolute']) ? '' : SERVER_PATH_PROTOCOL);

        // Append edit mode if currently editing
        if (defined('EDITING') or !empty($options['editing'])) {
            $base .= 'edit/';
        }

        if (!empty($options['ajax'])) {
            $base .= 'ajax/';
        }

        if (MULTI_LANGUAGE and $this instanceof \Frootbox\Persistence\Interfaces\MultipleAliases and !empty($this->data['aliases'])) {

            $aliases = json_decode($this->data['aliases'], true);

            if (!empty($aliases['index'][GLOBAL_LANGUAGE])) {
                $uri = SERVER_PATH_PROTOCOL . $aliases['index'][GLOBAL_LANGUAGE];
            }
            elseif (!empty($aliases['index'][DEFAULT_LANGUAGE])) {
                $uri = $aliases['index'][DEFAULT_LANGUAGE] . '?forceLanguage=' . GLOBAL_LANGUAGE;

            }
            else {
                $uri = $base . $this->getAlias();
            }
        }
        else {
            $uri = $base . $this->getAlias();
        }

        if (!empty($options['payload'])) {
            $payload = new \Frootbox\Payload;
            $payload->clear();
            $payload->addData($options['payload']);

            $uri .= '?' . http_build_query($payload->export());

            if (!empty($options['absolute'])) {
                $uri .= SID;
            }
        }
        elseif (!empty($options['absolute'])) {

            $uri .= '?' . SID;
        }

        if (!empty($options['fragment'])) {
            $uri .= '#' . $options['fragment'];
        }

        return trim($uri, '?');
    }

    /**
     *
     */
    public function getUriEdit(): string
    {
        return SERVER_PATH . 'edit/' . $this->getAlias();
    }

    /**
     *
     */
    protected function getSaveUri(
        string $aliasUri,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
        \Frootbox\Persistence\Alias $alias,
    ): string
    {
        $checkPayload = json_encode($alias->getPayload());

        for ($i = 0; $i <= 30; ++$i) {

            $checkUri = ($i == 0 ? $aliasUri : $aliasUri . '-' . $i);

            $result = $aliasesRepository->fetchOne([
                'where' => [
                    'alias' => $checkUri
                ],
            ]);

            // No alias found, uri is save
            if (empty($result)) {
                break;
            }

            // Old alias found, drop and re-use
            if ($result->getStatus() == 301) {
                $result->delete();
                break;
            }

            // If payload matches and item matches, alias can be used
            if ($alias->getUid() == $result->getUid())
            if ($result->getItemModel() == $alias->getItemModel() and $result->getItemId() == $alias->getItemId() and $result->getPayload() == $checkPayload) {
                break;
            }
        }

        if ($i == 30) {
            throw new \Frootbox\Exceptions\RuntimeError('Could not create alias.');
        }

        return $checkUri;
    }

    /**
     *
     */
    protected function getUriFromAlias(\Frootbox\Persistence\Alias $alias): string
    {
        try {

            // Fetch target page
            $pages = $this->db->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
            $targetPage = $pages->fetchById($alias->getPageId());

            $trace = $targetPage->getTrace();

            $trace->shift();

            $aliasDirectory = [];

            foreach ($trace as $page) {
                $aliasDirectory[] = $this->getStringUrlSanitized($page->getTitle($alias->getLanguage()), $alias->getLanguage());
            }

            foreach ($alias->getVirtualDirectory() as $folder) {

                if (empty($folder)) {
                    continue;
                }

                $aliasDirectory[] = $this->getStringUrlSanitized($folder);
            }

            $aliasUri = implode('/', $aliasDirectory);

            if (MULTI_LANGUAGE and !empty($alias->getLanguage()) and $alias->getLanguage() != DEFAULT_LANGUAGE) {
                $aliasUri = trim(substr($alias->getLanguage(), 0, 2) . '/' . $aliasUri, '/');
            }

            return $aliasUri;
        }
        catch ( \Frootbox\Exceptions\NotFound $e ) {
            return '#deletedPage';
        }
    }

    /**
     *
     */
    public function removeAlias(): void
    {
        // Get model
        $model = new \Frootbox\Persistence\Repositories\Aliases($this->getDb());
        $result = $model->fetch([
            'where' => [
                'alias' => $this->getAlias(),
            ],
        ]);

        $result->map('delete');


        if (!empty($this->data['aliases'])) {

            $data = json_decode($this->data['aliases'], true);

            foreach ($data as $sections => $aliases) {
                foreach ($aliases as $language => $alias) {

                    $result = $model->fetch([
                        'where' => [
                            'alias' => $this->getAlias(),
                        ],
                    ]);

                    $result->map('delete');
                }
            }
        }
    }

    /**
     *
     */
    public function save(array $options = null): \Frootbox\Db\Row
    {
        if (!empty($options['skipAlias'])) {
            return parent::save();
        }

        $db = $this->getDb();

        $db->transactionStart();

        // Save row
        parent::save();

        if (MULTI_LANGUAGE and $this instanceof \Frootbox\Persistence\Interfaces\MultipleAliases) {
            $aliases = $this->generateAliases();
        }
        else {

            // Generate alias
            $alias = $this->generateAlias();

            if ($alias !== null and empty($alias->getLanguage())) {

                $alias->setDb($this->getDb());
                $alias->setLanguage($alias->getPage()->getLanguage());
            }

            $aliases = $alias ? [ $alias ] : null;
        }

        $aliasesRepository = $db->getRepository(\Frootbox\Persistence\Repositories\Aliases::class);

        if (empty($aliases)) {

            $result = $aliasesRepository->fetch([
                'where' => [
                    'itemModel' => $this->getModelClass(),
                    'itemId' => $this->getId(),
                ],
            ]);

            $result->map('delete');

            $this->setAlias(null);
            $this->setAliases(null);

            // Save row
            parent::save();

            $db->transactionCommit();

            return $this;
        }

        foreach ($aliases as $alias) {

            if (empty($options['forceAlias'])) {
                $aliasUri = $this->getUriFromAlias($alias);
            }
            else {
                $aliasUri = $options['forceAlias'];
            }

            if (empty($alias->getUid())) {
                throw new \Exception("Alias UID missing for model " . $alias->getItemModel());
            }

            // Check if alias exists
            $checkAlias = $aliasesRepository->fetchOne([
                'where' => [
                    'alias' => $aliasUri,
                    'language' => $alias->getLanguage(),
                ],
            ]);

            if ($checkAlias) {

                if (empty($checkAlias->getUid())) {

                    // Occupy check alias
                    $checkAlias->setUid($alias->getUid());
                    $checkAlias->save();
                }

                if ($checkAlias->getUid() == $alias->getUid()) {

                    $checkAlias->setAlias($aliasUri);
                    $checkAlias->setPayload(json_encode($alias->getPayload()));
                    $checkAlias->save();

                    $this->setAlias($checkAlias);
                }
                else {

                    // Re-use old 301 alias
                    if ($checkAlias->getStatus() == 301) {

                        // Remove old alias
                        $checkAlias->delete();

                        // Persist new alias
                        $alias->setAlias($aliasUri);
                        $alias = $aliasesRepository->persist($alias);

                        $this->setAlias($alias);
                    }
                    else {

                        // Check of actual alias can stay
                        $actualAlias = $aliasesRepository->fetchOne([
                            'where' => [
                                'uid' => $alias->getUid(),
                                'status' => 200,
                            ],
                        ]);

                        if (empty($actualAlias)) {

                            $saveAliasUri = $this->getSaveUri($aliasUri, $aliasesRepository, $alias);
                            $alias->setAlias($saveAliasUri);
                            $alias = $aliasesRepository->persist($alias);

                            $this->setAlias($alias);

                            $result = $aliasesRepository->fetch([
                                'where' => [
                                    'uid' => $alias->getUid(),
                                    'language' => $alias->getLanguage(),
                                    new \Frootbox\Db\Conditions\NotEqual('id', $alias->getId()),
                                ]
                            ]);

                            foreach ($result as $oldAlias) {
                                $oldAlias->setStatus(301);
                                $oldAlias->save();
                            }
                        }
                        else {

                            if (preg_match('#^' . $checkAlias->getAlias() . '\-([0-9]+)$#', $actualAlias->getAlias(), $match)) {
                                $this->setAlias($actualAlias);
                            }
                            else {

                                $saveAliasUri = $this->getSaveUri($aliasUri, $aliasesRepository, $alias);
                                $alias->setAlias($saveAliasUri);
                                $alias = $aliasesRepository->persist($alias);
                                $this->setAlias($alias);

                                $result = $aliasesRepository->fetch([
                                    'where' => [
                                        'uid' => $alias->getUid(),
                                        'language' => $alias->getLanguage(),
                                        new \Frootbox\Db\Conditions\NotEqual('id', $alias->getId()),
                                    ]
                                ]);

                                foreach ($result as $oldAlias) {
                                    $oldAlias->setStatus(301);
                                    $oldAlias->save();
                                }
                            }
                        }
                    }
                }
            }
            else {

                // Persist new alias
                $alias->setAlias($aliasUri);
                $alias = $aliasesRepository->persist($alias);

                $result = $aliasesRepository->fetch([
                    'where' => [
                        'uid' => $alias->getUid(),
                        'language' => $alias->getLanguage(),
                        new \Frootbox\Db\Conditions\NotEqual('id', $alias->getId()),
                    ]
                ]);

                foreach ($result as $oldAlias) {
                    $oldAlias->setStatus(301);
                    $oldAlias->save();
                }

                $this->setAlias($alias);
            }


            parent::save();

            if ($this->hasColumn('visibility')) {

                if (!empty($alias) and ((is_int($this->getVisibility()) and $this->getVisibility() < 2) or $this->getVisibility() == 'Moderated' or $this->getVisibility() == 'Locked')) {

                    $nalias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $this->getAlias($alias->getSection(), $alias->getLanguage()),
                        ]
                    ]);

                    if (!empty($nalias) and $nalias->hasColumn('visibility')) {
                        $nalias->setVisibility(0);
                        $nalias->save();
                    }
                } elseif (!empty($alias) and ($this->getVisibility() == 'Public' or $this->getVisibility() == 'Hidden' or $this->getVisibility() === 2)) {

                    $nalias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $this->getAlias($alias->getSection(), $alias->getLanguage()),
                        ]
                    ]);

                    if (!empty($nalias) and $nalias->hasColumn('visibility')) {
                        $nalias->setVisibility(2);
                        $nalias->save();
                    }
                } else {

                    $nalias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $this->getAlias($alias->getSection(), $alias->getLanguage()),
                        ]
                    ]);

                    if (!empty($nalias)) {
                        // $nalias->setVisibility($this->getVisibility() > 1 ? 1 : 0);
                        $nalias->setVisibility($this->getVisibility());
                        $nalias->save();
                    }
                }
            }

            continue;
            // Update existing alias
            if (
                !empty($newAlias = $this->getAlias($alias->getSection(), $alias->getLanguage())) or
                ($this instanceof \Frootbox\Persistence\Page and $this->getParentId() == 0)
            )
            {
                if ($aliasUri != $newAlias) {

                    d('OLD ' . $aliasUri . ' - NEW ' . $newAlias);
                    d("UPDATE ALIAS URI");

                    $saveAliasUri = $this->getSaveUri($aliasUri, $aliasesRepository, $alias);

                    // Mark old alias as dead
                    $result = $aliasesRepository->fetch([
                        'where' => [
                            'alias' => $newAlias,
                            'language' => $alias->getLanguage(),
                        ],
                        'limit' => 1
                    ]);

                    if ($result->getCount() > 0) {

                        // Mark old aliases moved permanently
                        foreach ($result as $oldAlias) {

                            // Copy config from old aliasses
                            if (!empty($oldAlias->getConfig('seo'))) {

                                $alias->addConfig([
                                    'seo' => $oldAlias->getConfig('seo')
                                ]);
                            }

                            // Remove alais or mark old alias as redirected
                            $aliasCreateDate = new \DateTime($oldAlias->getDate());
                            $diff = $aliasCreateDate->diff(new \DateTime('now'));

                            if ($diff->d >= 1) {
                                $oldAlias->setStatus(301);
                                $oldAlias->addConfig([
                                    'target' => $saveAliasUri
                                ]);

                                $oldAlias->save();
                            }
                            else {
                                $oldAlias->delete();
                            }
                        }
                    }

                    // Insert new alias
                    $alias->setAlias($saveAliasUri);
                    $alias = $aliasesRepository->insert($alias);

                    $this->setAlias($alias);

                    parent::save();
                }
                // Check if payload of old alias needs to be updated
                else {

                    $result = $aliasesRepository->fetch([
                        'where' => [
                            'alias' => (!empty($newAlias) ? $newAlias : ''),
                            'language' => $alias->getLanguage(),
                        ],
                        'limit' => 1
                    ]);

                    $oldAlias = $result->current();

                    if (!$oldAlias) {

                        // Insert new alias
                        $saveAliasUri = $this->getSaveUri($aliasUri, $aliasesRepository, $alias);


                        $alias->setAlias($saveAliasUri);
                        $alias = $aliasesRepository->insert($alias);

                        $this->setAlias($alias);

                        parent::save();
                    }
                    else {

                        $xdata = $alias->getData();

                        $oldAlias->setPayload(json_encode($alias->getPayload()));

                        if (!empty($xdata['config'])) {
                            $oldAlias->addConfig($xdata['config']);

                        }
                        else {
                            $oldAlias->unsetConfig('target');
                        }

                        $oldAlias->setStatus($alias->getStatus() ?? 200);
                        $oldAlias->setLanguage($alias->getLanguage());

                        $oldAlias->save();


                        $alias->setAlias($aliasUri);
                        $this->setAlias($alias);

                        parent::save();
                    }
                }

            } // Create new alias
            else {

                $saveAliasUri = $this->getSaveUri($aliasUri, $aliasesRepository, $alias);

                $alias->setAlias($saveAliasUri);

                $alias->unset('virtualDirectory');

                $alias = $aliasesRepository->insert($alias);

                $alias->unset('virtualDirectory');

                // Set alias
                $this->setAlias($alias);

                parent::save();
            }


            if ($this->hasColumn('visibility')) {

                if (!empty($alias) and ((is_int($this->getVisibility()) and $this->getVisibility() < 2) or $this->getVisibility() == 'Moderated' or $this->getVisibility() == 'Locked')) {

                    $nalias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $this->getAlias($alias->getSection(), $alias->getLanguage()),
                        ]
                    ]);

                    if (!empty($nalias) and $nalias->hasColumn('visibility')) {
                        $nalias->setVisibility(0);
                        $nalias->save();
                    }
                } elseif (!empty($alias) and ($this->getVisibility() == 'Public' or $this->getVisibility() == 'Hidden' or $this->getVisibility() === 2)) {

                    $nalias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $this->getAlias($alias->getSection(), $alias->getLanguage()),
                        ]
                    ]);

                    if (!empty($nalias) and $nalias->hasColumn('visibility')) {
                        $nalias->setVisibility(2);
                        $nalias->save();
                    }
                } else {

                    $nalias = $aliasesRepository->fetchOne([
                        'where' => [
                            'alias' => $this->getAlias($alias->getSection(), $alias->getLanguage()),
                        ]
                    ]);

                    if (!empty($nalias)) {

                        // $nalias->setVisibility($this->getVisibility() > 1 ? 1 : 0);
                        $nalias->setVisibility($this->getVisibility());
                        $nalias->save();
                    }
                }
            }

        }


        $db->transactionCommit();


        return $this;
    }

    /**
     *
     */
    public function setAlias(\Frootbox\Persistence\Alias $alias = null): void
    {
        if ($alias === null) {

            $this->setAliases(null);
            parent::setAlias(null);
        }
        elseif (MULTI_LANGUAGE and $this instanceof \Frootbox\Persistence\Interfaces\MultipleAliases) {

            $aliases = !empty($this->data['aliases']) ? json_decode($this->data['aliases'], true) : [];
            $aliases[$alias->getSection()][$alias->getLanguage()] = $alias->getAlias();

            $this->setAliases(json_encode($aliases));
        }
        else {

            $this->data['alias'] = $alias->getAlias();
            $this->changed['alias'] = true;
        }
    }
}
