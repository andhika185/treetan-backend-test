<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecretKeyIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil secret key dari header request
        $secretKey = $request->header('X-Secret-Key');

        // Bandingkan dengan secret key yang kita tentukan di .env
        // Ganti 'YOUR_SECRET_KEY' dengan key acak yang kuat
        if ($secretKey !== config('app.api_secret_key')) {
            return response()->json(['message' => 'Forbidden: Invalid Secret Key'], 403);
        }

        return $next($request);
    }
}
