<?php
// Helper e personalizzazioni ACF.
// Snippet opzionali (filtri relationship/post_object per template, palette
// TinyMCE, ecc.) sono nel ricettario: .claude/CLAUDE.md → "Ricette".

// Nomi file ACF JSON parlanti: <prefisso-chiave>_<slug-del-titolo>.json (le chiavi
// dentro restano hash). Senza questo filtro un salvataggio da admin ricreerebbe il
// file col nome-chiave, duplicandolo. Priorità 5: il filtro di blocks.php (10) può
// ancora forzare fields.json per i field group dei blocchi. Titoli univoci!
add_filter('acf/json/save_file_name', function ($filename, $post) {
	if (empty($post['title']) || empty($post['key'])) {
		return $filename;
	}
	$prefix = preg_replace('/_[a-z0-9]+$/i', '', $post['key']);
	return $prefix . '_' . sanitize_title($post['title']) . '.json';
}, 5, 2);

// Disattiva sync ripetitore ACFxWPML
define('ACFML_REPEATER_SYNC_DEFAULT', false);

// Aggiunge la possibilità di nascondere i titoli dei campi ACF
function hidelabel_render_field_settings($field)
{
	acf_render_field_setting($field, array(
		'label'			=> __('Hide Label?'),
		'instructions'	=> '',
		'name'			=> 'hide_label',
		'type'			=> 'true_false',
		'ui'			=> 1,
	), true);
}
add_action('acf/render_field_settings', 'hidelabel_render_field_settings');

// Crea CSS specifico per ogni campo "group_xyz" trasformandolo in acf-field_xyz (substr @start_position = 6)
function hidelabel_prepare_field($field)
{
	if (@$field['hide_label']) :
		echo '<style type="text/css">
					.acf-field-', substr($field['key'], 6), ' > .acf-label {display: none;}
				</style>';
	endif;
	return $field;
}
add_filter('acf/prepare_field', 'hidelabel_prepare_field');

// Filtro ACF per gestire i livelli del menu
function acf_location_rules_types($choices)
{
	$choices['Menu']['menu_level'] = 'Menu Level';
	return $choices;
}
add_filter('acf/location/rule_types', 'acf_location_rules_types');

function acf_location_rule_values_level($choices)
{
	$choices[0] = '0';
	$choices[1] = '1';
	$choices[2] = '2';
	return $choices;
}
add_filter('acf/location/rule_values/menu_level', 'acf_location_rule_values_level');

function acf_location_rule_match_level($match, $rule, $options, $field_group)
{
	// Check if the operator is "=="
	if ($rule['operator'] == "==") {
		// Ensure the 'nav_menu_item_depth' key exists before comparing
		if (isset($options['nav_menu_item_depth'])) {
			$match = ($options['nav_menu_item_depth'] == $rule['value']);
		} else {
			$match = false; // If the key is missing, return false
		}
	}
	return $match;
}
add_filter('acf/location/rule_match/menu_level', 'acf_location_rule_match_level', 10, 4);

// Pattern per la formattazione dei campi di puro testo
function format_acf_text_fields($value, $post_id, $field)
{
	// Applica solo ai campi basati su testo.
	$text_field_types = array('text', 'textarea', 'message');
	if (in_array($field['type'], $text_field_types)) {
		// Check if value is not null or empty before processing
		if (!empty($value) && is_string($value)) {
			// Sostituisci i pattern racchiusi tra asterischi con tag <span class="alt">.
			$pattern = '/\*(.*?)\*/';
			$replacement = '<span class="alt">$1</span>';
			$value = preg_replace($pattern, $replacement, $value);
		}
	}
	return $value;
}
add_filter('acf/format_value', 'format_acf_text_fields', 10, 3);

// Aggiungi ID univoco a tutti gli array di ACF
function add_uniqueid_to_acf($value, $post_id, $field)
{
	// Controlla se il valore del campo è un array (applicabile per ripetitori e contenuti flessibili)
	if (is_array($value)) {
		// Cicla attraverso ogni elemento nell'array (riga o layout)
		foreach ($value as &$element) {
			// Controlla se l'elemento è un array e non ha un 'unique_id'
			if (is_array($element) && !isset($element['unique_id'])) {
				// Genera un ID univoco e assegnalo all'elemento
				$element['unique_id'] = uniqid();
			}
		}
	}
	return $value;
}
add_filter('acf/load_value', 'add_uniqueid_to_acf', 10, 3);

// Google API per campo Maps — definisci GMAPS_API_KEY in wp-config.php
function my_acf_google_map_api($api)
{
	$api['key'] = GMAPS_API_KEY;
	return $api;
}
add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

// Personalizza WYSIWYG Toolbar
add_filter('acf/fields/wysiwyg/toolbars', 'customize_acf_wysiwyg_toolbars');
function customize_acf_wysiwyg_toolbars($toolbars)
{
	// This ensures our TinyMCE filter runs for ACF fields
	add_filter('acf/prepare_field/type=wysiwyg', 'add_acf_wysiwyg_custom_settings');

	return $toolbars;
}

// Add custom settings to ACF WYSIWYG fields
function add_acf_wysiwyg_custom_settings($field)
{
?>
	<script type="text/javascript">
		(function($) {

			// Hook into ACF's TinyMCE initialization
			if (typeof acf !== 'undefined') {
				acf.add_filter('wysiwyg_tinymce_settings', function(mceInit, id, $field) {

					// Remove H1 from format dropdown
					mceInit.block_formats = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre';

					// Palette colori custom: vedi .claude/CLAUDE.md → "Ricette"

					// Number of columns in color picker
					mceInit.textcolor_cols = 5;

					// Remove the toolbar toggle button and show all toolbars by default
					mceInit.wordpress_adv_hidden = false;

					// Remove the kitchen sink (toggle) button from toolbar
					if (mceInit.toolbar1) {
						mceInit.toolbar1 = mceInit.toolbar1.replace(',wp_adv', '').replace('wp_adv,', '').replace('wp_adv', '');
					}

					// Ensure the kitchen sink (show/hide advanced toolbar) is always shown
					if (mceInit.toolbar2) {
						// Move toolbar2 buttons to toolbar1 to make them always visible
						mceInit.toolbar1 = mceInit.toolbar1 + ',' + mceInit.toolbar2;
						mceInit.toolbar2 = '';
					}

					return mceInit;
				});
			}

		})(jQuery);
	</script>
<?php

	return $field;
}
// Add CSS for larger color swatches in admin
add_action('admin_head', 'acf_wysiwyg_larger_color_swatches');
function acf_wysiwyg_larger_color_swatches()
{
?>
	<style>
		.mce-grid-cell {
			width: 40px !important;
			height: 40px !important;
		}

		.mce-grid-cell>div {
			width: 36px !important;
			height: 36px !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
		}
	</style>
<?php
}

