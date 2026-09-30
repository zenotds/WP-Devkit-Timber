<?php

// Icone Font Awesome: ogni <i class="fa fa-nome"></i> dell'output Timber diventa l'SVG di assets/icons/<stile>/<nome>.svg (npm run make:icons), con le classi e gli attributi dell'<i>
add_filter('timber/compile/result', 'theme_icons_inline');

// Classe di stile → cartella; `fa` da sola è solid
const THEME_ICON_STYLES = [
	'fa' => 'solid', 'fas' => 'solid', 'fa-solid' => 'solid',
	'fab' => 'brands', 'fa-brands' => 'brands',
	'far' => 'regular', 'fa-regular' => 'regular',
	'fal' => 'light', 'fa-light' => 'light',
	'fat' => 'thin', 'fa-thin' => 'thin',
];

function theme_icons_inline($output)
{
	if (!is_string($output) || stripos($output, '<i') === false) {
		return $output;
	}

	// Solo <i> vuoti; i valori quotati possono contenere '>' (Alpine)
	return preg_replace_callback(
		'/<i\s((?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+)>\s*<\/i>/i',
		'theme_icon_replace',
		$output
	) ?? $output;
}

function theme_icon_replace(array $match): string
{
	$attrs = $match[1];

	// (^|\s) esclude :class e x-bind:class
	if (!preg_match('/(^|\s)class\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $attrs, $class, PREG_OFFSET_CAPTURE)) {
		return $match[0];
	}

	$value = $class[2][1] >= 0 ? $class[2][0] : $class[3][0];
	if (!preg_match('/(^|\s)fa/', $value)) {
		return $match[0];
	}
	$classes = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
	$icon = theme_icon_resolve($classes);
	if (!$icon) {
		return $match[0];
	}

	$rest = trim(substr_replace($attrs, '', $class[0][1], strlen($class[0][0])));
	$a11y = preg_match('/(^|\s)aria-(hidden|label|labelledby)\s*=/i', $rest) ? '' : ' aria-hidden="true"';

	return '<svg class="svg-inline--fa ' . str_replace('"', '&quot;', $value) . '"'
		. ($rest !== '' ? ' ' . $rest : '') . $a11y
		. ' focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="' . $icon['viewbox'] . '">'
		. $icon['inner'] . '</svg>';
}

// Primo fa-<nome> con un file: nello stile indicato, poi negli altri generati (un brand scritto con `far`, uno stile non generato)
function theme_icon_resolve(array $classes): ?array
{
	$style = 'solid';
	foreach ($classes as $class) {
		if (isset(THEME_ICON_STYLES[$class]) && $class !== 'fa') {
			$style = THEME_ICON_STYLES[$class];
		}
	}

	foreach ($classes as $class) {
		if (strncmp($class, 'fa-', 3) !== 0 || isset(THEME_ICON_STYLES[$class])) {
			continue;
		}
		$name = substr($class, 3);
		foreach (array_unique([$style, 'regular', 'solid', 'brands']) as $try) {
			if ($icon = theme_icon_svg($try, $name)) {
				return $icon;
			}
		}
	}

	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log('[theme icons] nessun SVG per: ' . implode(' ', $classes));
	}
	return null;
}

// viewBox e contenuto dell'SVG, con cache per richiesta. Nome da HTML/ACF: solo [a-z0-9-]
function theme_icon_svg(string $style, string $name): ?array
{
	static $cache = [];
	static $aliases = null;

	$key = "$style/$name";
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}
	if (!preg_match('/^[a-z0-9-]+$/', $style) || !preg_match('/^[a-z0-9-]+$/', $name)) {
		return $cache[$key] = null;
	}

	$dir = get_template_directory() . '/assets/icons';
	$file = "$dir/$style/$name.svg";
	if (!is_file($file)) {
		$aliases ??= is_readable("$dir/aliases.php") ? include "$dir/aliases.php" : [];
		$canonical = $aliases[$style === 'brands' ? 'brands' : 'classic'][$name] ?? null;
		$file = $canonical ? "$dir/$style/$canonical.svg" : null;
	}

	$svg = $file && is_file($file) ? file_get_contents($file) : false;
	if (!$svg || !preg_match('/viewBox="([^"]+)"[^>]*>(.*)<\/svg>/s', $svg, $parts)) {
		return $cache[$key] = null;
	}

	return $cache[$key] = ['viewbox' => $parts[1], 'inner' => $parts[2]];
}
