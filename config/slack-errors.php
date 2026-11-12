<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for sending exceptions to Slack.
    |
    */

    'enabled' => (bool) env('SLACK_ERRORS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | The incoming webhook URL of the Slack channel. Nothing is sent while
    | it is empty.
    |
    */

    'webhook_url' => (string) env('SLACK_ERRORS_WEBHOOK_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | Comma separated list of environments that send notifications.
    | Wildcards are supported, so "*" sends from every environment.
    |
    */

    'environments' => array_values(array_filter(array_map(
        mb_trim(...),
        explode(',', (string) env('SLACK_ERRORS_ENVIRONMENTS', 'production')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Mention
    |--------------------------------------------------------------------------
    |
    | Optional mention prepended to every message, e.g. "<!channel>",
    | "<!here>" or a user mention like "<@U012AB3CD>".
    |
    */

    'mention' => '',

    /*
    |--------------------------------------------------------------------------
    | Icon
    |--------------------------------------------------------------------------
    |
    | Emoji shown in front of the app name in every message. Leave it empty
    | for no icon.
    |
    */

    'icon' => '🙈',

    /*
    |--------------------------------------------------------------------------
    | Color
    |--------------------------------------------------------------------------
    |
    | Color of the bar on the left of every message, as a hex code.
    |
    */

    'color' => '#ECB22E',

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for Slack. Keep it short, the request is sent while
    | the application handles the exception.
    |
    */

    'timeout' => 3,

    /*
    |--------------------------------------------------------------------------
    | Throttle
    |--------------------------------------------------------------------------
    |
    | Minutes during which the same exception (its class and where it comes
    | from in your code) is sent only once, so an error in a loop never floods
    | the channel. 0 disables it.
    |
    */

    'throttle' => 10,

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Cache store used by the throttle. The local "file" store keeps working
    | when Redis or the database is down, which is exactly when errors pile
    | up. An empty value uses the application's default store.
    |
    */

    'cache_store' => (string) env('SLACK_ERRORS_CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | Exception classes (and their subclasses) never sent to Slack. The
    | application's own "dontReport" list is respected as well.
    |
    */

    'ignore' => [
        // Illuminate\Validation\ValidationException::class,
    ],

];
