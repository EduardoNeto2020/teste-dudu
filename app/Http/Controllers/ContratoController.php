<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use App\Models\Contrato;

class ContratoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $classe = new Contrato();
        parent::__construct($classe);
        $this->jsonRet = true;
    }
}
