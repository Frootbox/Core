<?php
/**
 *
 */

namespace Frootbox\View\Minifier;

class JsMinifier extends \MatthiasMullie\Minify\JS {

    /**
     *
     */
    public function getHash ( ): string {

        return md5(json_encode($this->data));
    }
}
