<?php

//BOILERPLATES
// Filto ACF campo relazioni isolato per template
// function acf_rel_sample($args, $field, $post_id)
// {
// 	$args['meta_key'] = '_wp_page_template';
// 	$args['meta_value'] = ['template-name.php'];
// 	return $args;
// }
// add_filter('acf/fields/relationship/query/name=field_name', 'acf_rel_sample', 10, 3);

// Filto ACF per oggetto post isolato per template
// function acf_obj_sample($args, $field, $post_id) {
// 	$args['meta_key'] = '_wp_page_template';
// 	$args['meta_value'] = ['template.php', 'template-2.php'];
// 	return $args;
// }
// add_filter('acf/fields/post_object/query/name=field_name', 'acf_obj_sample', 10, 3);

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

// Google API per campo Maps
function my_acf_google_map_api($api)
{
	$api['key'] = '';
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

					// Add custom colors to text color palette
					// mceInit.textcolor_map = [
					// 	// Your 4 custom colors first
					// 	'4b7393', 'Avio ZS',
					// 	'0d5ea2', 'Azzurro ZS',
					// 	'003764', 'Blu ZS',
					// 	'71b3e7', 'Celeste ZS'
					// ];

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

// Help swatches for colors
// add_action('acf/input/admin_head', 'add_color_swatches_to_select');
function add_color_swatches_to_select()
{
?>

	<script type="text/javascript">
		(function($) {

			// Method 2: JavaScript approach - modify select options after page load
			$(document).ready(function() {

				// Define your colors
				var colorMap = {
					'blu': '#003764',
					'azzurro': '#0d5ea2',
					'celeste': '#71b3e7',
					'avio': '#4b7393',
					'none': '#fff'
				};

				// Alternative: Create a custom visual select
				$('[data-name="color"]').each(function() {
					var $field = $(this);
					var $select = $field.find('select');

					if ($select.length && !$field.find('.color-select-visual').length) {

						// Hide original select and create visual version
						$select.hide();

						var $visualSelect = $('<div class="color-select-visual"></div>');
						var currentValue = $select.val();

						// Create display button
						var $display = $('<div class="color-select-display"></div>');
						updateDisplay();

						// Create dropdown
						var $dropdown = $('<div class="color-select-dropdown" style="display:none;"></div>');

						$select.find('option').each(function() {
							var $option = $(this);
							var value = $option.val();
							var text = $option.text();

							var $item = $('<div class="color-select-item" data-value="' + value + '"></div>');

							if (colorMap[value]) {
								$item.html('<span class="color-swatch" style="background:' + colorMap[value] + ';"></span>' + text);
							} else {
								$item.text(text);
							}

							$item.click(function() {
								$select.val(value).trigger('change');
								currentValue = value;
								updateDisplay();
								$dropdown.hide();
							});

							$dropdown.append($item);
						});

						function updateDisplay() {
							var selectedText = $select.find('option:selected').text();
							if (colorMap[currentValue]) {
								$display.html('<span class="color-swatch" style="background:' + colorMap[currentValue] + ';"></span>' + selectedText);
							} else {
								$display.text(selectedText);
							}
						}

						$display.click(function() {
							$dropdown.toggle();
						});

						$visualSelect.append($display).append($dropdown);
						$select.after($visualSelect);
					}
				});
			});

		})(jQuery);
	</script>

	<style>
		/* Styles for the custom visual select */
		.color-select-visual {
			position: relative;
		}

		.color-select-display {
			border: 1px solid #ddd;
			padding: 8px 12px;
			background: #fff url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23555%22%2F%3E%3C%2Fsvg%3E') no-repeat right 5px top 55%;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.color-select-dropdown {
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			border: 1px solid #ddd;
			background: white;
			z-index: 1000;
			max-height: 200px;
			overflow-y: auto;
		}

		.color-select-item {
			padding: 8px 12px;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.color-select-item:hover {
			background: #f5f5f5;
		}

		.color-swatch {
			display: inline-block;
			width: 16px;
			height: 16px;
			border: 1px solid #ccc;
			border-radius: 2px;
			flex-shrink: 0;
		}
	</style>
<?php
}
