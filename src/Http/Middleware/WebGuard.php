<?php

namespace NewGeode\WebGuard\Http\Middleware;


use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\IpUtils;

use Closure;
use Exception;
use NewGeode\WebGuard\Support\NGStore;

class WebGuard
{
    // Dependency injection of NGStore for caching and rate limiting
    public function __construct(protected NGStore $store){ }


    // Handle an incoming request.
    public function handle(Request $request, Closure $next): Response
    {

        // 1. Check if WebGuard is enabled
        if(config('webguard.enabled') == false){ return $next($request); }

        // 2. Apply Trusted Proxies Configuration
        $this->applyTrustedProxies($request);

        // 3. Enforce HTTPS if configured
        if(config('webguard.force_https') == true){
            if(!$request->secure() && !app()->environment('local')){
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        // 4. Check for excluded routes
        if($this->isExcludedRoute($request)){
            return $next($request);
        }

        $ip = $request->ip();

        // 5. IP Restriction Check
        if(config('webguard.ip_restriction.enabled') == true){

            // Check if the IP is in the allowlist
            if($this->ipMatchList($ip, config('webguard.ip_restriction.ip_allowlist', []))){
                return $this->sanitizeAndContinue($request, $next);
            }
            
            // Check if the IP is in the blocklist or banned
            if($this->ipMatchList($ip, config('webguard.ip_restriction.ip_blocklist', [])) || $this->store->isBanned($ip)){
                return $this->renderPages('user-banned', 403, ['status' => 403]);
            }
        }

        // 6. Check for trap routes and ban if accessed
        if($this->hitsTrapRoute($request)){
            $banMinutes = config('webguard.trap_routes.ban_minutes', 1440);

            $this->store->banIp($ip, $banMinutes);
            $this->logEvent($request, 'trap_route_hit', "Auto-banned for {$banMinutes}m");

            return $this->renderPages('access-denied', 403, ['status' => 403]);
        }

        // 7. Track global traffic and detect spikes for Panic Shield
        $this->trackGlobalTrafficAndDetectSpike();

        $user = $request->user();

        // 8. Panic Shield Mode: If active, apply additional checks for unauthenticated users
        if($this->store->isPanicActive() && !$user && !$this->hasValidTrustCookie($request)){
            if(config('webguard.panic_shield.js_challenge.enabled') == true){
                if($request->isMethod('GET') && !$request->expectsJson()){
                    return $this->serveJsChallenge($request);
                }
            }
        }

        // 9. Basic bot detection: Check for suspicious traffic patterns
        if(config('webguard.bot_protection.enabled') == true){
            if(!$this->looksLikeRealBrowser($request)){
                return $this->renderPages('suspicious-traffic', 403, ['status' => 403]);
            }
        }

        // 10. SaaS-Aware Rate Limiting (Keyed by User ID for logged-in users, IP for guests)
        if(config('webguard.rate_limit.enabled', true)){
            if($this->enforceRateLimit($request, $user, $ip)){
                return $this->renderPages('too-many-requests', 429, ['status' => 429]);
            }
        }

        // 11. Detect honeypot traps
        if(!$user && $request->isMethod('POST')){
            if($this->detectHoneypot($request, $ip) == true){
                return $this->renderPages('spam-detected', 403, ['status' => 403]);
            }
        }

        // 12. Check if the IP is banned
        if($this->store->isBanned($ip)){
            return $this->renderPages('user-banned', 403, ['status' => 403]);
        }

        // 13. Sanitize request input and continue processing
        return $this->sanitizeAndContinue($request, $next);
    }


    // Apply trusted proxies configuration to the request
    protected function applyTrustedProxies(Request $request): void {

        if(config('webguard.trusted_proxies.enabled') == true){
            $proxies = config('webguard.trusted_proxies.proxies');
            if($proxies){
                Request::setTrustedProxies(
                    $proxies === '*' ? [$request->server->get('REMOTE_ADDR')] : (array) $proxies,
                    Request::HEADER_X_FORWARDED_FOR |
                    Request::HEADER_X_FORWARDED_HOST |
                    Request::HEADER_X_FORWARDED_PORT |
                    Request::HEADER_X_FORWARDED_PROTO
                );
            }
        }
    }

    // Check if the current request matches any of the excluded route patterns
    protected function isExcludedRoute(Request $request): bool {

        foreach(config('webguard.except', []) as $pattern){
            if($request->is($pattern)){
                return true;
            }
        }

        return false;
    }

    // Check if an IP address matches any pattern in a given list (allowlist or blocklist)
    protected function ipMatchList(string $ip, array $list): bool {
        return ! empty($list) && IpUtils::checkIp($ip, $list);
    }

    // Check if the request hits a configured trap route
    protected function hitsTrapRoute(Request $request): bool {

        if(!config('webguard.trap_routes.enabled', true)){
            return false;
        }

        $path = ltrim($request->path(), '/');
        foreach(config('webguard.trap_routes.paths', []) as $pattern){
            if(Str::is($pattern, $path)){
                return true;
            }
        }

        return false;
    }

    // Track global requests-per-minute and activate Panic Shield if a spike is detected
    protected function trackGlobalTrafficAndDetectSpike(): void {

        if(!config('webguard.panic_shield.enabled', true)){ return; }

        $now = now();
        $currentRpm = $this->store->incrementRpm('webguard:rpm:' . $now->format('YmdHi'));
        $threshold = config('webguard.panic_shield.spike_rpm_threshold', 2000);

        if($currentRpm > $threshold && ! $this->store->isPanicActive()){
            $this->store->activatePanic(config('webguard.panic_shield.duration_minutes', 10));
            Log::channel(config('webguard.log_channel', 'webguard'))->alert("Panic Shield activated! Global RPM reached {$currentRpm}.");
        }
    }

    // Check if the request has a valid trust cookie set by the JS challenge
    protected function hasValidTrustCookie(Request $request): bool {

        $cookie = $request->cookie('webguard_verified');
        $expected = hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), config('app.key'));

        return is_string($cookie) && hash_equals($expected, $cookie);
    }

