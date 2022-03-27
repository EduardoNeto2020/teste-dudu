<?php
/* cSpell:disable */

namespace App\Models;

use App\Models\MSSQL;
use App\Models\MySQL;
use App\Models\PostegreSQL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Classe que traz atributos e métodos padrão para o sistema
 * @author Adriano Sales Santos <sales.adriano@macsolucoes.com>
 * @author Marcus Aurelius de Araújo Hakozaki <marcus.hakozaki@macsolucoes.com>
 *
 * @version 1.0.1
 */
class Modelo extends Model
{
  /**
   * Classe de conexão ao banco de dados
   *
   * @var Object $banco
   */
  protected Object $banco;
  /**
   * Representa a classe que herdou os atributos e métodos do modelo
   *
   * @var String $nomeClasse
   */
  private String $nomeClasse = '';
  /**
   * defini se os dados das classes que são atributos nesta classe serão retornados
   * se definido como true só os dados da classe serão retornados, se definido como
   * true os dados de todas as classes definidas como atributo (classes filhas) serão
   * recuperados (necessário implementar retorno nas classes herdeiras)
   *
   * @var Bool $completo
   */
  public Bool $completo = true;
  /**
   * Campos obrigatórios do modelo.
   *
   * @var Array $validateRules
   */
  protected array $validateRules = [];
  /**
   * Chaves únicas da classe.
   *     *
   */
  public array $uniqueKeys = [];
  /**
   * Chaves únicas da classe.
   *     *
   */
  protected array $requestRequired = [];
  /**
   * Define campos filhos
   *
   * @var array
   */
  protected array $temUm = [];
  protected array $temVarios = [];
  protected array $eUm = [];

  /**
   * Construtor da Classe
   *
   * @param Array $parametros - dados iniciais da classe
   * @param Bool $name Bool $completo - indica se serão retornados os objetos dependentes
   *
   */
  public function __construct(array $parametros = [], Bool $completo = true, UsuarioSistema $usuario = null)
  {
    switch (env("DB_CONNECTION")) {
      case 'sqlsrv':
        $this->banco = new MSSQL($this);
        break;
      case 'pgsql':
        $this->banco = new PostegreSQL($this);
        break;
      default:
        $this->banco = new MySQL($this);
    }

    $this->completo = $completo;
    $this->banco->usuario = $usuario;

    if (!empty($parametros)) {
      $this->definir($parametros);
    }
    $this->nomeClasse = (string) get_class($this);

    try {
      $this->banco->carregaValidar($this);
    } catch (\Throwable $th) {
    }
  }

  public function setUsuario(UsuarioSistema $usuario): void
  {
    $this->banco->usuario = $usuario;
  }

  public function getUsuario(): Model
  {
    return $this->banco->usuario;
  }

  /**
   * encapsulamento de atributo
   *
   * @param Array $value
   *
   */
  public function setTemUm(array $filhos): void
  {
    if (!array_key_exists('foringKey', $filhos) || !array_key_exists('obrigatorio', $filhos) || count($filhos) < 3) {
      throw new \Exception("Array incorreto.");
    }
    $this->temUm = $filhos;
  }

  public function addTemUm(Model $filho, String $nomeFilho = null, String $foringKey = null, String $primaryKey = null, array $parametros = null, Bool $obrigado = false): void
  {
    if (is_null($foringKey)) {
      $foringKey = $this->getKeyName();
    }
    if (is_null($primaryKey)) {
      $primaryKey = $foringKey;
    }
    if (is_null($nomeFilho)) {
      $nomeFilho = $this->get_class($filho);
    }

    $clp = get_class($filho) ==  get_class($this) ? false : $filho->completo;

    $this->temUm[] = [$nomeFilho => get_class($filho), 'foringKey' => $foringKey, 'primaryKey' => $primaryKey, "parametros" => $parametros, "completo" => $clp];
  }

  /**
   *
   *
   * @return Bool
   *
   */
  public function getTemUm(): array
  {
    return $this->temUm;
  }

  /**
   * encapsulamento de atributo
   *
   * @param Array $value
   *
   */
  public function setTemVarios(array $filhos): void
  {
    if (!array_key_exists('foringKey', $filhos) || !array_key_exists('obrigatorio', $filhos) || count($filhos) < 3) {
      throw new \Exception("Array incorreto.");
    }
    $this->temVarios = $filhos;
  }

