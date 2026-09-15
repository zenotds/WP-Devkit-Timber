<?php

// ============================================
// CONTACT FORM 7
// ============================================
// Tre comportamenti di CF7 che vanno conosciuti prima di scrivere il markup di un form:
//
// 1. `novalidate` sul wrapper `.form` (vedi il modulo form in library/): CF7 valida a ogni `change`
//    e il suo validate() scorre tutti i wrap fino al target, quindi cliccando un consenso in fondo
//    segna come errati tutti i campi vuoti sopra. La validazione del submit resta.
//
// 2. `wpcf7-submit` sul <button> custom: è la classe che il JS di CF7 cerca per disabilitare l'invio
//    finché un [acceptance] obbligatorio non è spuntato. Su un submit senza quella classe il
//    comportamento nativo non si attiva. Serve un <button> e non l'<input> che genera `[submit]`,
//    perché su un input gli pseudo-elementi non esistono e l'effetto fill di .btn non parte.
//
// 3. Il CSS di CF7 è caricato SENZA `@layer`, quindi batte qualunque layer dell'autore a
//    prescindere dalla specificità: per raggiungere le sue regole (es. il margin-left sui
//    .wpcf7-list-item) serve `!important`. Vedi dev/css/partial/forms.css.

// Disattiva auto <p> di CF7
add_filter('wpcf7_autop_or_not', '__return_false');

// Ma mantienilo nelle mail
add_filter('wpcf7_mail_html_body', function ($body) {
	$body = wpcf7_autop($body);
	return $body;
});

// Disable native spam filter
add_filter('wpcf7_spam', '__return_false');

// Sostituisce markup checkbox e radio
// L'id porta il prefisso dello unit-tag del form e il valore: senza il prefisso due form nella stessa pagina collidono su `privacy`/`marketing` e la label spunta la casella dell'altro; senza il valore tutti i radio di un gruppo condividono lo stesso id.
// Il `(.*?)` prima di `>` raccoglie gli attributi che CF7 aggiunge dopo il value (aria-invalid, checked): il vecchio `value="(.*?)" \/>` li catturava DENTRO il value.
add_filter('wpcf7_form_elements', function ($content) {
	$form = function_exists('wpcf7_get_current_contact_form') ? wpcf7_get_current_contact_form() : null;
	$prefix = $form ? $form->unit_tag() . '-' : '';

	$content_custom = preg_replace_callback(
		'/<label><input type="(checkbox|radio)" name="(.*?)" value="(.*?)"(.*?)\/?><span class="wpcf7-list-item-label">(.*?)<\/span><\/label>/i',
		function ($matches) use ($prefix) {
			$type = $matches[1] === 'radio' ? 'form-radio' : 'form-checkbox';
			$id = $prefix . sanitize_html_class($matches[2] . '-' . $matches[3]);

			return '<div class="form-toggle"> <input class="' . $type . '" type="' . $matches[1] . '" name="' . $matches[2] . '" value="' . $matches[3] . '"' . $matches[4] . ' id="' . $id . '"> <label class="toggle-label" for="' . $id . '">' . $matches[5] . '</label> </div>';
		},
		$content
	);

	return $content_custom;
});
