<?php 
/**
 * 
 */


if (dirname($_SERVER['SCRIPT_NAME']) . '/' == $_SERVER['REQUEST_URI']) {
    header('Location: admin/Session/login');
    exit;
}

if ($_SERVER['SCRIPT_NAME'] == $_SERVER['REQUEST_URI']) {
    header('Location: admin/Session/login');
    exit;
}

$request = 'cms/' . (str_replace(dirname($_SERVER['SCRIPT_NAME']) . '/', '', $_SERVER['REQUEST_URI']));
$request = preg_replace('#[^a-zA-Z0-9]#', '_', $request);


$parent = dirname(dirname($_SERVER['SCRIPT_NAME']));

$location = '//' . $_SERVER['SERVER_NAME'] . $parent . '/' . $request;

header('Location: ' . $location);