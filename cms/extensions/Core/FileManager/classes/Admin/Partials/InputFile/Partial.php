<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\Admin\Partials\InputFile;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial {
    
    /**
     * 
     */
    public function getPath ( ): string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Admin\View $view
    )
    {
        $size = self::getfileUploadMaxSize();
        $units = [ 'K', 'M', 'G' ];

        while ( $size > 1024 ) {

            $size /= 1024;

            $unit = array_shift($units);
        }

        $view->set('maxSize', $size . ' ' . $unit);
    }


    /**
     * @return int
     */
    private static function getfileUploadMaxSize() {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = self::parseSize(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = self::parseSize(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    private static  function parseSize($size) {

        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        else {
            return round($size);
        }
    }
}