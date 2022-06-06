<?php
/**
 *
 */

$tests = [
    [
        'title' => 'PDO Driver',
        'action' => 'testPdo',
        'type' => 'bool',
        'needs' => true,
    ],

];

function testPdo() {
    return class_exists('PDO');
}




foreach ($tests as $index => $test) {

    $action = $test['action'];
    $value = $action();

    $tests[$index]['value'] = $value;


}

echo "<pre>";print_r($tests);