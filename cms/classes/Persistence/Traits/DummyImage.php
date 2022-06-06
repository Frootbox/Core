<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait DummyImage
{
    /**
     *
     */
    public function getDummyImage(
        $width,
        $height,
        \Frootbox\Payload $payload,
        \Frootbox\ConfigStatics $configStatics,
        \Frootbox\Persistence\Repositories\Files $files,
        $uid = null
    ): string
    {
        $params = [
            'uid' => $uid ?? null,
            'width' => (!empty($width) ? (int) $width : null),
            'height' => (!empty($height) ? (int) $height : null),
        ];

        $key = md5(serialize($params));
        $cachefile = FILES_DIR . 'cache/public/' . $configStatics->getCacheRevision() . '/dummy/image-' . $key . '.jpg';

        if (file_exists($cachefile)) {
            return 'FILE:' . $cachefile;
        }

        $payload->addData($params);

        $data = $payload->export();

        return SERVER_PATH_PROTOCOL . 'static/Ext/Core/Images/Dummy/render/?' . http_build_query($data);
    }
}
