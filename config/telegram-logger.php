<?php

declare(strict_types=1);

return [

    /**
     * Telegram Logger Configuration
     *
     * This configuration file contains settings for the Telegram Logger package.
     *
     * Configuration options:
     * - bot_token: Your Telegram Bot API token (set in .env as TELEGRAM_BOT_TOKEN)
     * - chat_id: The Telegram chat ID where logs will be sent (set in .env as TELEGRAM_CHAT_ID)
     * - level: Minimum log level threshold (set in .env as TELEGRAM_LOG_LEVEL)
     *          If set to 'error', only error, critical, alert, and emergency will be logged
     *          If set to 'debug', all levels will be logged
     * - silent_notification: When true, notifications will be sent without sound
     * - is_enabled: When false, the logger will be disabled (set in .env as TELEGRAM_LOGGER_ENABLED)
     *
     * @see https://core.telegram.org/bots/api
     */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'level' => 'error',
    'silent_notification' => false,
    'is_enabled' => env('TELEGRAM_LOGGER_ENABLED', true),

];
