<?php

require dirname(__DIR__) . '/cms/classes/PerformanceLogger.php';

if (($argv[1] ?? '') === 'child') {
    $mode = $argv[2];
    $directory = $argv[3];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/example?token=SECRET';
    $_COOKIE['private'] = 'SECRET';
    $_POST['password'] = 'SECRET';
    $options = ['enabled' => $mode !== 'disabled', 'directory' => $directory, 'release' => 'test'];
    if ($mode === 'cap') { $options['maxDailyBytes'] = 1; }
    $logger = new \Frootbox\PerformanceLogger($options, hrtime(true), $mode === 'public' ? $directory : dirname(__DIR__));
    $logger->phase('routing');
    $logger->context(['page_id' => 42, 'language' => 'de-DE', 'secret' => 'SECRET']);
    $start = hrtime(true);
    $logger->phase('content_render');
    $logger->element(1, 'ExamplePlugin', 'index', $start);
    if ($mode === 'unavailable') { rmdir($directory); }
    if ($mode === 'fatal') { trigger_error('Test fatal', E_USER_ERROR); }
    if ($mode === 'early') { http_response_code(302); echo 'redirect'; exit; }
    $logger->finish();
    $logger->finish();
    echo 'response';
    exit;
}

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

$root = sys_get_temp_dir() . '/frootbox-performance-test-' . bin2hex(random_bytes(6));
mkdir($root, 0700);
try {
    foreach (['normal', 'disabled', 'public', 'cap', 'early', 'fatal', 'unavailable'] as $mode) {
        $directory = $root . '/' . $mode;
        mkdir($directory, 0700);
        if ($mode === 'normal') {
            file_put_contents($directory . '/performance-2000-01-01.jsonl', 'old');
            file_put_contents($directory . '/keep.txt', 'keep');
        }
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', __FILE__, 'child', $mode, $directory], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $code = proc_close($process);
        check($mode === 'fatal' ? $code !== 0 : $code === 0, "$mode: exit status");
        if ($mode !== 'fatal') { check($stdout === ($mode === 'early' ? 'redirect' : 'response'), "$mode: response changed"); }
        $file = $directory . '/performance-' . gmdate('Y-m-d') . '.jsonl';
        if (in_array($mode, ['disabled', 'public', 'unavailable'], true)) {
            check(!file_exists($file), "$mode: unexpected log");
        }
        elseif ($mode === 'cap') { check(filesize($file) === 0, 'daily cap'); }
        else {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            check(count($lines) === 1, "$mode: duplicate/missing record");
            $record = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);
            check(strpos($lines[0], 'SECRET') === false, "$mode: sensitive data leaked");
            check($record['path'] === '/example' && $record['page_id'] === 42, "$mode: context");
            check($record['total_ms'] >= 0 && isset($record['phases_ms']['routing']), "$mode: timings");
            check(count($record['slowest_elements']) === 1, "$mode: element timings");
            if ($mode === 'early') { check($record['status'] === 302, 'redirect status'); }
            if ($mode === 'fatal') { check($record['fatal'] === true, 'fatal flag'); }
            if ($mode === 'normal') {
                check(!file_exists($directory . '/performance-2000-01-01.jsonl'), 'retention');
                check(file_exists($directory . '/keep.txt'), 'unrelated file removed');
            }
        }
        echo "$mode: PASS\n";
    }
}
finally {
    foreach (glob($root . '/*') as $directory) {
        foreach (glob($directory . '/*') as $file) { unlink($file); }
        rmdir($directory);
    }
    rmdir($root);
}
