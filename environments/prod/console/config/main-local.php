<?php

$dbhost = getenv('MYSQL_HOST') ?: 'localhost';
$dbport = getenv('MYSQL_PORT') ?: '3306';
$dbname = getenv('MYSQL_DBNAME');
$dbuser = getenv('MYSQL_DBUSER') ?: 'root';
$dbpass = getenv('MYSQL_DBPASSWORD');

return [
    'components' => [
        'mysql' => [
            'class' => 'yii\db\Connection',
            'dsn' => "mysql:host={$dbhost};port={$dbport};dbname={$dbname}",
            'username' => $dbuser,
            'password' => $dbpass,
            'charset' => 'utf8',
        ],
    ],
];
