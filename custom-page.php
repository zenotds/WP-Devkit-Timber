<?php
/*
 * Template Name: Custom
 * Description: Custom
 */

$context = Timber::context();
$context['post'] = Timber::get_post();

$templates = array('page-custom.twig');

Timber::render($templates, $context);
