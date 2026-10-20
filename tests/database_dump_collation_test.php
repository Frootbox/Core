<?php

// Run with: php tests/database_dump_collation_test.php
require dirname(__DIR__) . '/lib/autoload.php';
require dirname(__DIR__) . '/cms/extensions/Core/Development/classes/Apps/Database/DumpNormalizer.php';

use Frootbox\Ext\Core\Development\Apps\Database\DumpNormalizer;

// Use the installed dump library's real CREATE TABLE wrapper. Its PHP 8.2
// string-interpolation deprecations are unrelated to this regression test.
$errorReporting = error_reporting(E_ALL & ~E_DEPRECATED);
class_exists(\Ifsnop\Mysqldump\Mysqldump::class);
error_reporting($errorReporting);
$adapter = new \Ifsnop\Mysqldump\TypeAdapterMysql(null, [
    'reset-auto-increment' => false,
    'default-character-set' => 'utf8mb4',
]);

$sourceTable = <<<'SQL'
CREATE TABLE `utf8mb3_uca1400_ai_ci` (
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci DEFAULT 'utf8mb3_uca1400_ai_ci',
  `code` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `legacy` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `native` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `other` varchar(100) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL,
  `note` text COMMENT 'escaped \' quote; utf8mb3_uca1400_ai_ci; doubled '' quote;
still a comment: utf8mb3',
  `quoted` varchar(255) DEFAULT "utf8mb3_uca1400_ai_ci"
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci COMMENT='utf8mb3'
SQL;

$expectedTable = <<<'SQL'
CREATE TABLE `utf8mb3_uca1400_ai_ci` (
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'utf8mb3_uca1400_ai_ci',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `legacy` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `native` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `other` varchar(100) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL,
  `note` text COMMENT 'escaped \' quote; utf8mb3_uca1400_ai_ci; doubled '' quote;
still a comment: utf8mb3',
  `quoted` varchar(255) DEFAULT "utf8mb3_uca1400_ai_ci"
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='utf8mb3'
SQL;

$unchangedTable = <<<'SQL'
CREATE TABLE `unaffected` (
  `utf8mb3` varchar(100) DEFAULT 'utf8mb3_uca1400_ai_ci'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL;

$path = tempnam(sys_get_temp_dir(), 'dump-test-');

try {
    foreach (['structure', 'complete'] as $mode) {
        $rows = $mode === 'complete'
            ? "INSERT INTO `utf8mb3_uca1400_ai_ci` VALUES ('utf8mb3_uca1400_ai_ci', 'Grüße 😀');\n"
            : '';
        $unchanged = $adapter->create_table(['Create Table' => $unchangedTable]);
        $input = $adapter->create_table(['Create Table' => $sourceTable]) . $rows . $unchanged;
        $expected = $adapter->create_table(['Create Table' => $expectedTable]) . $rows . $unchanged;
        file_put_contents($path, $input);

        DumpNormalizer::normalizeFile($path);

        if (file_get_contents($path) !== $expected || filesize($path) !== strlen($expected)) {
            throw new RuntimeException($mode . ': incorrect dump or download size');
        }

        DumpNormalizer::normalizeFile($path);
        if (file_get_contents($path) !== $expected) {
            throw new RuntimeException($mode . ': normalization is not idempotent');
        }
        echo $mode . ": PASS\n";
    }

    // Definitions can span read chunks, including within quoted comments.
    $longComment = str_repeat('utf8mb3_uca1400_ai_ci; ', 1600);
    $longSource = str_replace("COMMENT='utf8mb3'", "COMMENT='" . $longComment . "'", $sourceTable);
    $longExpected = str_replace("COMMENT='utf8mb3'", "COMMENT='" . $longComment . "'", $expectedTable);
    file_put_contents($path, $adapter->create_table(['Create Table' => $longSource]));
    DumpNormalizer::normalizeFile($path);
    if (file_get_contents($path) !== $adapter->create_table(['Create Table' => $longExpected])) {
        throw new RuntimeException('Definition spanning read chunks was changed incorrectly');
    }
    echo "table definition spanning chunks: PASS\n";

    // An INSERT exceeding the memory budget must be copied without buffering
    // the full line or mistaking chunk boundaries for new SQL statements.
    $file = fopen($path, 'wb');
    $hash = hash_init('sha256');
    $prefix = "INSERT INTO `data` VALUES ('";
    $padding = str_repeat('x', 8191 - strlen($prefix));
    $largeChunk = str_pad('CREATE TABLE utf8mb3_uca1400_ai_ci; Grüße 😀 ', 8191, 'x');
    foreach ([$prefix, $padding] as $chunk) {
        fwrite($file, $chunk);
        hash_update($hash, $chunk);
    }
    for ($i = 0; $i < 4096; ++$i) {
        fwrite($file, $largeChunk);
        hash_update($hash, $largeChunk);
    }
    fwrite($file, "');\n");
    hash_update($hash, "');\n");
    fclose($file);

    $previousLimit = ini_set('memory_limit', '16M');
    DumpNormalizer::normalizeFile($path);
    ini_set('memory_limit', $previousLimit);
    if (hash_file('sha256', $path) !== hash_final($hash)) {
        throw new RuntimeException('Large INSERT contents changed');
    }
    echo "large INSERT with bounded memory: PASS\n";

    $incomplete = "CREATE TABLE `incomplete` (\n  `name` text";
    file_put_contents($path, $incomplete);
    try {
        DumpNormalizer::normalizeFile($path);
        throw new LogicException('Incomplete dump was accepted');
    }
    catch (RuntimeException $exception) {
        if (file_get_contents($path) !== $incomplete) {
            throw new RuntimeException('Original dump was replaced on failure');
        }
    }
    echo "incomplete dump preserves original: PASS\n";
}
finally {
    unlink($path);
}
