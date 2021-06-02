<?php
// Load JSON configuration
$app_config = require __DIR__ . '/config/loader.php';

// Check if JSON configuration has been loaded correctly
// TODO: Use the F3 error handler
if ($app_config === false) {
    die('Unable to load JSON configuration.');
}

// Load F3
$f3 = require __DIR__ . '/' . $app_config->framework . '/lib/base.php';

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
        mkdir($f3->get('APP_CONFIG')->cache->path . '/cache/', 0777, true);
        mkdir($f3->get('APP_CONFIG')->cache->path . '/tmp/', 0777, true);
    }
    $f3->set('CACHE', 'folder=' . $f3->get('APP_CONFIG')->cache->path . '/cache/');
    $f3->set('TEMP', $f3->get('APP_CONFIG')->cache->path . '/tmp/');
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

        // Render 'framework' template
        echo Template::instance()->render($f3->get('APP_CONFIG')->layout . '/framework.html');
    }
);

// Set plugins routes
// TODO: make that much better!!
if (count(glob(realpath($f3->get('APP_CONFIG')->plugins) . '/*/*.json')) > 0) {
    $plugins = [];

    // Search for plugin JSON file
    foreach (glob(realpath($f3->get('APP_CONFIG')->plugins) . '/*/*.json') as $plugin_file) {
        $raw_plugin_data = file_get_contents($plugin_file);
        $json_plugin_data = json_decode($raw_plugin_data);

        // Avoid JSON decoding issues
        if ($json_plugin_data !== false) {
            // Check if enabled before inclusion
            if (property_exists($json_plugin_data, 'enabled') && $json_plugin_data->enabled === true) {
                $plugin = new stdClass;
                $plugin->location = pathinfo($plugin_file, PATHINFO_DIRNAME);
                $plugin->details = $json_plugin_data->details;
                $plugin->routes = $json_plugin_data->routes;
        
                $plugins[] = $plugin;
            }
        }
    }

    // Add plugins related routes
    if (count($plugins) > 0) {
        foreach ($plugins as $found_plugin) {
            $f3->route('GET /' . $found_plugin->details->name,
                function ($f3) {
                    global $found_plugin;
    
                    // Set template values
                    $f3->set('PLUGIN_CONFIG', $found_plugin);
                    $f3->set('PLUGIN_NAME', $found_plugin->details->display_name);
                    $f3->set('PLUGIN_DESC', $found_plugin->details->description);
                    $f3->set('PLUGIN_URL', $found_plugin->details->url);
                    $f3->set('PLUGIN_HELP', $f3->get('APP_CONFIG')->framework);
                    $f3->set('PLUGIN_CSS', $f3->get('APP_CONFIG')->layout . '/css');
                    $f3->set('PLUGIN_IMG', $f3->get('APP_CONFIG')->layout . '/img');
                    $f3->set('PLUGIN_JS', $f3->get('APP_CONFIG')->layout . '/js');
                    $f3->set('PLUGIN_YEAR', date("Y"));
    
                    // Render 'framework' template
                    echo Template::instance()->render(str_replace(__DIR__, './', $found_plugin->location) . '/' . $found_plugin->details->name . '.html');
                }
            );

            // Add plugins related sub routes
            if (count($found_plugin->routes) > 0) {
                foreach ($found_plugin->routes as $found_plugin_route) {
                    if (property_exists($found_plugin_route, 'params') && $found_plugin_route->params === true) {
                        if (is_file($found_plugin->location . '/' . $found_plugin_route->resource)) {
                            $f3->route($found_plugin_route->method . ' ' . $found_plugin_route->name,
                                function ($f3, $params) {
                                    global $found_plugin, $found_plugin_route;
                                    require $found_plugin->location . '/' . $found_plugin_route->resource;
                                }
                            );
                        }
                        else {
                            $f3->route($found_plugin_route->method . ' ' . $found_plugin_route->name, $found_plugin_route->resource);
                        }
                    }
                    else {
                        if (is_file($found_plugin->location . '/' . $found_plugin_route->resource)) {
                            $f3->route($found_plugin_route->method . ' ' . $found_plugin_route->name,
                                function ($f3) {
                                    global $found_plugin, $found_plugin_route;
                                    require $found_plugin->location . '/' . $found_plugin_route->resource;
                                }
                            );
                        }
                        else {
                            $f3->route($found_plugin_route->method . ' ' . $found_plugin_route->name, $found_plugin_route->resource);
                        }
                    }
                }
            }
        }
    }
}

// Init routing engine
$f3->run();

// Some hidden debugging code
if ($f3->get('APP_CONFIG')->debug === true) {
    echo PHP_EOL . '<!--' . PHP_EOL;
    echo print_r($plugins, true);
    echo htmlentities(print_r($f3, true));
    echo PHP_EOL . '-->' . PHP_EOL;
}
