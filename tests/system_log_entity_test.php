<?php

// Run with: php tests/system_log_entity_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');

class LogTestEntity extends \Frootbox\Db\Row
{
    protected $model = LogTestRepository::class;

    public function getUri(): string
    {
        return '/test-entity';
    }
}

class LogTestRepository extends \Frootbox\Db\Model
{
    public function __construct() {}

    public function fetchById($rowId): \Frootbox\Db\Row
    {
        if ($rowId !== 42) {
            throw new RuntimeException('Entity deleted');
        }
        return new LogTestEntity(['id' => $rowId]);
    }
}

class LogTestDb extends \Frootbox\Db\Db
{
    public function __construct() {}

    public function getRepository($model): \Frootbox\Db\Model
    {
        if ($model !== LogTestRepository::class) {
            throw new RuntimeException('Unexpected repository');
        }
        return new LogTestRepository();
    }
}

$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(
    CORE_DIR . 'cms/extensions/Core/System/classes/Admin/Gizmos/Logs/resources/private/views'
), ['strict_variables' => true]);
$view = new class {
    public function getViewHelper($name) { return $this; }
    public function translate($code, $data) { return 'Log message'; }
};
$db = new LogTestDb();
$cases = [
    'empty data' => [],
    'cron success' => ['ExampleCron'],
    'cron failure' => ['ExampleCron', 'Failure', '/path/to/cron.php', 24],
    'invalid class type' => [42, 'Title', 123],
    'missing class' => [42, 'Title', 'MissingEntityClass'],
    'unrelated class' => [42, 'Title', stdClass::class],
    'missing id' => [2 => LogTestEntity::class],
    'deleted entity' => [43, 'Title', LogTestEntity::class],
    'existing entity' => [42, 'Title', LogTestEntity::class],
];
foreach ($cases as $label => $data) {
    $log = new \Frootbox\Persistence\SystemLog([
        'config' => $data, 'log_code' => 'Test', 'date' => '2026-11-04 12:00:00',
    ], $db);
    $expected = $label === 'existing entity';
    if (($log->getEntity() instanceof LogTestEntity) !== $expected) {
        throw new RuntimeException($label . ': unexpected entity');
    }
    $logs = new class([$log]) extends ArrayObject {
        public function getCount(): int { return $this->count(); }
    };
    $html = $twig->render('View.html.twig', ['view' => $view, 'logs' => $logs]);
    if (!str_contains($html, 'Log message') || str_contains($html, 'data-href=') !== $expected) {
        throw new RuntimeException($label . ': incorrect log rendering');
    }
    echo $label . ": PASS\n";
}
