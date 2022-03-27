<?php
/* cSpell:disable */

namespace App\Models;

use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\AuthenticationException;

class UsuarioSistema extends Modelo implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;
    /*
     * Tabela modelo
     */
    protected $table = 'usuario';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'IDUSUARIO';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * Atributos do modelo.
     *
     * @var array
     */
    protected $fillable = [
        'IDUSUARIO', 'LOGINUSUARIO', 'NOMEUSUARIO', 'EMAILUSUARIO',
        'SENHAUSUARIO', 'SITUACAOUSUARIO', 'NIVELACESSO', 'api_token'
    ];

    public function setSENHAUSUARIOAttribute($value)
    {
        if (!is_null($value) && !parent::isMd5($value)) {
            $this->attributes['SENHAUSUARIO'] = md5($value);
        } else {
            $this->attributes['SENHAUSUARIO'] = $value;
        }
    }

    public function login(String $username, String $password)
    {
        try {
            $senha = md5($password);
            $tmp = $this->buscar(['LOGINUSUARIO' => $username, 'SITUACAOUSUARIO' => 'ATIVO']);

            if (!count($tmp)) {
                throw new AuthenticationException("Usuário não encontrado");
            } elseif ($senha != $tmp[0]->SENHAUSUARIO) {
                throw new AuthenticationException("Senha inválida");
            } else {
                $this->definir($tmp[0]->getAttributes());
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checarNivel(int $nivel): bool
    {

        if ($this->NIVELACESSO <= $nivel) {
            return true;
        }

        return false;
    }

    public function maiorNivel(): int
    {
        return $this->NIVELACESSO;
    }
}
