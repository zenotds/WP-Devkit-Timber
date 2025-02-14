# Zeno's WP DevKit | Timber Edition
This is a version of my WP Devkit 4.0 that includes a customized and improved version of the Timber Starter Theme.


## Stack and technologies
- This theme uses Timber Starter Theme 2.0
- Styling is mainly done with Tailwind 4.0
- Useful other NPM Packages are included, check the packages.json for details


## Timber Starter Site improvements
- "views" renamed to "templates"
- "src" renamed to "functions"
- various function partials to deal with: forms, acf, twig, menus, options (not mandatory)


## Suggested WP plugins
Some custom functions are based on the usage of these plugins
- ACF Pro (custom fields)
- Yoast SEO (SEO)
- WPML (for multilanguage site)
- WPRocket (advanced cache)
- CF7 (forms)


## Basic Structure for build automation

Structure the theme as you see fit but keep the structure for these 2 folders:

- assets -> compiled files
  - js -> compiled scripts
  - css -> compiled styles

- dev -> source files
  - js -> source scripts
  - css -> source styles


## Instructions

1. Run `npm install` to install all dependencies
2. Change line 162 inside `esbuild.js` to your dev domain


## Commands

`npm run watch` to watch for changes and build assets
`npm run build` to build assets
`npm run bs5` to build Bootstrap from dev/css/bs5/bs5.source (SASS)


## Composer

1. Run `composer install` to install Timber and other dependencies


## Build notes

- Fonts (woff, woff2, ttf, eot) and other static assets are ignored and not processed during runtime, keep these files and reference them relative to the /assets/ folder


## Notes

- Avoid images in css, there are better ways :P
- The starter comes with some useful JS packages such as gLightbox, Swiper, Plyr, etc.
- Feel free to edit whatever you want as you see fit.

## Changelog

v1.0 - First version