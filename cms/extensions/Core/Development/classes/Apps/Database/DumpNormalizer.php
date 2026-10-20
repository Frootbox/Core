<?php

namespace Frootbox\Ext\Core\Development\Apps\Database;

class DumpNormalizer
{
    // SHOW CREATE TABLE quotes identifiers and string literals. Skip both so
    // defaults, comments and identifiers keep their original contents.
    private const QUOTED_SQL = <<<'REGEX'
'(?:[^'\\]++|\\.|'')*'|"(?:[^"\\]++|\\.|"")*"|`(?:[^`]++|``)*`
REGEX;

    public static function normalizeFile(string $path): void
    {
        $temporaryPath = tempnam(dirname($path), 'sql-normalized-');

        if ($temporaryPath === false) {
            throw new \RuntimeException('Could not create normalized export file.');
        }

        register_shutdown_function(static function () use ($temporaryPath): void {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        });

        $input = null;
        $output = null;

        try {
            $input = fopen($path, 'rb');
            $output = fopen($temporaryPath, 'wb');

            if ($input === false || $output === false) {
                throw new \RuntimeException('Could not open database export.');
            }

            $definition = '';
            $lineStart = true;
            $tablePattern = '~\ACREATE TABLE (?:(?:' . self::QUOTED_SQL . ')|[^\'"`;]++)*;\s*\z~s';

            // Buffer table definitions only; large INSERTs pass through in
            // bounded chunks and their values are never rewritten.
            while (($chunk = fgets($input, 8192)) !== false) {
                if ($definition !== '' || ($lineStart && str_starts_with($chunk, 'CREATE TABLE '))) {
                    $definition .= $chunk;
                    $lineStart = str_ends_with($chunk, "\n");

                    if (!preg_match('/;\s*$/', $chunk)) {
                        continue;
                    }

                    $complete = preg_match($tablePattern, $definition);

                    if ($complete === false) {
                        throw new \RuntimeException('Could not parse exported table definition.');
                    }

                    if ($complete === 0) {
                        continue;
                    }

                    $chunk = self::normalizeTable($definition);
                    $definition = '';
                }
                else {
                    $lineStart = str_ends_with($chunk, "\n");
                }

                if (fwrite($output, $chunk) !== strlen($chunk)) {
                    throw new \RuntimeException('Could not write normalized database export.');
                }
            }

            if (!feof($input) || $definition !== '') {
                throw new \RuntimeException('Could not read complete database export.');
            }

            fclose($input);
            fclose($output);

            if (!rename($temporaryPath, $path)) {
                throw new \RuntimeException('Could not replace normalized database export.');
            }

            clearstatcache(true, $path);
        }
        finally {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private static function normalizeTable(string $sql): string
    {
        $skipQuoted = '(?:' . self::QUOTED_SQL . ')(*SKIP)(*F)|';
        $sql = preg_replace(
            '~' . $skipQuoted . '\butf8mb3_uca1400_ai_ci\b~is',
            'utf8mb4_unicode_ci',
            $sql,
            -1,
            $count
        );

        if ($sql === null) {
            throw new \RuntimeException('Could not normalize exported table collation.');
        }

        if ($count === 0) {
            return $sql;
        }

        // Promote the affected table's character sets as well. Other explicit
        // UTF-8 collations keep their suffix (e.g. _bin) to remain compatible.
        $sql = preg_replace('~' . $skipQuoted . '\butf8(?:mb3)?(?=\b|_)~is', 'utf8mb4', $sql);

        if ($sql === null) {
            throw new \RuntimeException('Could not normalize exported table character set.');
        }

        return $sql;
    }
}
