<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use App\Models\Liquidacao;

class LiquidacaoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $classe = new Liquidacao();
        parent::__construct($classe);
        $this->jsonRet = true;
    }
}
