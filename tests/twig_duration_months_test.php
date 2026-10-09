<?php

// Run with: php tests/twig_duration_months_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
define('FILES_DIR', sys_get_temp_dir() . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');

$view = new \Frootbox\View\Engines\Twig();
$twig = (new ReflectionProperty($view, 'twig'))->getValue($view);
$twig->setCache(false);
$twig->setLoader(new \Twig\Loader\ArrayLoader([
    'duration' => '{{ months | duration_months }}',
]));

$cases = [
    [null, ''],
    [0, ''],
    [-1, ''],
    [1, '1 Monat'],
    [2, '2 Monate'],
    [11, '11 Monate'],
    [12, '1 Jahr'],
    [13, '1 Jahr und 1 Monat'],
    [18, '1 Jahr und 6 Monate'],
    [24, '2 Jahre'],
    [25, '2 Jahre und 1 Monat'],
    [36, '3 Jahre'],
    [42, '3 Jahre und 6 Monate'],
    [120, '10 Jahre'],
    ['36', '3 Jahre'],
];

foreach ($cases as [$months, $expected]) {
    $actual = $view->render('duration', ['months' => $months]);

    if ($actual !== $expected) {
        throw new \RuntimeException(sprintf(
            'duration_months(%s): expected %s, got %s',
            var_export($months, true),
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

echo 'duration_months: ' . count($cases) . " cases PASS\n";
