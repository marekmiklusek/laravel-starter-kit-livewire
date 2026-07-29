<?php

declare(strict_types=1);

use MarekMiklusek\DatabaseBackup\Events\BackupFailed;
use MarekMiklusek\DatabaseBackup\Events\BackupCreated;

return [

    /*
    |--------------------------------------------------------------------------
    | Database Connection / SSL Configuration
    |--------------------------------------------------------------------------
    |
    | mysqldump can be stricter about TLS than the app's PDO connection.
    | If your DB server forces TLS (common after moving to a managed host),
    | mysqldump may fail with:
    |   "TLS/SSL error: Certificate verification failure: ... NOT trusted"
    |
    | Set the SSL mode below (usually via the MYSQL_SSL_MODE env var):
    |
    | - ssl_mode:
    |     null       -> no SSL options passed (default, backwards compatible)
    |     'REQUIRED' -> force encrypted connection WITHOUT certificate verify
    |                   (use when you don't have the server's CA cert)
    |     'VERIFY_CA' / 'VERIFY_IDENTITY' -> require + verify (needs ssl_ca)
    |
    | - ssl_ca: absolute path to the server's CA certificate (.pem).
    |           Leave null if you don't have it.
    |
    | - dump_client: which dump client dialect to emit SSL options for.
    |     'mysql'   -> uses ssl-mode=... (MySQL 5.7+/8)
    |     'mariadb' -> uses ssl + ssl-verify-server-cert (MariaDB / mariadb-dump)
    |   MariaDB servers reject `ssl-mode` with "unknown variable 'ssl-mode'",
    |   so set this to 'mariadb' when your server runs MariaDB.
    |
    */

    'database' => [
        'ssl_mode' => env('MYSQL_SSL_MODE'),
        'ssl_ca' => env('MYSQL_ATTR_SSL_CA'),
        'dump_client' => env('DB_BACKUP_DUMP_CLIENT', 'mysql'),

        // Dump binary to invoke. Leave null to derive it from dump_client
        // ('mariadb' -> mariadb-dump, else mysqldump). Set an explicit path
        // or name to override, e.g. '/usr/bin/mariadb-dump'.
        'dump_binary' => env('DB_BACKUP_DUMP_BINARY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure storage settings for database backup files:
    |
    | - disk: Choose between 'local' or 'google' storage driver. For Google Drive,
    |         you can use any custom disk name defined in config/filesystems.php
    | - use_both_disks: If true, saves backups to both local and Google Drive
    | - directory: Custom local directory name where backups will be stored
    | - filename: Customizable naming pattern for backup files
    |   - prefix: Usually database name (defaults to Laravel env value)
    |   - date_format: Timestamp format for unique filenames
    |
    | Example filename: laravel_2025-01-10_14-30-00.sql
    |
    */

    'storage' => [
        'disk' => 'local',

        'use_both_disks' => false,

        'directory' => 'database-backups',

        'filename' => [
            'prefix' => env('DB_DATABASE', 'laravel'),
            'date_format' => 'Y-m-d_H-i-s',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cleanup of old backup files:
    |
    | - days_to_keep: Number of days to retain backup files
    |                 Set to 0 to keep backups indefinitely
    |                 Files older than this will be permanently deleted
    |
    | - automatic: Enable/disable automatic cleanup after backups
    |                 true: Run cleanup after each backup
    |                 false: Manual cleanup only via command
    |
    | Usage:
    | - Automatic: Runs with new backups if 'automatic' is true
    | - Manual: php artisan db-backup:cleanup
    |
    */

    'cleanup' => [
        'days_to_keep' => 14,
        'automatic' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email notifications for backup and cleanup operations:
    |
    | - mail: Email configuration settings
    |   - to: Recipient email address for notifications
    |   - from: Sender details pulled from environment variables
    |          Configure MAIL_FROM_ADDRESS and MAIL_FROM_NAME in .env
    |
    | - events: Toggle notifications for specific events
    |   - backup_successful: Notify when backup completes successfully
    |   - backup_failed: Notify when backup operation fails
    |   - cleanup_successful: Notify when cleanup completes successfully
    |   - cleanup_failed: Notify when cleanup operation fails
    |
    | Note: Ensure your mail configuration is properly set in .env file:
    |       MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
    |
    */

    'notifications' => [
        'mail' => [
            'to' => 'your@email.com',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'events' => [
            'backup_successful' => false,
            'backup_failed' => false,
            'cleanup_successful' => false,
            'cleanup_failed' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Information
    |--------------------------------------------------------------------------
    |
    | Available events that you can listen to in your application:
    |
    | 1. BackupCreated
    |    - Triggered after successful backup creation
    |
    | 2. BackupFailed
    |    - Triggered when backup operation encounters an error
    |
    */

    'events' => [
        BackupCreated::class,
        BackupFailed::class,
    ],
];
