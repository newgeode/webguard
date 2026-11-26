# WebGuard - All-in-One Security Middleware for Laravel

**Lightweight Laravel security middleware for protecting applications against suspicious traffic, unwanted requests, automated bots, and common web attacks.**

NewGeode/WebGuard provides a collection of security features in a single Laravel middleware, including IP restrictions, trusted proxy handling, HTTPS enforcement, rate limiting, automatic IP bans, honeypot detection, bot protection, Panic Shield, and input sanitization.

## Features

- **IP Restriction:** Allow or block requests based on IP addresses and CIDR ranges.
- **Trusted Proxies:** Configure trusted proxy addresses for accurate client IP detection.
- **HTTPS Enforcement:** Redirect insecure requests to HTTPS.
- **Trap Routes:** Detect requests to common sensitive paths and automatically ban offending IPs.
- **Rate Limiting:** Apply request limits to authenticated users and guests.
- **Panic Shield:** Detect unusual traffic spikes and temporarily apply stricter guest protection.
- **JavaScript Verification:** Challenge unverified guests during Panic Shield activation.
- **Bot Protection:** Detect empty user agents and block configured automated clients.
- **Honeypot Detection:** Detect automated form submissions using a hidden field.
- **Input Sanitization:** Optionally trim strings, strip null bytes, and remove HTML tags.
- **Security Logging:** Record security events using a dedicated Laravel log channel.
- **Custom Security Responses:** Render package-provided response pages.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, 12, or 13
- Laravel components:
  - `illuminate/support`
  - `illuminate/http`
  - `illuminate/cache`

The package declares compatibility with Laravel 10–13 through its Composer requirements. Development and testing have been performed with Laravel 13.

## Installation

Install the package using Composer:

```bash
composer require newgeode/webguard
```

Laravel package auto-discovery registers the service provider automatically.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=webguard-publish-config
```

This creates:

```text
config/webguard.php
```

## Middleware Setup

### Laravel 11, 12 and 13

Open `bootstrap/app.php` and append WebGuard to the global middleware stack:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use NewGeode\WebGuard\Http\Middleware\WebGuard;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(WebGuard::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();
```

### Laravel 10

Open `app/Http/Kernel.php` and add WebGuard to the global `$middleware` array:

```php
protected $middleware = [
    // Other middleware...

    \NewGeode\WebGuard\Http\Middleware\WebGuard::class,
];
```

### Using the Middleware Alias

WebGuard also registers the `webguard` middleware alias automatically.

