<?php

// Allow Twig files to be editable in the theme editor
function add_custom_file_types_to_editor($file_types)
{
    $file_types['twig'] = 'text/plain';
    return $file_types;
}
add_filter('upload_mimes', 'add_custom_file_types_to_editor');

/**
 * Extract a video URL from an oEmbed iframe or a raw URL.
 * Shared helper for the video_* Twig filters.
 */
function twig_video_url($input): ?string
{
    if (!is_string($input) || $input === '') {
        return null;
    }

    if (preg_match('/<iframe[^>]+src="([^"]+)"/i', $input, $match)) {
        return $match[1];
    }

    return filter_var($input, FILTER_VALIDATE_URL) ? $input : null;
}

// Add custom Twig filters and functions
function extend_twig($twig)
{
    // Convert string to slug
    $twig->addFilter(new Twig\TwigFilter('slug', 'sanitize_title'));

    // Deduplicate array
    $twig->addFilter(new Twig\TwigFilter('unique', function ($array) {
        return is_array($array) ? array_unique($array) : $array;
    }));

    // Shuffle array (supports PostQuery)
    $twig->addFilter(new Twig\TwigFilter('shuffle', function ($array) {
        if ($array instanceof Timber\PostQuery) {
            $array = $array->to_array();
        }
        if (!is_array($array)) {
            return $array;
        }
        shuffle($array);
        return $array;
    }));

    // Video URL from oEmbed iframe or raw URL
    $twig->addFilter(new Twig\TwigFilter('video_src', 'twig_video_url'));

    // Video provider (youtube | vimeo | null)
    $twig->addFilter(new Twig\TwigFilter('video_provider', function ($input) {
        $url = twig_video_url($input);
        if (!$url) {
            return null;
        }
        if (preg_match('/(?:youtube(?:-nocookie)?\.com|youtu\.be)/i', $url)) {
            return 'youtube';
        }
        if (preg_match('/vimeo\.com/i', $url)) {
            return 'vimeo';
        }
        return null;
    }));

    // Video ID from YouTube/Vimeo URLs (embed, watch, shorts, youtu.be, player.vimeo)
    $twig->addFilter(new Twig\TwigFilter('video_id', function ($input) {
        $url = twig_video_url($input);
        if (!$url) {
            return null;
        }
        if (preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:embed\/|shorts\/|watch\?(?:.*&)?v=)|youtu\.be\/)([\w-]{6,})/i', $url, $match)) {
            return $match[1];
        }
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/i', $url, $match)) {
            return $match[1];
        }
        return null;
    }));

    // Sort array of objects/arrays by key (supports PostQuery)
    $twig->addFilter(new Twig\TwigFilter('sortby', function ($array, $key, $order = 'asc') {
        if ($array instanceof Timber\PostQuery) {
            $array = $array->to_array();
        }
        if (!is_array($array)) {
            return $array;
        }
        usort($array, function ($a, $b) use ($key, $order) {
            $valA = is_array($a) ? ($a[$key] ?? null) : ($a->{$key} ?? null);
            $valB = is_array($b) ? ($b[$key] ?? null) : ($b->{$key} ?? null);
            return $order === 'desc' ? $valB <=> $valA : $valA <=> $valB;
        });
        return $array;
    }));

    // Inline SVG from theme path, absolute path or local URL
    $twig->addFilter(new Twig\TwigFilter('svg', function ($source) {
        static $cache = [];

        if (!is_string($source) || $source === '') {
            return '';
        }

        if (isset($cache[$source])) {
            return $cache[$source];
        }

        $path = $source;

        // Convert local URLs (e.g. ACF image src) to filesystem paths
        if (str_starts_with($path, 'http')) {
            $uploads = wp_get_upload_dir();
            $path = str_replace(
                [$uploads['baseurl'], get_template_directory_uri()],
                [$uploads['basedir'], get_template_directory()],
                $path
            );
        }

        // Handle paths relative to the theme directory
        if (!file_exists($path)) {
            $theme_path = get_template_directory() . '/' . ltrim($source, '/');
            if (file_exists($theme_path)) {
                $path = $theme_path;
            }
        }

        if (!is_readable($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'svg') {
            return $cache[$source] = '<!-- SVG not found: ' . esc_html($source) . ' -->';
        }

        $content = file_get_contents($path);
        if ($content === false || !str_contains($content, '<svg')) {
            return $cache[$source] = '<!-- Invalid SVG: ' . esc_html($source) . ' -->';
        }

        return $cache[$source] = $content;
    }, ['is_safe' => ['html']]));

    // PDF/attachment preview image
    $twig->addFilter(new Twig\TwigFilter('pdf', function ($attachment_id) {
        return wp_get_attachment_image_src($attachment_id, 'default');
    }));

    // Human readable filesize
    $twig->addFilter(new Twig\TwigFilter('size', function ($bytes, $precision = 2) {
        if (!is_numeric($bytes) || $bytes < 0) {
            return null;
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = $bytes ? min(floor(log($bytes) / log(1024)), count($units) - 1) : 0;
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }));

    // ACF get_field in Twig
    $twig->addFunction(new Twig\TwigFunction('get_field', 'get_field'));

    // Unique ID
    $twig->addFunction(new Twig\TwigFunction('uniqueid', 'uniqid'));

    return $twig;
}
add_filter('timber/twig', 'extend_twig');

// Sanitized request object: {{ request.get.foo }} / {{ request.post.bar }}
add_filter('timber/context', function ($context) {
    $context['request'] = (object) [
        'get'  => map_deep(wp_unslash($_GET), 'sanitize_text_field'),
        'post' => map_deep(wp_unslash($_POST), 'sanitize_text_field'),
    ];
    return $context;
});
