<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    protected $model = Repositories\Categories::class;
    
    use \Frootbox\Persistence\Traits\DummyImage;
    use \Frootbox\Persistence\Traits\Uid;

    use \Frootbox\Persistence\Traits\Alias{
        \Frootbox\Persistence\Traits\Alias::getUri as getUriFromTrait;
    }

    /**
     * 
     */
    public function delete()
    {
        // Cleanup files
        $this->getImages()->map('delete');
        
        return parent::delete();
    }

    /**
     * Generate alias of event
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        // Build virtual directory
        $trace = $this->getTrace();
        $trace->shift();
        
        $vd = [ ];
        
        foreach ($trace as $category) {            
            $vd[] = $category->getTitle();
        }
                
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),            
            'virtualDirectory' => $vd,
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showCategory',
                'categoryId' => $this->getId()
            ])
        ]);
    }


    /**
     *
     */
    public function getDefaultImage(
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\ConfigStatics $configStatics,
        \Frootbox\Payload $payload,
        $width = 600,
        $height = 200
    ): string
    {
        foreach ($this->getOffspring() as $category) {

            $files = $filesRepository->fetch([
                'where' => [
                    'uid' => $category->getUid('images')
                ],
                'order' => [ 'orderId DESC' ],
                'limit' => 1,
            ]);

            if ($files->getCount() > 0) {

                $file = $files->current();

                return $file->getUriThumbnail([
                    'width' => $width,
                    'height' => $height
                ]);
            }
        }

        return $this->getDummyImage($width, $height, $payload, $configStatics, $filesRepository);
    }
    
    
    /**
     * 
     */
    public function getImages(
        $limit = 1024
    ): \Frootbox\Db\Result
    {
        // Fetch files
        $fileRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Files::class);
        $result = $fileRepository->fetch([
            'calcFoundRows' => true,
            'where' => [
                'uid' => $this->getUid('images')
            ],
            'order' => [ 'orderId DESC' ],
            'limit' => $limit
        ]);
        
        return $result;
    }


    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if ($this->getRootId() == $this->getId()) {

            $pages = $this->db->getModel(\Frootbox\Persistence\Repositories\Pages::class);
            $page = $pages->fetchById($this->getPageId());

            return $page->getUri();
        }

        return $this->getUriFromTrait($options);
    }
}