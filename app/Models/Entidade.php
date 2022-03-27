<?php
/* cSpell:disable */

namespace App\Models;

class Entidade extends Modelo
{
    /*
     * Tabela modelo
     */

    protected $table = 'entidade';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'IDENTIDADE';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['IDENTIDADE', 'ORGAO', 'ENTIDADE', 'NOMEENTIDADE',
    'SIGLA', 'HASHCOMPLETO',
    ];

    public function __construct(array $parametros = [], Bool $completo = true)
    {
        // $this->addTemVarios(new UsuarioEntidade(), 'administradores');
        parent::__construct($parametros, $completo);
    }

}
