<?php

namespace App\Http\Middleware;

use App\Models\UsuarioSistema;
use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Illuminate\Http\Response;

class JwtMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        try {
            $token = explode(' ', $request->header('Authorization'))[1];
        } catch (Exception $e) {
            $token = $request->header('Authorization');
        }

        if (!$token) {
            // Unauthorized response if token not there
            return response()->json([
                'error' => 'Token nao fornecido.',
            ], Response::HTTP_BAD_REQUEST);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch (ExpiredException $e) {
            return response()->json([
                'error' => 'TOKEN expirado.',
            ], Response::HTTP_UNAUTHORIZED );
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Erro ao decodificar TOKEN. ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
        $user = New UsuarioSistema(["idUsuarioSistema" => $credentials->sub]);
        $user->carrega();
        $request->auth = $user;
        $request->session()->put('user', $user);

        return $next($request);
    }
}
