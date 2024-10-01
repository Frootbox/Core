<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Persistence;

class Video extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\Videos::class;

    /**
     *
     */
    public function getEmbedUri(): string
    {
        return 'https://www.youtube.com/embed/' . $this->getVideoId() . '/?rel=0';
    }

    /**
     *
     */
    public function getVideoId(): ?string
    {
        $url = $this->getConfig('url');

        $videoId = null;

        if (preg_match('#https://www.youtube.com/watch\?v=(.*?)$#', $url, $match)) {
            $videoId = $match[1];
        }
        elseif (preg_match('#https://youtu.be/(.*?)$#', $url, $match)) {
            $videoId = $match[1];
        }
        elseif (preg_match('#https://www.youtube.com/embed/(.*?)$#', $url, $match)) {
            $videoId = $match[1];
        }


        return $videoId;
    }

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showVideo',
                'videoId' => $this->getId()
            ])
        ]);
    }

    /**
     *
     */
    public function getThumbnail(): string
    {
        return 'https://img.youtube.com/vi/' . $this->getVideoId() . '/hqdefault.jpg';
    }
}
