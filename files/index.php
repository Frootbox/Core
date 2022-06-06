<?php
/**
 *
 */


$request = explode('?', $_SERVER['REQUEST_URI'])[0];

$path = str_replace(dirname($_SERVER['SCRIPT_NAME']) . '/', '', $request) . '.xdata.json';

$json = file_get_contents($path);
$request = json_decode($json, true);

$query = http_build_query($request);

$location = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . str_replace('/files/index.php', '', $_SERVER['SCRIPT_NAME']) . '/static/Ext/Core/Images/Thumbnail/render?' . $query;

header("HTTP/1.1 307 Temporary Redirect");
header("Location: " . $location);

exit;
