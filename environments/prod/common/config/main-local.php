<?php

$dbhost = getenv('PGSQL_HOST') ?: 'localhost';
$dbport = getenv('PGSQL_PORT') ?: '5432';
$dbname = getenv('PGSQL_DBNAME');
$dbuser = getenv('PGSQL_DBUSER') ?: 'postgres';
$dbpass = getenv('PGSQL_DBPASSWORD');

$rdhost = getenv('REDIS_HOST') ?: 'localhost';
$rdport = getenv('REDIS_PORT') ?: '6379';
$rdname = getenv('REDIS_DBNAME') ?: 0;

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "pgsql:host={$dbhost};port={$dbport};dbname={$dbname}",
            'username' => $dbuser,
            'password' => $dbpass,
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => $rdhost,
            'port' => $rdport,
            'database' => $rdname,
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
    ],
];
