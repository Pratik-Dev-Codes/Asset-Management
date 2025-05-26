<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Postmark
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Postmark service for sending transactional emails.
    |
    */
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
        'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
        'client' => [
            'timeout' => (int) env('POSTMARK_TIMEOUT', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SES
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Amazon SES service for sending transactional emails.
    |
    */
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'options' => [
            'ConfigurationSetName' => env('AWS_SES_CONFIGURATION_SET'),
            'SourceArn' => env('AWS_SES_SOURCE_ARN'),
            'ReturnPathArn' => env('AWS_SES_RETURN_PATH_ARN'),
            'SendingPoolUpdate' => env('AWS_SES_SENDING_POOL_UPDATE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Resend service for sending transactional emails.
    |
    */
    'resend' => [
        'key' => env('RESEND_KEY'),
        'base_uri' => env('RESEND_BASE_URI', 'https://api.resend.com'),
        'timeout' => (int) env('RESEND_TIMEOUT', 10),
        'retry_times' => (int) env('RESEND_RETRY_TIMES', 3),
        'retry_sleep' => (int) env('RESEND_RETRY_SLEEP', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Slack notification channel for your application.
    |
    */
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL', '#general'),
            'username' => env('SLACK_BOT_USER_NAME', 'Laravel'),
            'icon' => env('SLACK_BOT_USER_ICON', ':robot_face:'),
            'link_names' => (bool) env('SLACK_LINK_NAMES', true),
            'unfurl_links' => (bool) env('SLACK_UNFURL_LINKS', false),
            'unfurl_media' => (bool) env('SLACK_UNFURL_MEDIA', true),
            'allow_markdown' => (bool) env('SLACK_ALLOW_MARKDOWN', true),
            'markdown_in_attachments' => array_filter(explode(',', env('SLACK_MARKDOWN_IN_ATTACHMENTS', 'pretext,text,fields'))),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Stripe service for processing payments.
    |
    */
    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub
    |--------------------------------------------------------------------------
    |
    | Here you may configure the GitHub service for OAuth authentication.
    |
    */
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Google service for OAuth authentication.
    |
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Facebook service for OAuth authentication.
    |
    */
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twitter (X)
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Twitter service for OAuth authentication.
    |
    */
    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('TWITTER_REDIRECT_URI'),
        'oauth' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | LinkedIn
    |--------------------------------------------------------------------------
    |
    | Here you may configure the LinkedIn service for OAuth authentication.
    |
    */
    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI'),
    ],

];
