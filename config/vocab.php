<?php

return [
    'routing' => [
        'mode' => env('VOCAB_MODE', 'path'),
        'prefix' => 'vocab',
    ],

    'guard' => 'web',

    'navigation' => [
        'route' => 'vocab.dashboard',
        'icon'  => 'heroicon-o-language',
        'order' => 80,
    ],

    'sidebar' => [
        [
            'group' => 'Vokabeln',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'vocab.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
                [
                    'label' => 'Listen',
                    'route' => 'vocab.lists.index',
                    'icon'  => 'heroicon-o-list-bullet',
                ],
            ],
        ],
    ],
];
