<?php
return [
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzadmin-user',
                    'provider' => [
                        'moduleId' => 'awz.admin',
                        'className' => '\\Awz\\Admin\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzadmin-group',
                    'provider' => [
                        'moduleId' => 'awz.admin',
                        'className' => '\\Awz\\Admin\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];