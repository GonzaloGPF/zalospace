<?php

// Written by php artisan larecipe:upgrade from your LaRecipe 2 config.
// Content and presentation (versions, theme, navigation) now live next to the
// docs in larecipe.json and navigation.json. The full reference for this file
// is the package's own config/larecipe.php.

return [

    'default' => 'docs',

    'sets' => [
        'docs' => [
            'route' => [
                'prefix' => 'tutorials',
                'domain' => null,
                'middleware' => ['web'],
            ],
            'source' => [
                'driver' => 'repository',
                'path' => base_path('resources/tutorials'),
            ],
            'access' => [
                'mode' => 'public',
            ],
        ],
    ],

    // Public surfaces this site has no use for: keep the attack surface small.
    'mcp' => [
        'web' => ['enabled' => false],
        'local' => false,
    ],

    'feedback' => [
        'enabled' => false,
    ],

];
