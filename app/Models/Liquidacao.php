<?php
/* cSpell:disable */

namespace App\Models;

class Liquidacao extends Modelo
{
    /*
     * Tabela modelo
     */

    protected $table = 'liquidacao';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'IDLIQUIDACAO';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['IDLIQUIDACAO', 'NUMEROLIQUIDACAO', 'ANOLIQUIDACAO', 'MESLIQUIDACAO', 'ENTIDADE', 'NUMEROEMPENHO', 'ANOEMPENHO', 'DATAEMISSAO', 'STATUS', 'NUMEROEMISSOES', 'PREVISAOPAGAMENTO', 'DATAEMPENHO', 'HISTORICO', 'TIPOEMPENHO', 'CODIGOCREDOR', 'RAZAOSOCIAL', 'EXERCICIO', 'ORGAO', 'UNIDADE', 'REDUZIDOPROGRAMATICA', 'FUNCIONALPROGRAMATICA', 'DESPESAORCAMENTARIA', 'FONTERECURSO', 'VALORDALIQUIDACAO', 'ANULADOLIQUIDACAO', 'VALOREMPENHO', 'TOTALALIQUIDAR', 'PAGODALIQUIDACAO', 'TOTALAPAGAR', 'DESCRITIVOLIQUIDACAO', 'TIPODOCUMENTO', 'DOCUMENTOCOMPROBATORIO', 'ANODOCUMENTO', 'MESDOCUMENTO', 'DATADOCUMENTO', 'EVENTOCONTABIL', 'IDEMPENHO',
    ];

    protected $hidden = [];

    public function __construct(array $parametros = [], Bool $completo = true)
    {
        $this->addTemVarios(new Pagamento(), 'PAGAMENTOS',null,null, true);
        parent::__construct($parametros, $completo);
        $this->validateRules = [ "ANOLIQUIDACAO" => "required","MESLIQUIDACAO" => "required" ];

    }

}