  public function addTemVarios(Model $filho, String $nomeFilho = null, String $foringKey = null, array $parametros = null, Bool $obrigado = false): void
  {
    if (is_null($foringKey)) {
      $foringKey = $this->getKeyName();
    }
    if (is_null($nomeFilho)) {
      $nomeFilho = $this->get_class($filho) . 's';
    }

    $clp = get_class($filho) ==  get_class($this) ? false : $filho->completo;

    $this->temVarios[] = [$nomeFilho => get_class($filho), 'foringKey' => $foringKey, "parametros" => $parametros, "completo" => $clp];
  }

  /**
   *
   *
   * @return Bool
   *
   */
  public function getTemVarios(): array
  {
    return $this->temVarios;
  }

  /**
   * encapsulamento de atributo
   *
   * @param Array $value
   *
   */
  public function setEUm(array $filhos): void
  {
    if (!array_key_exists('foringKey', $filhos) || !array_key_exists('obrigatorio', $filhos) || count($filhos) < 3) {
      throw new \Exception("Array incorreto.");
    }
    $this->eUm = $filhos;
  }

  public function addEUm(Model $filho, String $nomeFilho = null, String $foringKey = null, String $primaryKey = null, array $parametros = null, Bool $obrigado = false): void
  {
    if (is_null($foringKey)) {
      $foringKey = $filho->getKeyName();
    }
    if (is_null($primaryKey)) {
      $primaryKey = $foringKey;
    }
    if (is_null($nomeFilho)) {
      $nomeFilho = $this->get_class($filho);
    }

    $clp = get_class($filho) ==  get_class($this) ? false : $filho->completo;

    $this->eUm[] = [$nomeFilho => get_class($filho), 'foringKey' => $foringKey, 'primaryKey' => $primaryKey, "parametros" => $parametros, "completo" => $clp];
  }

  /**
   *
   *
   * @return
   *
   */
  public function getEUm(): array
  {
    return $this->eUm;
  }

  /**
   * Carrega dados na classe a partir do array passado como parâmetro
   *
   * @param Array $parametros
   *
   */
  public function definir(array $parametros): void
  {
    $this->fill($parametros);
    foreach ($this->eUm as $filho) {
      $nome = array_keys($filho)[0];
      $this->fillable[] = $nome;
      $this->attributes[$nome] = $this->belongsTo($filho[$nome],  $filho['primaryKey'], $filho['foringKey'], null, $filho['parametros'], $filho['completo']);
    }
    foreach ($this->temUm as $filho) {
      $nome = array_keys($filho)[0];
      if ($this->completo || $filho['obrigado']) {
        $this->fillable[] = $nome;
        $this->attributes[$nome] = $this->hasOne($filho[$nome], $filho['primaryKey'], $filho['foringKey'], $filho['parametros'], $filho['completo']);
      }
    }
    foreach ($this->temVarios as $filho) {
      $nome = array_keys($filho)[0];
      if ($this->completo || $filho['obrigado']) {
        $this->fillable[] = $nome;
        $this->attributes[$nome] = $this->hasMany($filho[$nome], $this->getKeyName(), $filho['foringKey'], $filho['parametros'], $filho['completo']);
      }
    }
  }

  /**
   * Carrega dados do banco na classe, dados no array são usados como
   * parâmetros para busca
   *
   * @param Array $parametros
   *
   */
  public function carrega(array $parametros = []): Model
  {
    if (!count($parametros) > 0) {
      $parametros = $this->getAttributes();
    }

    $this->definir((array) $this->banco->get($parametros)->first());

    return $this;
  }

  /**
   * Busca registros no banco a partir dos parâmetros passados
   *
   * @param Array $parametros
   * @param Bool $completo - indica se serão retornados os objetos dependentes
   * @return Modelo[]
   */
  public function buscar(array $parametros = [], Bool $completo = true): array
  {
    $retorno = [];
    if (!count($parametros) > 0) {
      $parametros = $this->getAttributes();
    }
    $tmp = $this->banco->get($parametros);
    if (count($tmp) > 0) {
      foreach ($tmp as $reg) {
        $retorno[] = new $this->nomeClasse((array) $reg, $completo, $this->banco->usuario);
      }
    }
    return $retorno;
  }

