<?php
/* cSpell:disable */

namespace App\Models;

class JsonStore extends Modelo
{
    /*
     * Tabela modelo
     */
    protected $table = 'jsonStore';

    /*
     * Id da tabela
     */
    protected $primaryKey = 'idJsonStore';

    /*
     * Define o marcador de timeStamp "false"
     */
    public $timestamps = false;

    /**
     * Atributos do modelo.
     *
     * @var array
     */
    protected $fillable = [
        'idJsonStore', 'origem', 'nivelAcesso', 'atributos', 'tempoVida', 'jsonContent',
    ];

    public function buscarJson(String $classe, $parametros = [])
    {
        $this->origem = $classe;
        $this->atributos = '';
        foreach ($parametros as $key => $value) {
            if (!is_array($value) && strval($value) != "") {
                $this->atributos =  $this->atributos . "$key='" . strval($value) . "',";
            }
        }

        $this->atributos = $this->atributos = '' ? 'N'.$this->banco->usuario->maiorNivel() : 'N'.$this->banco->usuario->maiorNivel().', '.$this->atributos;

        if (!key_exists('nivelAcesso', $this->attributes) || !strlen($this->attributes['nivelAcesso']) > 0) {
            $this->attributes['nivelAcesso'] = 'publico';
        }
        $this->carrega();
    }

    public function manutencao()
    {
        $this->exec("DELETE FROM fWork.jsonStore WHERE tempoVida < getdate()");
    }

    public function salvar(array $parametros = [], Bool $salvarFilhos = true): void
    {
        if (!key_exists('nivelAcesso', $this->attributes) || !strlen($this->attributes['nivelAcesso']) > 0) {
            $this->attributes['nivelAcesso'] = 'publico';
        }

        if ($this->origem  != 'jsonStore') {
            parent::salvar($parametros, $salvarFilhos);
        }
    }

    public function buscar(array $parametros = [], Bool $completo = true): array
    {
        $this->manutencao();
        return parent::buscar($parametros, $completo);
    }
}
