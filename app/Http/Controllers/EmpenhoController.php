<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use App\Models\Empenho;

class EmpenhoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $classe = new Empenho();
        parent::__construct($classe);
        $this->jsonRet = true;
    }
}
