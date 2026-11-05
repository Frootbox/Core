<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * The connection of this installation to frootbox.de: key pair, keys of
 * the hub, open request, connection, hash of the status token.
 *
 * It lives in frootbox-de.key.php next to localconfig.php, not in the
 * database, so a copy of the database never takes the connection or the
 * key along. The file starts with "<?php exit; ?>": opened in the browser
 * it shows nothing, and it is read as text, so OPcache never serves an old
 * state. Each change holds a lock on the file.
 */
final class State
{
    public const FILE = 'frootbox-de.key.php';
    private const HEAD = "<?php exit; ?>\n";

    public function __construct(
        private readonly string $file = CORE_DIR . self::FILE,
    ) {}

    public function file(): string
    {
        return $this->file;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $content = file_get_contents($this->file);

        return $this->decode($content === false ? '' : $content);
    }

    /**
     * Changes the state under a lock and returns the new one.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $change
     *
     * @return array<string, mixed>
     */
    public function update(callable $change): array
    {
        $new = !is_file($this->file);
        $handle = @fopen($this->file, 'c+');

        if ($handle === false) {
            throw HubException::notWritable($this->file);
        }

        try {
            if ($new) {
                @chmod($this->file, 0600);
            }

            flock($handle, LOCK_EX);
            $content = stream_get_contents($handle);
            $state = $change($this->decode($content === false ? '' : $content));

            ftruncate($handle, 0);
            rewind($handle);

            if (fwrite($handle, self::HEAD . json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n") === false) {
                throw HubException::notWritable($this->file);
            }

            fflush($handle);

            return $state;
        }
        finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function forget(): void
    {
        if (is_file($this->file) && !@unlink($this->file)) {
            throw HubException::notWritable($this->file);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        if (!str_starts_with($content, self::HEAD)) {
            throw HubException::unreadable($this->file);
        }

        $data = json_decode(substr($content, strlen(self::HEAD)), true);

        if (!is_array($data)) {
            throw HubException::unreadable($this->file);
        }

        return $data;
    }
}
