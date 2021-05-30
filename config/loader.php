<?php
$config = false;
if (is_file(__DIR__ . '/config.json') && is_readable(__DIR__ . '/config.json')) {
    $raw_json_config = file_get_contents(__DIR__ . '/config.json');
    $config = json_decode($raw_json_config);
}
return $config;