You can apply it to individual routes or route groups:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['webguard'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });
});
```

**Important:** If WebGuard is already registered globally, adding the alias to the same routes may cause the middleware to execute more than once. Choose the global or route-specific approach according to your application.

## Configuration

The main configuration file is:

```text
config/webguard.php
```

Default configuration:

```php
return [

    'enabled' => env('WG_ENABLED', true),

    'cache_store' => env('WG_CACHE_STORE', 'file'),

    'log_channel' => env('WG_LOG_CHANNEL', 'webguard'),

    'log_retention_days' => env('WG_LOG_DAYS', 14),

    'except' => [
        'up',
        'api/*',
        'webhooks/*',
        'livewire/*',
    ],

    'trusted_proxies' => [
        'enabled' => true,
        'proxies' => '*',
    ],

    'force_https' => env('WG_FORCE_HTTPS', false),

    'ip_restriction' => [
        'enabled' => false,
        'ip_allowlist' => [],
        'ip_blocklist' => [],
    ],

    'trap_routes' => [
        'enabled' => true,
        'ban_minutes' => 1440,
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

    'rate_limit' => [
        'enabled' => true,
        'guest_per_minute' => 60,
        'auth_per_minute' => 300,
        'ban_guest_on_exceed' => true,
        'ban_minutes' => 10,
    ],

    'panic_shield' => [
        'enabled' => true,
        'spike_rpm_threshold' => 2000,
        'spike_multiplier' => 4,
        'duration_minutes' => 10,
        'panic_guest_rpm' => 20,
        'js_challenge' => [
            'enabled' => true,
            'duration_seconds' => 10,
        ],
    ],

    'bot_protection' => [
        'enabled' => true,
        'block_empty_user_agent' => true,
        'blocked_agents' => [
            'curl/',
            'python-requests',
            'Go-http-client',
            'libwww-perl',
            'wget',
            'PostmanRuntime',
        ],
    ],

    'honeypot' => [
        'enabled' => true,
        'field' => 'webguard_field',
        'ban_minutes' => 10,
    ],

    'sanitize' => [
        'enabled' => false,
        'trim_strings' => true,
        'strip_null_bytes' => true,
        'strip_tags' => true,
        'except_fields' => [
            'password',
            'confirm_password',
            'password_confirmation',
            'current_password',
            'html_body',
        ],
    ],

];
```

## Security Features

### 1. IP Restriction

Control access using IP allowlists and blocklists.

```php
'ip_restriction' => [
    'enabled' => true,
    'ip_allowlist' => [
        '192.168.1.10',
        '10.0.0.0/24',
    ],
    'ip_blocklist' => [
        '203.0.113.50',
        '198.51.100.0/24',
    ],
],
```

- Allowlist entries support individual IP addresses and CIDR ranges.
- Blocklist entries support individual IP addresses and CIDR ranges.
- When an allowlist is configured, only matching addresses are allowed through the IP restriction check.
- Blocked or banned IPs receive an HTTP 403 response.

**Implementation note:** The supplied middleware checks the allowlist before the blocklist. Consequently, an allowlisted IP can bypass a matching blocklist entry. If blocklist precedence is desired, change the evaluation order to check the blocklist first.

### 2. Trusted Proxies

WebGuard can configure Laravel's trusted proxy handling.

```php
'trusted_proxies' => [
    'enabled' => true,
    'proxies' => [
        '10.0.0.10',
        '192.168.1.0/24',
    ],
],
```

For wildcard configuration:

```php
'trusted_proxies' => [
    'enabled' => true,
    'proxies' => '*',
],
```

When `*` is used, the middleware trusts the immediate remote address for the current request.

**Security recommendation:** In production, specify your actual trusted proxy IP addresses or CIDR ranges whenever possible. Ensure your application cannot be reached directly through an untrusted path that would undermine proxy trust.

### 3. Force HTTPS

Automatically redirect insecure requests to HTTPS.

```php
'force_https' => env('WG_FORCE_HTTPS', false),
```

Enable it in your `.env` file:

```env
WG_FORCE_HTTPS=true
```

The middleware uses a permanent HTTP 301 redirect. The redirect is skipped in the local environment.

### 4. Trap Routes

WebGuard monitors requests to commonly targeted files and paths.

Default patterns include:

```php
'paths' => [
    '*.env*',
    'wp-admin*',
    'wp-login.php',
    'xmlrpc.php',
    '*.git*',
    'phpmyadmin*',
    'vendor/phpunit/*',
],
```

When a matching request is detected:

1. The visitor's IP address is temporarily banned.
2. The event is logged.
3. An HTTP 403 response is returned.

Configure the ban duration:

```php
'trap_routes' => [
    'enabled' => true,
    'ban_minutes' => 1440,
],
```

The default duration is 1,440 minutes (24 hours).

### 5. Rate Limiting

WebGuard applies separate rate limits to guests and authenticated users.

```php
'rate_limit' => [
    'enabled' => true,
    'guest_per_minute' => 60,
    'auth_per_minute' => 300,
    'ban_guest_on_exceed' => true,
    'ban_minutes' => 10,
],
```

Default limits:

| Visitor            | Requests per minute |
|--------------------|---------------------|
| Guest              | 60                  |
| Authenticated user | 300                 |


Guests who exceed their configured limit can be automatically banned.

The current implementation uses a 60-second cache counter window. Its expiration is anchored to the first counter creation rather than continuously sliding with each request.

### 6. Panic Shield

Panic Shield monitors global request traffic and activates additional protection when the configured requests-per-minute threshold is exceeded.

```php
'panic_shield' => [
    'enabled' => true,
    'spike_rpm_threshold' => 2000,
    'spike_multiplier' => 4,
    'duration_minutes' => 10,
    'panic_guest_rpm' => 20,
    'js_challenge' => [
        'enabled' => true,
        'duration_seconds' => 10,
    ],
],
```

When Panic Shield activates:

- The activation event is logged.
- The protection remains active for the configured duration.
- Guest requests receive a stricter rate limit.
- Unverified guests can be presented with a JavaScript verification page.

**Current implementation detail:** The supplied middleware activates Panic Shield when the global RPM counter exceeds `spike_rpm_threshold`. The `spike_multiplier` configuration option is present but is not currently used in the detection calculation.

### 7. JavaScript Verification Challenge

During Panic Shield activation, unverified guests may receive a verification page.

The challenge:

1. Displays a connection verification page.
2. Runs a JavaScript countdown.
3. Creates a `webguard_verified` cookie after the countdown.
4. Reloads the page.
5. Allows the middleware to validate the cookie using an HMAC token associated with the visitor's IP address and user agent.

Configure the challenge:

```php
'js_challenge' => [
    'enabled' => true,
    'duration_seconds' => 10,
],
```

The challenge is currently applied to unauthenticated visitors making normal GET requests that do not expect JSON.

The verification cookie currently has a one-hour lifetime.

**Important:** This is a JavaScript execution and delay challenge, not a CAPTCHA or proof of human identity. Automated clients capable of executing JavaScript may complete it.

### 8. Bot Protection

WebGuard checks incoming requests for suspicious user-agent information.

```php
'bot_protection' => [
    'enabled' => true,
    'block_empty_user_agent' => true,
    'blocked_agents' => [
        'curl/',
        'python-requests',
        'Go-http-client',
        'libwww-perl',
        'wget',
        'PostmanRuntime',
    ],
],
```

The current implementation:

- Rejects empty user agents.
- Rejects user agents containing configured blocked-agent strings.
- Requires `Accept` and `Accept-Language` headers.

Requests identified as suspicious receive HTTP 403.

**Implementation note:** The current middleware rejects empty user agents regardless of the `block_empty_user_agent` setting. The setting is present in configuration but is not yet read by the middleware.

Some legitimate integrations and API clients may be affected. Use route exclusions and test your application integrations before enabling this protection in production.

### 9. Honeypot Protection

WebGuard supports a hidden form field for detecting automated submissions.

```php
'honeypot' => [
    'enabled' => true,
    'field' => 'webguard_field',
    'ban_minutes' => 10,
],
```

Add the configured field to your form:

```html
<input
    type="text"
    name="webguard_field"
    value=""
    autocomplete="off"
    tabindex="-1"
    style="position:absolute;left:-9999px;"
>
```

Normal users should leave the field empty.

When an unauthenticated visitor submits a POST request with the honeypot field filled:

1. The IP address is temporarily banned.
2. The event is logged.
3. An HTTP 403 response is returned.

The honeypot check applies to unauthenticated POST requests.

### 10. Input Sanitization

WebGuard can optionally sanitize incoming request input.

```php
'sanitize' => [
    'enabled' => false,
    'trim_strings' => true,
    'strip_null_bytes' => true,
    'strip_tags' => true,
    'except_fields' => [
        'password',
        'confirm_password',
        'password_confirmation',
        'current_password',
        'html_body',
    ],
],
```

When enabled, the middleware can:

- Trim leading and trailing whitespace.
- Remove null bytes.
- Strip HTML tags.
- Exclude specified fields from sanitization.

Sanitization is disabled by default.

**Important:** Input sanitization is not a replacement for output escaping, validation, authorization, or context-aware SQL protection. Applications that accept HTML content should carefully configure their excluded fields and apply an appropriate HTML sanitization strategy.

## Excluded Routes

WebGuard supports route exclusions through the `except` configuration option.

Default exclusions:

```php
'except' => [
    'up',
    'api/*',
    'webhooks/*',
    'livewire/*',
],
```

Excluded routes skip the remaining WebGuard security checks after trusted proxy handling and HTTPS enforcement.

This means excluded routes do not receive WebGuard's IP restriction, trap-route, rate-limit, bot-protection, honeypot, or sanitization checks.

Review the exclusions carefully. In particular, excluding all API or webhook routes means those routes will not receive WebGuard's protections.

## Security Response Pages

WebGuard loads its Blade views using the `WGResourcesViews` namespace.

The middleware references the following views:

| View                 | Purpose                           | HTTP status |
|----------------------|-----------------------------------|-------------|
| `access-denied`      | Trap route detection              | 403         |
| `user-banned`        | Banned or blocked visitor         | 403         |
| `suspicious-traffic` | Suspicious client detected        | 403         |
| `too-many-requests`  | Rate limit exceeded               | 429         |
| `spam-detected`      | Honeypot submission detected      | 403         |
| `verify-connection`  | JavaScript verification challenge | 202         |

The verification page receives the following data from the middleware:

- `$status`: HTTP status displayed on the page.
- `$duration`: Verification countdown duration in seconds.
- `$token`: HMAC token used for the verification cookie.

The other response pages receive the status value.

The middleware attempts to render the configured package view. If rendering throws an exception, it falls back to an empty HTML response with the intended HTTP status.

## Logging

WebGuard automatically registers a dedicated `webguard` daily log channel if one does not already exist.

Default channel configuration:

```php
[
    'driver' => 'daily',
    'path' => storage_path('logs/webguard.log'),
    'level' => 'debug',
    'days' => 14,
]
```

Security events can include:

- Trap route hits.
- Automatic IP bans.
- Rate-limit violations.
- Panic Shield activation.
- Honeypot detections.

Configure the logging channel in `config/webguard.php`:

```php
'log_channel' => env('WG_LOG_CHANNEL', 'webguard'),
```

Environment variables:

```env
WG_LOG_CHANNEL=webguard
WG_LOG_DAYS=14
```

**Note:** The current service provider uses a fixed `days` value of 14 when it creates the channel. The `WG_LOG_DAYS` setting is declared in configuration but is not currently connected to the channel registration code.

## Environment Variables

The package supports the following environment variables:

```env
WG_ENABLED=true
WG_CACHE_STORE=file
WG_LOG_CHANNEL=webguard
WG_LOG_DAYS=14
WG_FORCE_HTTPS=false
```

| Variable         | Default    | Description                   |
|------------------|------------|-------------------------------|
| `WG_ENABLED`     | `true`     | Enables or disables WebGuard  |
| `WG_CACHE_STORE` | `file`     | Configured cache store        |
| `WG_LOG_CHANNEL` | `webguard` | Security logging channel      |
| `WG_LOG_DAYS`    | `14`       | Intended log retention period |
| `WG_FORCE_HTTPS` | `false`    | Enables HTTPS enforcement     |

The cache store must be configured appropriately in your Laravel application. Supported cache backends described by the package configuration include file, database, Redis, and Memcached.

## Recommended Production Configuration

A production configuration might look like this:

```php
return [
    'enabled' => true,
    'cache_store' => 'redis',
    'log_channel' => 'webguard',
    'log_retention_days' => 14,

    'except' => [
        'up',
        'api/*',
        'webhooks/*',
        'livewire/*',
    ],

    'trusted_proxies' => [
        'enabled' => true,
        'proxies' => [
            '10.0.0.10',
        ],
    ],

    'force_https' => true,

    'ip_restriction' => [
        'enabled' => false,
        'ip_allowlist' => [],
        'ip_blocklist' => [],
    ],

    'trap_routes' => [
        'enabled' => true,
        'ban_minutes' => 1440,
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

    'rate_limit' => [
        'enabled' => true,
        'guest_per_minute' => 60,
        'auth_per_minute' => 300,
        'ban_guest_on_exceed' => true,
        'ban_minutes' => 10,
    ],

    'panic_shield' => [
        'enabled' => true,
        'spike_rpm_threshold' => 2000,
        'spike_multiplier' => 4,
        'duration_minutes' => 10,
        'panic_guest_rpm' => 20,
        'js_challenge' => [
            'enabled' => true,
            'duration_seconds' => 10,
        ],
    ],

    'bot_protection' => [
        'enabled' => true,
        'block_empty_user_agent' => true,
        'blocked_agents' => [
            'curl/',
            'python-requests',
            'Go-http-client',
            'libwww-perl',
            'wget',
            'PostmanRuntime',
        ],
    ],

    'honeypot' => [
        'enabled' => true,
        'field' => 'webguard_field',
        'ban_minutes' => 10,
    ],

    'sanitize' => [
        'enabled' => false,
        'trim_strings' => true,
        'strip_null_bytes' => true,
        'strip_tags' => true,
        'except_fields' => [
            'password',
            'confirm_password',
            'password_confirmation',
            'current_password',
            'html_body',
        ],
    ],
];
```

Replace the trusted proxy address with the actual address of your infrastructure. Do not copy the example IP blindly.

## Performance and Cache

WebGuard uses Laravel's cache system to track request counts, temporary bans, and Panic Shield state.

For production applications with significant traffic, Redis is a recommended cache backend.

Example:

```env
WG_CACHE_STORE=redis
```

Make sure the Laravel cache configuration and Redis connection are correctly configured.

**Concurrency consideration:** The supplied `NGStore` implementation initializes counters using separate cache operations (`has`, `put`, and `increment`). Under concurrent traffic, initial counter updates can race. Consider replacing this with atomic cache operations before relying on accurate counters under heavy load.

## Troubleshooting

### Configuration changes are not taking effect

Clear Laravel's configuration cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### WebGuard is blocking API requests

Check the `except` configuration and confirm whether the API path matches an excluded pattern.

Remember that excluded routes bypass WebGuard's remaining security checks.

### Verification page keeps reloading

Check that:

- JavaScript is enabled in the browser.
- Cookies are enabled.
- The browser accepts the `webguard_verified` cookie.
- The visitor's IP address and user agent remain consistent during verification.
- The application is using the expected `APP_KEY`.

### Security logs are missing

Check that:

- The `webguard` logging channel is registered.
- The configured log path is writable.
- The Laravel logging configuration is correct.
- The configured `WG_LOG_CHANNEL` exists.

### Requests are being incorrectly classified as bots

Review the blocked user-agent list and ensure legitimate integrations are not being blocked. Also check whether required `Accept` and `Accept-Language` headers are present.

## Production Readiness Notes

Before deploying WebGuard to a production application, review these implementation details:

1. Check IP blocklist precedence over the allowlist.
2. Implement or remove the unused `spike_multiplier` option.
3. Make `block_empty_user_agent` control the empty user-agent check.
4. Connect `log_retention_days` to the logging channel configuration.
5. Use atomic cache operations for counter initialization and updates.
6. Consider making the verification cookie lifetime configurable.
7. Set the verification cookie's `Secure` attribute on HTTPS requests.
8. Review trusted proxy configuration for your hosting environment.
9. Test middleware ordering, especially when authentication middleware runs after global middleware.
10. Test API, webhook, and Livewire routes to ensure exclusions match your intended security policy.

WebGuard is a security middleware layer, not a replacement for Laravel's built-in authentication, authorization, validation, CSRF protection, or secure application development practices.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Author

**New Geode**

Package: `newgeode/webguard`
