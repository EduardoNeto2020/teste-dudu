<?php
/* cSpell:disable */

namespace App\Models;

class Relatorio extends Modelo
{
    /*
     * Tabela modelo
     */

    protected $table = 'fWork.relatorio';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'idRelatorio';

    /*
     * Define o marcador de time,0Stamp "false"
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idRelatorio', 'nomeRelatorio', 'nomeArquivo', 'query',
    ];

    public function __construct(array $parametros = [], Bool $completo = true, $usuario = null)
    {
        parent::__construct($parametros, $completo, $usuario);
    }
}
