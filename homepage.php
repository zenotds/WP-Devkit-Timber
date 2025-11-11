<?php
/*
 * Template Name: Homepage
 * Description: Homepage
 */

$context = Timber::context();
$context['post'] = Timber::get_post();

$templates = array('homepage.twig');

Timber::render($templates, $context);
