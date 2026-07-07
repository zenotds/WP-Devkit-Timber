<?php
// Modulo Posts — estrazione articoli: source = latest | category | manual.
// Installazione: copia la funzione in functions/logic.php e registra la Twig
// function in functions/twig.php dentro extend_twig():
//   $twig->addFunction(new Twig\TwigFunction('module_posts', 'theme_module_posts'));
// Per CPT di progetto, duplica con prefisso progetto e adatta post_type/tassonomia.

function theme_module_posts($content)
{
    $source = $content['source'] ?? 'latest';

    if ($source === 'manual') {
        $ids = $content['posts'] ?? [];
        if (!$ids) {
            return [];
        }
        return Timber::get_posts([
            'post_type'      => 'post',
            'post__in'       => $ids,
            'orderby'        => 'post__in',
            'posts_per_page' => -1,
        ])->to_array();
    }

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => $content['limit'] ?? 3,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ($source === 'category' && !empty($content['category'])) {
        $cat = $content['category'];
        $tid = is_object($cat) ? $cat->term_id : (is_array($cat) ? reset($cat) : $cat);
        $args['tax_query'] = [[
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $tid,
        ]];
    }

    return Timber::get_posts($args)->to_array();
}
