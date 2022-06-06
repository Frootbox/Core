<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\Plugins\Slider;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use \Frootbox\Persistence\Traits\Uid;

    /**
     *
     */
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor
    ): void
    {
        $files = $container->get(\Frootbox\Persistence\Repositories\Files::class);
        $uid = $ancestor->getUid('files-' . $ancestor->getPageId());

        $result = $files->fetch([
            'where' => [
                'uid' => $uid
            ]
        ]);

        $newUid = $this->getUid('files-' . $this->getPageId());

        foreach ($result as $file) {

            $newFile = $file->duplicate();
            $newFile->setUid($newUid);
            $newFile->save();
        }
    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): void
    {
        // Cleanup files
        $files = $filesRepository->fetchByUidBase($this->getUidBase());
        $files->map('delete');
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getAspectRatio($width, $height)
    {
        if (empty($width) or empty($height)) {
            return 100;
        }

        return number_format(($height / $width) * 100, 2, '.', '');
    }

    /**
     *
     */
    public function getImages(
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $imagesRepository,
        $overrideUid = null,
        $overrideFilePath = null
    ): \Frootbox\Db\Result
    {
        if (!empty($overrideFilePath)) {

            $result = new \Frootbox\Db\Result([], $this->getDb());


            $file = new \Frootbox\Persistence\File([
                'path' => $overrideFilePath
            ]);

            $result->push($file);

            return $result;
        }



        if (!empty($overrideUid)) {

            $result = $imagesRepository->fetch([
                'where' => [
                    'uid' => $overrideUid
                ],
                'order' => [ 'orderId DESC' ]
            ]);

            if ($result->getCount() > 0) {
                return $result;
            }
        }

        // Obtain uid
        $uid = $this->getUid('files-' . $this->getPage()->getId());

        $result = $imagesRepository->fetch([
            'where' => [
                'uid' => $uid
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        if ($result->getCount() == 0 and $this->page->getParentId() != 0) {

            $trace = $this->page->getTrace();
            $trace->reverse();
            $trace->shift();

            foreach ($trace as $child) {

                $uid = $this->getUid('files-' . $child->getId());

                $result = $imagesRepository->fetch([
                    'where' => [
                        'uid' => $uid
                    ],
                    'order' => [ 'orderId DESC' ]
                ]);

                if ($result->getCount() > 0) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return new Response([

        ]);
    }
}
