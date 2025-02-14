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
    // convert string to slug
    $twig->addFilter(new Twig\TwigFilter('slug', function ($title) {
        return sanitize_title($title);
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

    // import SVG
    $twig->addFilter(new \Twig\TwigFilter('svg', function ($source) {
        // Convert the URL to a local file path if needed
        $local_path = str_replace(
            get_bloginfo('url'),    // This will get the WordPress site's URL
            ABSPATH,                // The absolute path to the root of the WordPress installation
            $source
        );

        // Check if the converted path exists
        if (file_exists($local_path)) {
            // Read and return the SVG contents
            return file_get_contents($local_path);
        } else {
            // Return an error comment if the file doesn't exist
            return "<!-- SVG file not found: $source -->";
        }
    }));

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
