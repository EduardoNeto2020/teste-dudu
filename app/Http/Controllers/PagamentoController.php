<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use App\Models\Pagamento;

class PagamentoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $classe = new Pagamento();
        parent::__construct($classe);
        $this->jsonRet = true;
    }
}
