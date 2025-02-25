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

// Sostituisce markup checkbox e radio
add_filter('wpcf7_form_elements', function ($content) {
    $content_custom = preg_replace_callback(
        '/<label><input type="(checkbox|radio)" name="(.*?)" value="(.*?)" \/><span class="wpcf7-list-item-label">(.*?)<\/span><\/label>/i',
        function ($matches) {
            $type = $matches[1] === 'radio' ? 'form-radio' : 'form-checkbox';

            return '<div class="form-toggle">
                        <input class="' . $type . '" type="' . $matches[1] . '" name="' . $matches[2] . '" value="' . $matches[3] . '" id="' . $matches[2] . '">
                        <label class="toggle-label" for="' . $matches[2] . '">' . $matches[4] . '</label>
                    </div>';
        },
        $content
    );

    return $content_custom;
});