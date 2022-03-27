<?php
/* cSpell:disable */

namespace App\Models;

class Objeto extends Modelo
{
    /*
    * Tabela modelo
    */

    protected $table = 'storage.repositorio';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'idIndexador';

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
        'idIndexador', 'origemObjeto',  'idOrigem', 'nomeObjeto', 'caminhoRelativo', 'tipoObjeto', 'descricaoObjeto', 'md5', 'tamanho', 'dataRegistro', 'dataCriacaoObjeto', 'versaoObjeto', 'comentario', 'arquivoCompactado', 'arquivoCriptografado', 'objeto'
    ];




}
