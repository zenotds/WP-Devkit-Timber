<?php
/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 */

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions/twig.php';
require_once __DIR__ . '/functions/acf.php';
require_once __DIR__ . '/functions/menus.php';
require_once __DIR__ . '/functions/forms.php';
require_once __DIR__ . '/functions/custom.php';
require_once __DIR__ . '/functions/setup.php';

Timber\Timber::init();

// Sets the directories (inside your theme) to find .twig files.
Timber::$dirname = [ 'templates', 'views' ];

new StarterSite();
