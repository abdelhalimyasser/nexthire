<?php
declare(strict_types=1);

/**
 * Database Configuration
 * Returns PDO connection parameters for the nexthire database.
 */
return [
           'host'     => getenv('DB_HOST') ?: '127.0.0.1',
           'port'     => getenv('DB_PORT') ?: '3306',
           'dbname'   => getenv('DB_NAME') ?: 'nexthire',
           'username' => getenv('DB_USER') ?: 'root',
           'password' => getenv('DB_PASS') ?: '',
           'charset'  => 'utf8mb4',
           'options'  => [
               PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES   => false,
               PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
           ],
        ];
