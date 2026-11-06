<?php

namespace Frootbox\AI;

/** Provider adapters encode these bytes in the format required by their API. */
final class Image
{
    public function __construct(
        public readonly string $data,
        public readonly string $mimeType,
    ) {
        if ($data === '') {
            throw new \InvalidArgumentException('Die Bilddaten dürfen nicht leer sein.');
        }

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new \InvalidArgumentException('Nicht unterstützter Bildtyp: ' . $mimeType);
        }
    }
}
