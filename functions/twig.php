<?php

use Timber\Twig\FunctionWrapper;
use Laminas\Diactoros\ServerRequestFactory;

// Allow Twig files to be editable in the theme editor
function add_custom_file_types_to_editor($file_types)
{
    $file_types['twig'] = 'text/plain';
    return $file_types;
}
add_filter('upload_mimes', 'add_custom_file_types_to_editor');

// Add custom Twig filters and functions
function extend_twig($twig)
{
    $twig->addFunction(new \Twig\TwigFunction('theme_text_domain', function () {
        return defined('DEVKIT_TEXT_DOMAIN') ? DEVKIT_TEXT_DOMAIN : 'theme';
    }));

    // convert string to slug
    $twig->addFilter(new Twig\TwigFilter('slug', function ($title) {
        return sanitize_title($title);
    }));

    // deduplicate array
    $twig->addFilter(new \Twig\TwigFilter('unique', function ($array) {
        return array_unique($array);
    }));

    // shuffle
    $twig->addFilter(new Twig\TwigFilter('shuffle', function ($array) {
        if (is_a($array, 'Timber\PostQuery')) {
            $array = $array->get();
        }
        shuffle($array);
        return $array;
    }));

    // iframe to url
    $twig->addFilter(new Twig\TwigFilter('iframesrc', function ($string) {
        $string = preg_match('/src="([^"]+)"/', $string, $match);
        $url = $match[1];
        return $url;
    }));

    // oembed videoID
    $twig->addFilter(new Twig\TwigFilter('video_id', function ($string) {
        // Extract the src URL from the iframe
        preg_match('/src="([^"]+)"/', $string, $match);
        $url = $match[1];

        // Check for YouTube URL and extract the video ID
        if (preg_match('/(?:youtube\.com\/embed\/|youtu\.be\/)([^\/\?\&]+)/', $url, $id_match)) {
            return $id_match[1];
        }

        // Check for Vimeo URL and extract the video ID
        if (preg_match('/vimeo\.com\/video\/(\d+)/', $url, $id_match)) {
            return $id_match[1];
        }

        // Return null or an empty string if no valid video ID is found
        return null;
    }));

    // sortbykey
    $twig->addFilter(new Twig\TwigFilter('sortby', function ($array, $key, $order = 'asc') {
        usort($array, function ($a, $b) use ($key, $order) {
            if ($order === 'desc') {
                return $b->{$key} <=> $a->{$key};
            } else {
                return $a->{$key} <=> $b->{$key};
            }
        });
        return $array;
    }));

    // Sort by term order
    $twig->addFilter(new Twig\TwigFilter('sbt', function ($terms) {
        usort($terms, function ($a, $b) {
            return ((int) $a->term_order) <=> ((int) $b->term_order);
        });
        return $terms;
    }));

    // import SVG
    $twig->addFilter(new \Twig\TwigFilter('svg', function ($source) {
        // Handle relative paths from theme directory
        if (!file_exists($source)) {
            $theme_path = get_template_directory() . '/' . ltrim($source, '/');
            if (file_exists($theme_path)) {
                $source = $theme_path;
            }
        }

        // Check if file exists and is readable
        if (!file_exists($source) || !is_readable($source)) {
            return '<!-- SVG file not found: ' . esc_html($source) . ' -->';
        }

        // Verify it's an SVG file
        $file_info = pathinfo($source);
        if (strtolower($file_info['extension']) !== 'svg') {
            return '<!-- Invalid file type. Expected SVG: ' . esc_html($source) . ' -->';
        }

        // Read and return SVG content
        $svg_content = file_get_contents($source);

        // Basic security: ensure it's valid SVG content
        if ($svg_content === false || strpos($svg_content, '<svg') === false) {
            return '<!-- Invalid SVG content: ' . esc_html($source) . ' -->';
        }

        return $svg_content;
    }, ['is_safe' => ['html']]));

    // Get PDF Preview
    $twig->addFilter(new Twig\TwigFilter('pdf', function ($attachment_id) {
        return wp_get_attachment_image_src($attachment_id, 'default');
    }));

    // Readable Size
    $twig->addFilter(new \Twig\TwigFilter('size', function ($bytes, $precision = 2) {
        // The logic from your readableFilesize function
        if (!is_numeric($bytes) || $bytes < 0) {
            throw new InvalidArgumentException('The value must be a non-negative number.');
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }));

    // Get Filebird folder
    $twig->addFilter(new Twig\TwigFilter('fvb', function ($attachment_id) {
        global $wpdb;

        // Use wpdb->prefix to make the table names dynamic and universal
        $attachment_table = $wpdb->prefix . 'fbv_attachment_folder';
        $folder_table = $wpdb->prefix . 'fbv';

        // Query to get the folder ID
        $folder_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT folder_id FROM {$attachment_table} WHERE attachment_id = %d",
                $attachment_id
            )
        );

        // If folder ID is found, get the folder name
        if ($folder_id) {
            return $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$folder_table} WHERE id = %d",
                    $folder_id
                )
            );
        }

        return null; // Return null if no folder is found
    }));

    // Get field in twig
    $twig->addFunction(new Twig\TwigFunction('get_field', function ($field_name) {
        return get_field($field_name);
    }));

    // Generate uniqueID
    $twig->addFunction(new Twig\TwigFunction('uniqueid', function () {
        return uniqid();
    }));

    return $twig;
}
add_filter('timber/twig', 'extend_twig');

// Add a request object from laminas-diactoros PSR-7 server request
add_filter('timber/context', function ($context) {
    $context['request'] = ServerRequestFactory::fromGlobals();
    return $context;
});