  //TODO verificar se o objeto já existe
  /**
   * Função que persiste uma classe;
   *
   * @param Array $parametros Classe com os dados para persistir.
   * @return Modelo Retorna a classe persistida.
   *
   */
  public function salvar(array $parametros = [], Bool $salvarFilhos = true): void
  {
    if (!count($parametros) > 0) {
      $parametros = $this->getAttributes();
    }
    $this->definir((array) $this->banco->set($parametros));
    $pk = $this->getKeyName();
    $pk = $this->getAttributeValue($pk);
    if ($salvarFilhos) {
      foreach ($this->eUm as $filho) {
        $nome = array_keys($filho)[0];
        $fk = $filho['foringKey'];
        if (array_key_exists($nome, $parametros)) {
          foreach ($parametros[$nome] as $reg) {
            $cls = new $filho[$nome];
            foreach ($cls->uniqueKeys as $unique) {
              $tmp = [];
              foreach ($unique as $key) {
                if (array_key_exists($key, $reg)) {
                  $tmp[$key] = $reg[$key];
                }
              }
              $tmp[$fk] = $pk;
              $clsTmp = $cls->buscar($tmp);
              try {
                $cls = $clsTmp[0];
              } catch (\Throwable $th) {
              }
            }
            try {
              $reg[$fk] = $pk;
              $cls->definir((array) $reg);
              $cls->salvar();
              $this->$nome = $cls->$fk;
              $this->salvar(salvarFilhos: false);
            } catch (\Exception $th) {
              if (env("SQL_DEBUG")) {
                Log::info($th->getMessage());
                $this->attributes["erros"][] = [$nome => $th->getMessage()];
              }
              continue;
            }
          }
        }
      }
      foreach ($this->temUm as $filho) {
        $nome = array_keys($filho)[0];
        $fk = $filho['foringKey'];
        if (array_key_exists($nome, $parametros)) {
          foreach ($parametros[$nome] as $reg) {
            $cls = new $filho[$nome];
            foreach ($cls->uniqueKeys as $unique) {
              $tmp = [];
              foreach ($unique as $key) {
                if (array_key_exists($key, $reg)) {
                  $tmp[$key] = $reg[$key];
                }
              }
              $tmp[$fk] = $pk;
              $clsTmp = $cls->buscar($tmp);
              try {
                $cls = $clsTmp[0];
              } catch (\Throwable $th) {
              }
            }
            $reg[$fk] = $pk;
            try {
              $cls->definir((array) $reg);
              $cls->salvar();
            } catch (\Exception $th) {
              if (env("SQL_DEBUG")) {
                Log::info($th->getMessage());
                $this->attributes["erros"][] = [$nome => $th->getMessage()];
              }
              continue;
            }
          }
        }
      }
      foreach ($this->temVarios as $filho) {
        $nome = array_keys($filho)[0];
        $fk = $filho['foringKey'];
        if (array_key_exists($nome, $parametros)) {
          foreach ($parametros[$nome] as $reg) {
            $cls = new $filho[$nome];
            foreach ($cls->uniqueKeys as $unique) {
              $tmp = [];
              foreach ($unique as $key) {
                if (array_key_exists($key, $reg)) {
                  $tmp[$key] = $reg[$key];
                }
              }
              $tmp[$fk] = $pk;
              $clsTmp = $cls->buscar($tmp);
              try {
                $cls = $clsTmp[0];
              } catch (\Throwable $th) {
              }
            }
            $reg[$fk] = $pk;
            try {
              $cls->definir((array) $reg);
              $cls->salvar();
            } catch (\Exception $th) {
              if (env("SQL_DEBUG")) {
                Log::info($th->getMessage());
                $this->attributes["erros"][] = [$nome => $th->getMessage()];
              }
              continue;
            }
          }
        }
      }
    }
    try {
      (new JsonStore())->manutencao($this->nomeClasse);
    } catch (\Exception $e) {
    }
  }

  /**
   * Função que remove um registro;
   *
   */
  public function deletar(): void
  {
    $this->banco->rm();
    foreach ($this->fillable as $tmp) {
      $this->attributes[$tmp] = null;
    }
    try {
      (new JsonStore())->manutencao($this->nomeClasse);
    } catch (\Exception $e) {
    }
  }

  /**
   * executa consulta no banco baseada em uma script
   *
   * @param String $scriptSQL
   * @param Array $parametros
   * @param String $tipoFiltro
   * @return Modelo
   */
  public function exec(String $scriptSQL, array $parametros = [], String $tipoFiltro = 'F'): bool|int|Collection
  {
    return $this->banco->exec($scriptSQL, $parametros, $tipoFiltro);
  }

