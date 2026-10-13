<?php

// Run with: php tests/inline_file_content_type_test.php
namespace Frootbox\Ext\Core\FileManager\StaticPages\Download {
    // Capture headers in CLI, where headers_list() does not expose them.
    function header(string $header): void
    {
        $GLOBALS['inlineHeaders'][] = $header;
    }
}

namespace {
    require dirname(__DIR__) . '/lib/autoload.php';
    define('CORE_DIR', dirname(__DIR__) . '/');
    define('FILES_DIR', sys_get_temp_dir() . '/');
    require CORE_DIR . 'cms/classes/Front.php';
    spl_autoload_register('Frootbox\\Front::autoload');
    require CORE_DIR . 'cms/extensions/Core/FileManager/classes/StaticPages/Download/Page.php';

    class InlineTestFiles extends \Frootbox\Persistence\Repositories\Files
    {
        public function __construct(private \Frootbox\Persistence\File $file) {}

        public function fetchById($rowId): \Frootbox\Db\Row
        {
            return $this->file;
        }
    }

    if (($argv[1] ?? '') === 'child') {
        $path = tempnam(FILES_DIR, 'inline-test-');
        file_put_contents($path, 'original-file-bytes');
        $GLOBALS['inlineHeaders'] = [];
        ob_start();
        register_shutdown_function(static function () use ($path): void {
            $body = ob_get_clean();
            unlink($path);
            echo json_encode(['headers' => $GLOBALS['inlineHeaders'], 'body' => $body]);
        });

        if (($argv[3] ?? '') === 'range') {
            $_SERVER['HTTP_RANGE'] = 'bytes=0-7';
        }

        $file = new \Frootbox\Persistence\File([
            'id' => 42,
            'path' => basename($path),
            'type' => $argv[2],
            'isPrivate' => false,
        ]);
        (new \Frootbox\Ext\Core\FileManager\StaticPages\Download\Page())->inline(
            new \Frootbox\Http\Get(), new InlineTestFiles($file)
        );
        exit;
    }

    foreach (['image/jpeg', 'image/png', 'image/webp', 'video/mp4'] as $type) {
        foreach (['full', 'range'] as $mode) {
            $process = proc_open([PHP_BINARY, __FILE__, 'child', $type, $mode], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
            $result = json_decode($output, true);

            if ($status !== 0 || $error !== '' || !is_array($result)) {
                throw new \RuntimeException('Inline endpoint failed: ' . $output . ' ' . $error);
            }
            if (!in_array('Content-Type: ' . $type, $result['headers'], true)) {
                throw new \RuntimeException('Incorrect Content-Type for ' . $type);
            }
            $expectedBody = $mode === 'range' ? 'original' : 'original-file-bytes';
            if ($result['body'] !== $expectedBody || !in_array('Content-Length: ' . strlen($expectedBody), $result['headers'], true)) {
                throw new \RuntimeException('Incorrect inline payload for ' . $type . ' (' . $mode . ')');
            }
            echo $type . ' (' . $mode . "): PASS\n";
        }
    }
}
