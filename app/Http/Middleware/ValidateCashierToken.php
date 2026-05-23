<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCashierToken
{
    /**
     * التحقق من التوكن الثابت والمشترك القادم من الكاشير المحلي
     */
    public function handle(Request $request, Closure $next): Response
    {
        // اكتب هنا التوكن الثابت الخاص بك حرفياً وبيدك
        $staticSecretToken = "MY_FIXED_SECRET_CASHIER_KEY_2026";

        // قراءة التوكن المرسل من الكاشير في الترويسات (Authorization Header)
        $token = $request->bearerToken();

        // المقارنة اليدوية الصريحة والبديهية
        if (!$token || $token !== $staticSecretToken) {
            return response()->json([
                'message' => 'غير مصرح لك بالوصول، التوكن الثابت للكاشير غير صحيح أو مفقود.'
            ], 401);
        }

        return $next($request);
    }
}
