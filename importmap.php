<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'admin-recipe-illustrations' => [
        'path' => './assets/admin-recipe-illustrations.js',
        'entrypoint' => false,
    ],
    'table-search' => [
        'path' => './assets/table-search.js',
        'entrypoint' => false,
    ],
    'password-validator' => [
        'path' => './assets/password-validator.js',
        'entrypoint' => false,
    ],
    'password-toggle' => [
        'path' => './assets/password-toggle.js',
        'entrypoint' => false,
    ],
    'confirmation-dialog' => [
        'path' => './assets/confirmation-dialog.js',
        'entrypoint' => false,
    ],
    'admin-dashboard-styles' => [
        'path' => './assets/styles/admin/dashboard.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin-themes-list-styles' => [
        'path' => './assets/styles/admin/themes-list.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin-opening-schedule-styles' => [
        'path' => './assets/styles/admin/opening-schedule.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin-menu-form-styles' => [
        'path' => './assets/styles/admin/menu-form.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin-menus-list-styles' => [
        'path' => './assets/styles/admin/menus-list.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin-reviews-styles' => [
        'path' => './assets/styles/admin/reviews.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
];
