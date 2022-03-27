<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioSistema;
use App\Models\UsuarioEntidade;
use App\Models\UsuarioPerfil;

class UsuarioSistemaController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
       $classe = new UsuarioSistema();
        parent::__construct($classe);
    }

}
