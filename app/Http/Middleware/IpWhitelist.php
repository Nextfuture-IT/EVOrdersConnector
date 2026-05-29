<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consente l'accesso solo agli IP presenti nella whitelist
 * (config/connettore.php → ip_whitelist). Altrimenti 403.
 *
 * '*' nella lista = consenti tutti (solo sviluppo).
 * Lista vuota = deny-all.
 */
class IpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = (array) config('connettore.ip_whitelist', []);

        if (in_array('*', $whitelist, true)) {
            return $next($request);
        }

        // $request->ip() rispetta i proxy fidati (config trustProxies / nginx).
        if (in_array($request->ip(), $whitelist, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'IP non autorizzato.',
            'ip' => $request->ip(),
        ], Response::HTTP_FORBIDDEN);
    }
}
