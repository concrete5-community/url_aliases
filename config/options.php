<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

return [
    'middleware' => [
        'priority' => PHP_INT_MAX,
    ],
    'log404' => [
        'enabled' => false,
        // Don't log 404s if their request path matches any of the following regular expressions (one per line). If null we'll use excludePathRXDefault
        'excludePathRX' => [
            'custom' => '',
            'useDefault' => true,
        ],
        // true: all, false: none, string: one querystring parameter per line
        'logQueryString' => 'cID',
        // Max age in seconds before deleting logs (0: unlimited)
        'entryMaxAge' => 604800,
        'pageSize' => 50,
    ],
];
