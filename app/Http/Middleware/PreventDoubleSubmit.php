<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreventDoubleSubmit
{
    /**
     * Lifetime of a fingerprint in seconds
     */
    protected $ttl = 5;

    public function handle(Request $request, Closure $next)
    {
        // Apply only to POST/PUT/PATCH/DELETE
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Fingerprint: route + method + hash(payload)
        $payload = $request->except(['_token', '_method', 'client_updated_at', 'updated_at']);
        $fingerprint = $request->method() . '|' . $request->route()->getName() . '|' . md5(json_encode($payload));

        $sessionKey = '_request_fingerprint_' . $fingerprint;

        if (session()->has($sessionKey)) {
            // If within TTL, block
            return redirect()->back()->withInput()->withErrors(['msg' => 'Yêu cầu đang được xử lý. Vui lòng đợi vài giây trước khi thử lại.']);
        }

        // store fingerprint
        session()->put($sessionKey, time() + $this->ttl);
        // schedule removal after response
        $response = $next($request);

        // ensure removal: we remove immediately if response is not a redirect to avoid locking form for long.
        // otherwise keep until TTL is expired automatically on next request checks.
        // Here remove it instantly to not block unrelated requests (but TTL provides safety)
        session()->forget($sessionKey);

        return $response;
    }
}