    // Basic heuristic to determine if the request looks like it comes from a real browser
    protected function looksLikeRealBrowser(Request $request): bool {

        $ua = (string) $request->userAgent();
        if($ua === '' || Str::contains($ua, config('webguard.bot_protection.blocked_agents', []))){
            return false;
        }

        return $request->hasHeader('Accept') && $request->hasHeader('Accept-Language');
    }

    // Serve a simple JavaScript challenge page to verify the client is a real browser
    protected function serveJsChallenge(Request $request){
    
        return $this->renderPages('verify-connection', 202, [
            'status' => 202,
            'duration' => config('webguard.panic_shield.js_challenge.duration_seconds', 30),
            'token' => hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), config('app.key')),
        ]);
    }

    // Enforce rate limiting based on user ID for authenticated users or IP for guests
    protected function enforceRateLimit(Request $request, mixed $user, string $ip) : bool {

        $isPanic = $this->store->isPanicActive();

        if($user){
            $key = 'webguard:rl:user:'.$user->getAuthIdentifier();
            $limit = config('webguard.rate_limit.auth_per_minute', 300);
        }else{
            $key = 'webguard:rl:ip:'.$ip;
            $limit = $isPanic
                ? config('webguard.panic_shield.panic_guest_rpm', 20)
                : config('webguard.rate_limit.guest_per_minute', 60);
        }

        $hits = $this->store->hitRateLimit($key, 60);

        if($hits > $limit){
            // Only auto-ban anonymous guests, never authenticated SaaS customers
            if(!$user && config('webguard.rate_limit.ban_guest_on_exceed', true)){
                $this->store->banIp($ip, config('webguard.rate_limit.ban_minutes', 30));
                $this->logEvent($request, 'rate_limit_ban', "Exceeded {$limit} RPM");
            }
            
            return true;

        }

        return false;
    }

    // Detect if the request is a honeypot trap
    protected function detectHoneypot(Request $request, string $ip): bool {
        
        if(config('webguard.honeypot.enabled', false) == true){

            $honeypot = config('webguard.honeypot.field', 'webguard_field');

            if($honeypot && $request->filled($honeypot)){
                $this->store->banIp($ip, config('webguard.honeypot.ban_minutes', 10));
                $this->logEvent($request, 'honeypot_triggered', 'Bot filled hidden field');
                return true;
            }
        }

        return false;
    }

    // Sanitize request input and continue processing
    protected function sanitizeAndContinue(Request $request, Closure $next): Response {

        if(config('webguard.sanitize.enabled') == true){

            $except = config('webguard.sanitize.except_fields', []);
            $input = $request->all();

            array_walk_recursive($input, function (&$value, $key) use ($except){
                if(! is_string($value) || in_array($key, $except, true)){ return; }
                if(config('webguard.sanitize.strip_null_bytes', true)){ $value = str_replace("\0", '', $value); }
                if(config('webguard.sanitize.trim_strings', true)){ $value = trim($value); }
                if(config('webguard.sanitize.strip_tags', false)){ $value = strip_tags($value); }
            });

            $request->merge($input);
        }

        return $next($request);
    }

    // Log security events for monitoring and auditing
    protected function logEvent(Request $request, string $reason, string $message): void {

        Log::channel(config('webguard.log_channel', 'webguard'))->warning($message, [
            'reason' => $reason,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ua' => $request->userAgent(),
        ]);
    }

    // Render security pages for error handling
    protected function renderPages(string $page, int $status, array $data = []): Response {
        
        try{
            $html = view('WGResourcesViews::'.$page, $data)->render();
        }catch(Exception $e){ $html = ''; }
        
        return response($html, $status)->header('Content-Type', 'text/html');
    }

}