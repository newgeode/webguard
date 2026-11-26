<?php

return [

    'enabled' => env('WG_ENABLED', false),

    /*
     * Use 'redis' or 'memcached' for multi-server/high-traffic setups.
     * Defaults to 'file' so it works out-of-the-box on without DB load.
     */
    'cache_store' => env('WG_CACHE_STORE', 'file'),


    /*
     * Logging
     * Configure logging for WebGuard events.
     */
    'log_channel' => env('WG_LOG_CHANNEL', 'webguard'),


    /*
     * Log Retention
     * Number of days to retain WebGuard logs before automatic deletion.
     */
    'log_retention_days' => env('WG_LOG_DAYS', 14),


    /*
     * Route Exclusions
     * Routes that match these patterns will be excluded from WebGuard checks.
     */
    'except' => [
        'up',
        'api/*',
        'webhooks/*',
        'livewire/*',
    ],

    /*
     * Trusted Proxies
     */
    'trusted_proxies' => [

        'enabled' => false,

        /*
         * List of trusted proxy IP addresses or CIDR ranges.
         */
        'proxies' => '*',
    ],

    
    /*
     * HTTPS Enforcement
     * Make sure to configure your web server and SSL certificates properly before enabling this option.
     * Not working on localhost without proper SSL setup, so use with caution in development environments.
     */
    'force_https' => env('WG_FORCE_HTTPS', false),


    /*
     * IP Restriction
     * Allowlist and blocklist for IP addresses.
     */
    'ip_restriction' => [

        'enabled' => false,

        /*
         * List of IP addresses or CIDR ranges that are allowed to access the application.
         * If this list is not empty, only these IPs will be allowed.
         */
        'ip_allowlist' => [
            // '127.0.0.1',
        ],

        /*
         * List of IP addresses or CIDR ranges that are blocked from accessing the application.
         * If an IP is in this list, it will be denied access regardless of the allowlist.
         */
        'ip_blocklist' => [
            // Static permanently blocked IPs or CIDRs
        ],
    ],

    
    /*
     * Trap Routes
     * Routes that match these patterns will be treated as honeypots and trigger a ban if accessed.
     */
    'trap_routes' => [

        'enabled' => false,

        'ban_minutes' => 1440, // 24 hours

        /*
         * List of route patterns to treat as traps.
         * Use wildcards (*) to match multiple routes.
         */
        'paths' => [
            '*.env*',
            'wp-admin*',
            'wp-login.php',
            'xmlrpc.php',
            '*.git*',
            'phpmyadmin*',
            'vendor/phpunit/*',
        ],
    ],


    /*
     * Rate Limiting
     * Configure rate limits for guests and authenticated users.
     */
    'rate_limit' => [

        'enabled' => false,

        /*
         * Guests typically have lower limits to prevent abuse.
         */
        'guest_per_minute' => 60,

        /*
         * Authenticated users typically have higher limits,
         * especially for SaaS applications where users may be auto-saving or editing content frequently.
         */
        'auth_per_minute' => 300,

        /*
         * If true, guests will be banned when they exceed the rate limit.
         * Authenticated users will not be auto-banned, but their requests will be throttled.
         */
        'ban_guest_on_exceed' => true,

        /*
         * Duration of the ban in minutes for guests who exceed the rate limit.
         */
        'ban_minutes' => 10,
    ],


    /*
     * Panic Shield
     * Automatically triggers stricter rate limits and JS challenges during traffic spikes.
     */
    'panic_shield' => [

        'enabled' => false,
        
        /*
         * Spike detection thresholds.
         * If the current requests per minute (RPM) exceeds either of these thresholds,
         * the panic shield will activate stricter limits and JS challenges.
         */
        'spike_rpm_threshold' => 2000, // If current RPM exceeds 2000, activate panic shield

        /*
         * Duration of the panic shield in minutes.
         * After this duration, the system will automatically recover to normal limits.
         */
        'duration_minutes' => 10, // Auto-recover after 10 minutes

        /*
         * Stricter limits during panic shield activation.
         * These limits apply to unverified guests during a traffic spike.
         */
        'panic_guest_rpm' => 20, // Strict limit for unverified guests during spike


        /*
         * Enable a 10-second JavaScript challenge for unverified guests during panic shield activation.
         * This helps to filter out bots and automated traffic.
         */
        'js_challenge' => [

            'enabled' => true,

            /*
             * Duration of the JavaScript challenge in seconds.
             * Guests must complete the challenge within this time frame to continue accessing the site.
             * Default 10 seconds for JS challenge
             */
            'duration_seconds' => 10,
        ],
    ],


    /*
     * Bot & Form Protection
     * Protects against bots and automated form submissions.
     */
    'bot_protection' => [

        'enabled' => false,

        /*
         * Block requests with empty User-Agent headers.
         * Many bots and scrapers do not set a User-Agent, so this is a simple way to block them.
         */
        'block_empty_user_agent' => true,

        /*
         * List of known bot user agents to block.
         * This is not exhaustive, but covers common bots and scrapers.
         */
        'blocked_agents' => [
            'curl/',
            'python-requests',
            'Go-http-client',
            'libwww-perl',
            'wget',
        ],

        /*
         * Honeypot field for form protection.
         * 
         * This field is used to detect and block automated form submissions.
         * You can customize the field name to something unique for your application.
         * Make sure to add this field to your forms and hide it with CSS or JavaScript.
         * Bots will typically fill out all fields, including hidden ones, which will trigger the honeypot protection.
         * If a bot fills out this field, it will be considered a bot and will be blocked from submitting the form.
         * You can also use this field to log bot activity for further analysis.
         * Note: This field should not be used for any legitimate user input, as it is intended solely for bot detection.
         * If you need to collect user input, use a separate field with a different name that is not part of the honeypot protection.
         */
        'honeypot_field' => 'webguard_field',
    ],


    /*
     * Input Sanitization
     * Sanitizes user input to prevent XSS and other injection attacks.
     */
    'sanitize' => [

        'enabled' => false,

        /*
         * Trim whitespace from input strings.
         */
        'trim_strings' => true,

        /*
         * Strip null bytes from input strings.
         */
        'strip_null_bytes' => true,

        /*
         * Strip HTML tags from input strings.
         */
        'strip_tags' => true,

        /*
         * List of input fields to exclude from sanitization.
         * These fields will not be trimmed, stripped of null bytes, or stripped of HTML tags.
         */
        'except_fields' => [
            'password',
            'confirm_password',
            'password_confirmation',
            'current_password',
            'html_body',
        ],
    ],
];