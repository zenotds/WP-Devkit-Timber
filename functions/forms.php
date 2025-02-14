<?php

// Disattiva auto <p> di CF7
add_filter('wpcf7_autop_or_not', '__return_false');

// Ma mantienilo nelle mail
add_filter('wpcf7_mail_html_body', function ($body) {
    $body = wpcf7_autop($body);
    return $body;
});

// Disable native spam filter
add_filter('wpcf7_spam', '__return_false');

// Sostituisce markup checkbox con quelli di Bootstrap 5
add_filter('wpcf7_form_elements', function ($content) {

    // BS5
    $content_bs5 = preg_replace('/<label><input type="(checkbox|radio)" name="(.*?)" value="(.*?)" \/><span class="wpcf7-list-item-label">/i', '<label class="form-check"><input class="form-check-input" type="\1" name="\2" value="\3" id="\2"><span class="form-check-label wpcf7-list-item-label" for="\2">', $content);

    return $content_bs5;
});