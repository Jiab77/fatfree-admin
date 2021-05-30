<?php
// Load F3
$f3 = require __DIR__ . '/framework/fatfree/lib/base.php';

// Load JSON configuration
$app_config = require __DIR__ . '/config/loader.php';

// Check if JSON configuration has been loaded correctly
// TODO: Use the F3 error handler
if ($app_config === false) {
    die('Unable to load JSON configuration.');
}

// Add JSON configuration to the F3 memory space
$f3->set('APP_CONFIG', $app_config);

// Set F3 debugging level
if ($f3->get('APP_CONFIG')->debug === true) {
    $f3->set('DEBUG', 3);
}

// Set new 'tmp' and 'cache' location
if ($f3->get('APP_CONFIG')->cache->enabled === true) {
    // Create 'tmp' and 'cache' folders if not already exist
    if (!is_dir($f3->get('APP_CONFIG')->cache->path)) {
        mkdir($f3->get('APP_CONFIG')->cache->path . 'cache/', 0777, true);
        mkdir($f3->get('APP_CONFIG')->cache->path . 'tmp/', 0777, true);
    }
    $f3->set('CACHE', 'folder=' . $f3->get('APP_CONFIG')->cache->path . 'cache/');
    $f3->set('TEMP', $f3->get('APP_CONFIG')->cache->path . 'tmp/');
}

// Set routes
$f3->route('GET /',
    function ($f3) {
        // Set template values
        $f3->set('APP_HELP', $f3->get('APP_CONFIG')->framework);
        $f3->set('APP_NAME', $f3->get('APP_CONFIG')->project->display_name);
        $f3->set('APP_DESC', $f3->get('APP_CONFIG')->project->description);
        $f3->set('APP_URL', $f3->get('APP_CONFIG')->project->url);
        $f3->set('APP_CSS', $f3->get('APP_CONFIG')->layout . '/css');
        $f3->set('APP_IMG', $f3->get('APP_CONFIG')->layout . '/img');
        $f3->set('APP_JS', $f3->get('APP_CONFIG')->layout . '/js');
        $f3->set('APP_YEAR', date("Y"));

        // Render 'index' template
        echo Template::instance()->render($f3->get('APP_CONFIG')->layout . '/index.html');
    }
);

// Init routing engine
$f3->run();

// Some hidden debugging code
echo PHP_EOL . '<!--' . PHP_EOL;
echo htmlentities(print_r($f3, true));
echo PHP_EOL . '-->' . PHP_EOL;
