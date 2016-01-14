<?php
return [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
        ],
        'urlManager' => [
            'enablePrettyUrl' => false,
            //'showScriptName' => true,
            'rules' => [
                'announce.php' => 'site/about',
            ],
        ],
    ],
];
