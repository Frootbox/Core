<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait Tags
{
    /**
     *
     */
    public function clearTags(): void
    {
        // Obtain tags
        $tags = $this->getTags();

        // Remove tags
        $tags->map('delete');
    }

    /**
     *
     */
    public function getTags(array $parameters = null): \Frootbox\Db\Result
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getModel(\Frootbox\Persistence\Repositories\Tags::class);

        $where = [
            'itemId' => $this->getId(),
            'itemClass' => get_class($this)
        ];

        if (!empty($parameters['ignore'])) {

            foreach ($parameters['ignore'] as $ignoredTag) {

                if (!empty($ignoredTag)) {
                    $where[] = new \Frootbox\Db\Conditions\NotEqual('tag', $ignoredTag);
                }
            }
        }

        // Fetch tags
        $tags = $tagsRepository->fetch([
            'where' => $where,
        ]);

        return $tags;
    }

    /**
     *
     */
    public function getTagsList(array $params = null): array
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getModel(\Frootbox\Persistence\Repositories\Tags::class);

        $result = $tagsRepository->fetch([
            'where' => [
                'itemId' => $this->getId(),
                'itemClass' => get_class($this)
            ]
        ]);

        $tags = [];

        foreach ($result as $tag) {

            if (!empty($params['unmatch'])) {

                if (preg_match('#' . $params['unmatch'] . '#', $tag->getTag())) {
                    continue;
                }
            }

            $tags[] = $tag->getTag();
        }

        return $tags;
    }

    /**
     *
     */
    public function hasTag(string $tag): bool
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getModel(\Frootbox\Persistence\Repositories\Tags::class);

        $result = $tagsRepository->fetchOne([
            'where' => [
                'itemId' => $this->getId(),
                'itemClass' => get_class($this),
                'tag' => $tag
            ]
        ]);

        return !empty($result);
    }

    /**
     *
     */
    public function setTags($tags): void
    {
        if (!is_array($tags)) {
            $tags = explode(',', $tags);
        }

        $tags = array_filter($tags);

        // Clear old tags
        $this->clearTags();

        // Obtain tags repository
        $tagsRepository = $this->getDb()->getModel(\Frootbox\Persistence\Repositories\Tags::class);

        foreach ($tags as $tag) {

            $newTag = new \Frootbox\Persistence\Tag([
                'tag' => $tag,
                'itemId' => $this->getId(),
                'itemClass' => get_class($this)
            ]);

            $tagsRepository->insert($newTag);
        }
    }
}
