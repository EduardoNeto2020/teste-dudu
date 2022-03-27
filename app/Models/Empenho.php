<?php
/* cSpell:disable */

namespace App\Models;

class Empenho extends Modelo
{
    /*
     * Tabela modelo
     */

    protected $table = 'empenho_consolidado';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'IDEMPENHO';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['IDEMPENHO', 'IDENTIDADE', 'NUMEROEMPENHO', 'ANOEMPENHO', 'MESEMPENHO', 'ENTIDADE', 'DATAEMPENHO', 'TIPOEMPENHO', 'DATAPROGRAMACAO', 'NUMEROEMISSOES', 'STATUS', 'PEDIDOEMPENHO', 'MOTIVOEMPENHO', 'NUMEROCONTRATO', 'DATAPEDIDO', 'MODALIDADELICITACAO', 'NUMEROLICITACAO', 'HISTORICO', 'CODIGOCREDOR', 'RAZAOSOCIAL', 'CPFCNPJCREDOR', 'CPFCNPJCREDOR_', 'CLASSECREDOR', 'BANCO', 'BANCO_', 'AGENCIA', 'AGENCIA_', 'CONTA', 'CONTA_', 'ORGAO', 'UNIDADE', 'REDUZIDOFUNCIONAL', 'FUNCIONALPROGRAMATICA', 'DESPESAORCAMENTARIA', 'FONTERECURSO', 'DETALHAMENTOFONTE', 'VALOREMPENHADO', 'NATUREZARECURSO', 'NUMEROCONVENIO', 'ANOCONVENIO', 'NUMEROPROCESSO', 'ANOPROCESSO', 'EVENTOCONTABIL', 'TOTALLIQUIDADO', 'TOTALPAGO', 'COMPLETO', 'HASHCOMPLETO',
    ];

    protected $hidden = ['CPFCNPJCREDOR_', 'BANCO_', 'AGENCIA_', 'CONTA_',];

    public function __construct(array $parametros = [], Bool $completo = true)
    {
        $this->addTemVarios(new Liquidacao(), "LIQUIDACOES");
        parent::__construct($parametros, $completo);
        $this->requestRequired = [ "IDENTIDADE" => "required","ANOEMPENHO" => "required","MESEMPENHO" => "required" ];

    }

}
