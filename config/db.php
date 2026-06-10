<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'phd_feed') . ';charset=utf8mb4',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'tablePrefix' => '',
    'enableSchemaCache' => false,
];