  /**
   * retorna parameteros da procedure de busca do banco
   *
   * @return Array
   */
  public function retornaParametros(): Collection
  {
    return $this->banco->retornaParametros();
  }

  /**
   * Retorna os campos obrigatórios da tabela.
   *
   * @return array
   */
  public function getValidar(): array
  {
    return $this->validateRules;
  }

  /**
   * Retorna os campos obrigatórios da tabela.
   *
   * @return array
   */
  public function getValidarRequest(): array
  {
    return $this->requestRequired;
  }

  /**
   * Retorna os campos obrigatórios da tabela.
   *
   * @return array
   */
  public function getMensagemValidar(): array
  {
    $mensagens = [];
    foreach ($this->getValidar() as $chave => $valor) {
      foreach (explode('|', $valor) as $explodeValor) {
        $mensagens[] = [$chave . '.' . $explodeValor => $this->retornaMensagem($explodeValor)];
      }
    }
    return $mensagens;
  }

  /**
   * Retorna os campos obrigatórios da tabela.
   * @param String $valor -
   * @return array
   */
  private function retornaMensagem(String $valor): string
  {
    switch ($valor) {
      case 'required':
        $retorno = ' é obrigatório.';
        break;
      case 'accepted':
        $retorno = ' tem que ser YES ou 1.';
        break;
      case 'alpha':
        $retorno = ' só pode conter caracteres.';
        break;
      case 'email':
        $retorno = ' tem que ser um e-mail.';
        break;
      case 'integer':
        $retorno = ' tem que ser um número inteiro.';
        break;
      default:
        $retorno = '';
        break;
    }

    return $retorno;
  }

  /**
   * inclui classe na lista one-to-one
   *
   * @param String $classe
   * @param String $foringKey
   * @param String $localKey
   * @return boolean
   */
  public function hasOne($classe, $localKey = null, $foringKey = null, array $parametros = null, $completo = true): Model| null
  {
    if (is_null($localKey)) {
      $localKey = $this->getKeyName();
    }
    if (is_null($foringKey)) {
      $foringKey = $this->getKeyName();
    }
    if (!array_key_exists($localKey, $this->attributes) || is_null($this->attributes[$localKey])) {
      return null;
    }

    $retorno = new $classe(completo: $completo);
    $parametros[$foringKey] = $this->attributes[$localKey];
    $retorno->carrega($parametros, $completo);
    try {
      $tmp = $retorno->$foringKey;
      return $retorno;
    } catch (\Throwable $th) {
      return null;
    }
  }

  /**
   * inclui classe na lista one-to-many
   *
   * @param String $classe
   * @param String $foringKey
   * @param String $localKey
   * @return boolean
   */
  public function hasMany($classe, $localKey = null, $foringKey = null, array $parametros = null, Bool $completo = true): array| null
  {
    if (is_null($parametros)) {
      $parametros = [];
    }
    if (is_null($localKey)) {
      $localKey = $this->getKeyName();
    }
    if (is_null($foringKey)) {
      $foringKey = $this->getKeyName();
    }

    if (!array_key_exists($localKey, $this->attributes) || is_null($this->attributes[$localKey])) {
      return null;
    }

    $dados = new $classe(completo: $completo);
    $parametros[$foringKey] = $this->attributes[$localKey];
    return $dados->buscar($parametros);
  }

  /**
   * retorna classe a qual a classe atual está subordinada
   *
   * @param $classe
   * @param $localKey
   * @return boolean
   */
  public function belongsTo($classe, $localKey = null, $foringKey = null, $relation = null, $parametros = null, $completo = false): Model| null
  {

    $dados = new $classe(completo: $completo);

    if (is_null($parametros)) {
      $parametros = [];
    }

    if (is_null($localKey)) {
      $localKey = $dados->getKeyName();
    }

    if (is_null($foringKey)) {
      $foringKey = $dados->getKeyName();
    }

    if (!array_key_exists($foringKey, $this->attributes) || is_null($this->attributes[$foringKey])) {
      return null;
    }

    $parametros[$localKey] = $this->attributes[$foringKey];

    try {
      $dados->carrega($parametros);
      $tmp = $dados->$localKey;
      return $dados;
    } catch (\Exception $e) {
      return null;
    }
  }

  public function isMd5(string $md5 = ''): bool
  {
    return strlen($md5) == 32 && ctype_xdigit($md5);
  }
}
