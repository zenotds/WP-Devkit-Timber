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
	if ($rule['operator'] == "==") {
		$match = ($options['nav_menu_item_depth'] == $rule['value']);
	}
	return $match;
}
add_filter('acf/location/rule_match/menu_level', 'acf_location_rule_match_level', 10, 4);

// Pattern per la formattazione dei campi di puro testo
function format_acf_text_fields($value, $post_id, $field) {
    // Applica solo ai campi basati su testo.
    $text_field_types = array('text', 'textarea', 'message');

    if (in_array($field['type'], $text_field_types)) {
        // Sostituisci i pattern racchiusi tra asterischi con tag <span class="alt">.
        $pattern = '/\*(.*?)\*/';
        $replacement = '<span class="alt">$1</span>';
        $value = preg_replace($pattern, $replacement, $value);
    }

    return $value;
}
add_filter('acf/format_value', 'format_acf_text_fields', 10, 3);

// Aggiungi ID univoco a tutti gli array di ACF
function add_uniqueid_to_acf($value, $post_id, $field) {
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

// Aggiungi il filtro
add_filter('acf/load_value', 'add_uniqueid_to_acf', 10, 3);