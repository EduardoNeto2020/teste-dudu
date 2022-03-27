<?php
/* cSpell:disable */

namespace App\Models;

class Pagamento extends Modelo
{
    /*
     * Tabela modelo
     */

    protected $table = 'pagamento';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'IDPAGAMENTO';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['IDPAGAMENTO', 'IDLIQUIDACAO', 'NUMEROPAGAMENTO', 'ANOPAGAMENTO', 'MESPAGAMENTO', 'ENTIDADE', 'ANOLIQUIDACAO', 'NUMEROLIQUIDACAO', 'DATAEMISSAO', 'DATABORDERO', 'DATAPAGAMENTO', 'DATABAIXA', 'STATUS', 'CODIGOCREDOR', 'RAZAOSOCIAL', 'CODIGOBANCO', 'CODIGOAGENCIA', 'CONTABANCARIA', 'CONTAFINANCEIRA', 'DESCRICAOPAGAMENTO', 'CODIGONATUREZAPAGAMENTO', 'DESCRICAONATUREZAPAGAMENTO', 'NUMEROEMISSOES', 'VALORPAGAMENTO', 'TOTALCONSIGNACOES', 'TOTALDESCONTO', 'FORMAPAGAMENTO', 'TIPOOPERACAOBANCARIA', 'DOCUMENTOPAGAMENTO', 'EVENTOCONTABIL    ',
    ];

    public function __construct(array $parametros = [], Bool $completo = true)
    {
        // $this->addTemVarios(new UsuarioEntidade(), 'administradores');
        parent::__construct($parametros, $completo);
        $this->validateRules = [ "ANOPAGAMENTO" => "required","MESPAGAMENTO" => "required"];
    }

}
