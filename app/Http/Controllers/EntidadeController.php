<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use App\Models\Entidade;

class EntidadeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
       $classe = new Entidade();
        parent::__construct($classe);
    }

}
