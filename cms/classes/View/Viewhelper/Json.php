<?php

namespace Frootbox\View\Viewhelper;

class Json extends AbstractViewhelper
{
    /**
     * Encode JSON for a script element before the CMS HTML parser runs.
     *
     * Keep Unicode escaped for compatibility with HTML serializers outside
     * the CMS crawler, which can turn script characters into HTML entities.
     * HTML-sensitive characters are escaped to keep the script element intact.
     *
     * @throws \JsonException
     */
    public function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );
    }
}
