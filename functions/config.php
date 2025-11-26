<?php

/**
 * Helper utilities to expose installer-driven configuration.
 */
function devkit_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $configPath = get_template_directory() . '/devkit.config.json';

    if (!file_exists($configPath)) {
        $config = [];
        return $config;
    }

    $contents = file_get_contents($configPath);
    $decoded = json_decode($contents, true);

    $config = is_array($decoded) ? $decoded : [];

    return $config;
}

function devkit_config_get($path = null, $default = null)
{
    $config = devkit_config();

    if ($path === null) {
        return $config;
    }

    $segments = explode('.', $path);
    $current = $config;

    foreach ($segments as $segment) {
        if (!is_array($current) || !array_key_exists($segment, $current)) {
            return $default;
        }

        $current = $current[$segment];
    }

    return $current;
}

if (!defined('DEVKIT_TEXT_DOMAIN')) {
    define('DEVKIT_TEXT_DOMAIN', devkit_config_get('theme.textDomain', 'theme'));
}
