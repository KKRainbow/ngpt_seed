<?php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'mysql' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;port=3306;dbname=migratengpt',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
    ],
];
