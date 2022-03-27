<?php

namespace App\Http\Controllers;

use App\Models\UsuarioSistema;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;

/**
 * @group Login Acesso
 */
class AuthController extends Controller
{
    /**
     * Instânçia do REQUEST
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $tmp = new UsuarioSistema();
        parent::__construct($tmp);
        $this->request = $request;
        $this->middleware('jwt.auth', ['only' => ['refreshToken']]);
    }

    /**
     * Atualiza o token.
     */
    public function refreshToken()
    {
        //Token enviado
        $token = $this->request->header('Authorization');
        //Payload do TOKEN
        $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        //Usuário do TOKEN
        $user = New UsuarioSistema(["idUsuarioSistema" => $credentials->sub]);
        $user->carrega();

        return response()->json(['token' => $this->jwt($user)], 200);
    }

    /**
     * Cria o TOKEN JWT
     *
     * @param  \App\User $user
     * @return string
     */
    protected function jwt(UsuarioSistema $user)
    {
        $payload = [
            'iss' => getenv('APP_URL'), // Issuer do TOKEN. *TODO Mudar para o endereço real do servidor
            'sub' => $user->idUsuarioSistema, // Subject do TOKEN.
            'user' => $user->nomeUsuarioSistema, // Nome do usuário.
            'iat' => time(), // Hora da emissão do TOKEN.
            'exp' => time() + 60 * 60, // Hora de exporiração do TOKEN.
        ];
        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Login no Sistema
     *
     */
    public function login()
    {
        $this->validate($this->request, [
            'loginUsuarioSistema' => 'required',
            'senhaUsuarioSistema' => 'required',
        ], [
            'loginUsuarioSistema.required' => 'Login obrigatorio.',
            'senhaUsuarioSistema.required' => 'Senha obrigatoria.',
        ]);
        // Encontra o usuario pelo e-mail
        $user = new UsuarioSistema();
        try {
            $user->login($this->request->input('loginUsuarioSistema'),$this->request->input('senhaUsuarioSistema'));
            $user->api_token = $this->jwt($user);
            $user->salvar();
            return response()->json([$user], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 401);
        }

    }
}
