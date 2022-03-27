<?php
/* cSpell:disable */

namespace App\Models;

class Contrato extends Modelo
{
    /*
     * Tabela modelo
     */

    protected $table = 'contratos';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'IDCONTRATO';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'IDCONTRATO', 'MATRICULASERVIDOR', 'NUMEROCONTRATO', 'CPFSERVIDOR', 'TIPOCONTRATO', 'CARGOSERVIDOR', 'DATAADMISSAO,',
    ];

    public function __construct(array $parametros = [], Bool $completo = true)
    {
        // $this->addTemVarios(new UsuarioEntidade(), 'administradores');
        parent::__construct($parametros, $completo);
    }
}
