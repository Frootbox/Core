<?php

require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
define('SERVER_PATH', '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
spl_autoload_register(static function (string $class): void {
    $prefix = 'Frootbox\\Ext\\Core\\System\\';
    if (str_starts_with($class, $prefix)) {
        require CORE_DIR . 'cms/extensions/Core/System/classes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

if (($argv[1] ?? '') === 'child') {
    $mode = $argv[2];
    $_SESSION = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/private';

    $config = new \Frootbox\Config\Config();
    $hash = password_hash('example', PASSWORD_DEFAULT);
    $config->append(['globalPassword' => [ 'enabled' => $mode !== 'disabled', 'hash' => $hash ]]);
    $request = (new \Frootbox\Http\ClientRequest())->setRequestTarget($mode === 'form' ? 'global-password' : 'private');
    $route = new \Frootbox\Ext\Core\System\Routing\GlobalPasswordRoute($request, $config);
    $session = (new ReflectionClass(\Frootbox\Session::class))->newInstanceWithoutConstructor();

    if ($mode === 'authorized') {
        $_SESSION['globalPassword']['authorizedHash'] = hash('sha256', $hash);
    }
    if ($mode === 'api') {
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    $route->performRouting($session);
    echo 'passed';
    exit;
}

foreach ([
    'disabled' => 'passed',
    'authorized' => 'passed',
    'form' => 'Passwort erforderlich',
    'api' => 'Passwort erforderlich.',
] as $mode => $expected) {
    $process = proc_open([PHP_BINARY, __FILE__, 'child', $mode], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status !== 0 || !str_contains($output, $expected) || $error !== '') {
        throw new RuntimeException($mode . ' failed: ' . $output . ' ' . $error);
    }
    echo $mode . ": PASS\n";
}
