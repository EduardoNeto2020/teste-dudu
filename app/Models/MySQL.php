<?php
/* cSpell:disable */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class MySQL
{
    private $nomeModelo = '';
    private $modelo;
    private $origemDados;
    private $isSP;
    public $usuario = null;

    /**
     * Construtor da Classe
     *
     * @param Model $modelo - Classe de modelo que utiliza a conexão.
     */
    public function __construct(Model &$modelo = null)
    {
        if ($modelo != null) {
            $this->nomeModelo = $this->retornaNomeClasse($modelo);
            $this->modelo = $modelo;
        }
    }


    /**
     * * Função que executa a procedure "getControleCriterios" que retorna a
     * procedure get,set ou rm da classe .
     *
     *  @access private
     *  @param String $filtro Tipo de filtro 'F' - GET ;'S' - SET;'E' - RM
     *  @return String Retorna a procedure.
     */
    private function retornaOrigemDados(string $tipofiltro): string
    {
        try {
            $controleCriterios = collect(DB::select("exec fwork.getControleCriterio @tipofiltro = '$tipofiltro' , @origemCriterio = '$this->nomeModelo' "))->first();
            $linha = (array) $controleCriterios;
            $this->origemDados = $controleCriterios === null ? $this->modelo->getTable() : $linha['origemDados'];
        } catch (\Exception $e) {
            $this->origemDados = $this->modelo->getTable();
        }

        return $this->origemDados;
    }


    /**
     * * Função que executa a procedure "getCamposProcedure" e retorna
     * um conjunto campos usados como parametro de uma procedure.
     *
     *  @access private
     *  @param String $filtro Tipo de filtro
     * 'F' - Pesquisa ;'S' - Salvar;'R' - Remover
     *  @return Array[] Retorna o conjunto de parametros de uma procedure.
     */
    public function retornaParametros($tipofiltro = 'F', $procedure = null): Collection
    {
        if ($procedure == null) {
            $procedure = $this->retornaOrigemDados($tipofiltro);
        }

        $nomeProcedure = explode('.', explode(' ', $procedure)[0]);
        $str = "";
        if (count($nomeProcedure) == 1) {
            $schema = strtolower(env("DB_DATABASE"));
            $tabela = strtolower($nomeProcedure[0]);
        } else {
            $schema = strtolower($nomeProcedure[0]);
            $tabela = strtolower($nomeProcedure[1]);
        }
        $str = " lower(TABLE_SCHEMA) = '$schema' and lower(TABLE_NAME) = '$tabela' ";
        $this->isSP = 0;
        try {
            $this->isSP = collect(DB::select("select count(1) isSP
                                    from sys.procedures sp
                                    inner join sys.objects so
                                        ON sp.object_id = so.object_id
                                    inner join sys.schemas ss
                                        ON ss.schema_id = so.schema_id
                                    where
                                        " . $str))[0]->isSP;
            $this->isSP = 0;
            if ($this->isSP == 1) {
                return collect(DB::select("exec getCamposProcedure '$schema.$tabela'"));
            } else {
                return collect(DB::select("select COLUMN_NAME name,
                                                DATA_TYPE tipo,
                                                COALESCE(CHARACTER_MAXIMUM_LENGTH, 9) comprimento
                                            from INFORMATION_SCHEMA.COLUMNS
                                            where
                                            " . $str));
            }
        } catch (\Exception $e) {
            return collect(DB::select("select COLUMN_NAME name,
                                        DATA_TYPE tipo,
                                        COALESCE(CHARACTER_MAXIMUM_LENGTH, 9) comprimento
                                    from INFORMATION_SCHEMA.COLUMNS
                                    where
                                    " . $str));
        }
    }

    /**
     * * Função que retorna o nome da classe sem o "\App"
     *
     *  @access private
     *  @return String Retorna o nome da classe sem o "\App".
     */
    public function retornaNomeClasse(&$classe): string
    {
        return substr(get_class($classe), strrpos(get_class($classe), '\\') + 1);
    }

    /**
     * Função que retorna uma SCRIPT para a execução no banco de dados.
     *
     * @access private
     * @param Illuminate\Database\Eloquent\Model $modelo Modelo base.
     * @param Array $parametros Parametros da SCRIPT.
     * @param String $tipofiltro Tipo do filtro 'F' PROCURAR / 'S' SALVAR / 'E' REMOVER
     * @return String Retorna uma script.
     */
    private function retornaScript(array $parametros = [], $tipofiltro = 'F', $procedure = null): string
    {
        if (!count($parametros) > 0 && isset($this->modelo)) {
            $parametros = $this->modelo->getAttributes();
        }
        //Verifica as variaveis de SESSÃO(DO MIDDLEWARE RequestToken) ou do \Auth
        if ($this->usuario != null && !strpos(strtoupper($procedure), "@USERNAME")) {
            if (!count($parametros) > 0) {
                $parametros = ['userName' => $this->usuario->loginUsuarioSistema];
            } else {
                $parametros += ['userName' => $this->usuario->loginUsuarioSistema];
            }
            $parametros += ['senha' => $this->usuario->senhaUsuarioSistema];
        }

        //Variavel que recebe os parametros de uma procedure do banco
        $parametros_banco = $this->retornaParametros($tipofiltro, $procedure);
        // Array de valores
        $valores = [];

        //Atributo de verificação de atributos da classe
        $temParametros = false;

        //  Variáveis auxiliares
        $arroba = '';
        $exec = '';
        $where = ' where ';
        $script = '';

        //Laço que verifica os parametros preenchidos
        //do objeto com os parametros da procedure do banco
        foreach ($parametros_banco as $parametro_banco) {
            $parametro_banco = (array) $parametro_banco;

            $nome = $parametro_banco["name"];

            if (array_key_exists($nome, $parametros) && strlen($parametros[$nome]) > 0) {

                $temParametros = true;

                switch ($parametro_banco["tipo"]) {
                    case 'varchar':
                    case 'date':
                    case 'datetime':
                        $valor = rtrim(ltrim($parametros[$nome]));
                        $valores[$nome] = strlen($valor) > 0 ? "'" . str_replace("\`", "''", str_replace("'", "`", $valor)) . "'" : '';
                        break;
                    case 'numeric':
                        $valores[$nome] = rtrim(ltrim($parametros[$nome]));
                        break;
                    case 'int':
                        $valores[$nome] = rtrim(ltrim($parametros[$nome]));
                        break;
                    case 'blob':
                        $valores[$nome] = rtrim(ltrim($parametros[$nome]));
                        break;
                    default:
                        $valor = rtrim(ltrim($parametros[$nome]));
                        $valores[$nome] = strlen($valor) > 0 ? "'" . str_replace("'", "`", $valor) . "'" : '';
                }
            }
        }

        //Laço que verifica os parametros preenchidos
        //do objeto com os parametros da procedure do banco
        if ($this->isSP == 0) {
            switch (strtolower($tipofiltro)) {
                case 'f':
                    foreach ($parametros_banco as $parametro_banco) {
                        $parametro_banco = (array) $parametro_banco;

                        $nome = $parametro_banco["name"];

                        if (array_key_exists($nome, $valores) && strlen($valores[$nome]) > 0) {

                            if (!is_numeric(strpos($script, ' where '))) {
                                $script .= $where;
                            }

                            $script .= " $nome = $valores[$nome] AND ";
                        }
                    }
                    $script = substr($script, 0, strlen($script) - 4);
                    $procedure =  $procedure === null ?  "SELECT DISTINCT * FROM $this->origemDados " . $script . "   " : $procedure . " " . $script;
                    break;
                case 'e':
                    $pk = $this->modelo->getKeyName();
                    if (array_key_exists($pk, $valores) && strlen($valores[$pk]) > 0) {
                        $procedure =  $procedure === null ?  "DELETE FROM  $this->origemDados WHERE $pk =  $valores[$pk]    " : $procedure . " " . $script;
                    } else {
                        throw new \Exception("Chave primária obrigatória");
                    }
                    break;
                case 's':
                    $pk = $this->modelo->getKeyName();
                    if (array_key_exists($pk, $valores) && strlen($valores[$pk]) > 0) {
                        foreach ($parametros_banco as $parametro_banco) {
                            $parametro_banco = (array) $parametro_banco;

                            $nome = $parametro_banco["name"];

                            if (array_key_exists($nome, $valores) && strlen($valores[$nome]) > 0 && $nome != $pk) {
                                $script .= " $nome = $valores[$nome], ";
                            }
                        }
                        $script = substr($script, 0, strlen($script) - 2);
                        $procedure =  $procedure === null ?  "UPDATE $this->origemDados  SET $script WHERE $pk =  $valores[$pk]  ||  SELECT DISTINCT * FROM $this->origemDados WHERE $pk = $valores[$pk]   " : $procedure . " " . $script;
                    } else {
                        $campos = "";
                        foreach ($parametros_banco as $parametro_banco) {
                            $parametro_banco = (array) $parametro_banco;

                            $nome = $parametro_banco["name"];

                            if (array_key_exists($nome, $valores) && strlen($valores[$nome]) > 0 && $nome != $pk) {
                                $campos .= " $nome, ";
                                $script .= " $valores[$nome], ";
                            }
                        }
                        $script = substr($script, 0, strlen($script) - 2);
                        $campos = substr($campos, 0, strlen($campos) - 2);

                        $procedure =  $procedure === null ?  "INSERT $this->origemDados ($campos) VALUES ($script) ||  SELECT DISTINCT * FROM $this->origemDados WHERE $pk = IDENT_CURRENT('$this->origemDados')  " : $procedure . " " . $script;
                    }
                    break;

                default:
                    break;
            }
        } else {
            $arroba = '@';
            $exec = strpos($procedure, 'exec ') == false ? 'exec ' : '';
            foreach ($parametros_banco as $parametro_banco) {
                $parametro_banco = (array) $parametro_banco;

                $nome = $parametro_banco["name"];

                if (array_key_exists($nome, $valores) && strlen($valores[$nome]) > 0) {

                    if ((is_numeric(strpos($script, ' @')) || is_numeric(strpos($script, ' where '))) && substr(rtrim($script), strlen($script) - 2, 1) != ',') {
                        $script .= ', ';
                    }

                    $script .= " $arroba$nome = $valores[$nome], ";
                }
            }
            $procedure =  $procedure === null ?  $exec . $this->origemDados . $script : $procedure . " " . $script;
        }

        foreach ($parametros as $chave => $valor) {
            if (is_string($valor)) {
                $procedure = str_replace("|$chave|", $valor, $procedure);
            }
        }

        //Retira a virgula final
        if ($temParametros) {
            $procedure = substr($procedure, 0, strlen($procedure) - 2);
        }

        return $procedure;
    }

    /**
     * * Função que retorna os valores de uma pesquisa.
     *
     *  @access public
     *  @param Illuminate\Database\Eloquent\Model $modelo Classe base.
     *  @return Array Retorna os registros pesquisados .
     */
    public function get(array $parametros = []): Collection
    {
        if (!count($parametros) > 0) {
            $parametros = $this->modelo->getAttributes();
        }

        $script = $this->retornaScript($parametros, 'F');

        try {
            if (env("SQL_DEBUG")) Log::info($this->trataErro($script));
            return collect(DB::select($script));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception($this->trataErro($e->getMessage()));
        }
    }

    /**
     * * Função que persiste uma classe;
     *
     *  @access public
     *  @param Illuminate\Database\Eloquent\Model $modelo Classe base.
     *  @return Array Retorna a classe persistida.
     */
    public function set(array $parametros = []): stdClass
    {
        if (!count($parametros) > 0) {
            $parametros = $this->modelo->getAttributes();
        }

        $script = $this->retornaScript($parametros, 'S');

        try {
            if (env("SQL_DEBUG")) {
                Log::info($this->trataErro($script));
            }
            $query = explode("||", $script);
            if (count($query) > 1) {
                DB::update($query[0]);
                return collect(DB::select($query[1]))->first();
            } else {
                return collect(DB::select($query[0]))->first();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception($this->trataErro($e->getMessage()));
        }
    }

    /**
     * * Função que remove um registro.
     *
     *  @access public
     *  @param Illuminate\Database\Eloquent\Model $modelo Classe base.
     *  @return Array Retorno da função do banco.
     */
    public function rm(array $parametros = []): int
    {
        if (!count($parametros) > 0) {
            $parametros = $this->modelo->getAttributes();
        }

        $script = $this->retornaScript($parametros, 'E');

        try {
            if (env("SQL_DEBUG")) {
                Log::info($this->trataErro($script));
            }
            DB::Delete($script);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception($this->trataErro($e->getMessage()));
        }
    }

    /**
     * * Função que executa uma "Stored Procedure"
     *
     *  @access public
     *  @param String $storedProcedure Nome da Stored Procedure.
     *  @param Array $parametros Parametros da Stored Procedure.
     *  @return Array[] Retorno da função do banco.
     */
    public function exec($scriptSQL, array $parametros = [], $tipofiltro = 'F'): bool|int|Collection
    {
        $script = $this->retornaScript($parametros, $tipofiltro, $scriptSQL);

        $resul = null;

        try {
            if (is_numeric(strpos(strtoupper($scriptSQL), "INSERT"))) {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($script));
                DB::insert($script);
                $resul = true;
            } elseif (is_numeric(strpos(strtoupper($scriptSQL), "DELETE"))) {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($script));
                DB::delete($script);
                $resul =  true;
            } elseif (is_numeric(strpos(strtoupper($scriptSQL), "UPDATE"))) {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($script));
                DB::update($script);
                $resul = true;
            } else {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($script));
                $resul =  collect(DB::select($script));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception($this->trataErro($e->getMessage()));
        }

        return $resul;
    }

    /**
     * * Função que executa uma "Stored Procedure"
     *
     *  @access public
     *  @param String $storedProcedure Nome da Stored Procedure.
     *  @return Array[] Retorno da função do banco.
     */
    public function query($scriptSQL): bool|int|Collection
    {
        $resul = null;

        try {
            if (is_numeric(strpos(strtoupper($scriptSQL), "INSERT"))) {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($scriptSQL));
                DB::insert($scriptSQL);
                $resul =  true;
            } elseif (is_numeric(strpos(strtoupper($scriptSQL), "DELETE"))) {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($scriptSQL));
                DB::delete($scriptSQL);
                $resul =  true;
            } elseif (is_numeric(strpos(strtoupper($scriptSQL), "UPDATE"))) {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($scriptSQL));
                DB::update($scriptSQL);
                $resul =  true;
            } else {
                if (env("SQL_DEBUG")) Log::info($this->trataErro($scriptSQL));
                $resul = Collect(DB::select($scriptSQL));
            }
        } catch (\Exception $e) {
            if (env("SQL_DEBUG")) Log::info($this->trataErro($scriptSQL));
            Log::error($e->getMessage());
            throw new \Exception($this->trataErro($e->getMessage()));
        }

        return $resul;
    }

    public function carregaValidar(Model &$classe): array
    {
        try {
            $nomeProcedure = explode(".", $classe->table);
            $str = "";
            if (count($nomeProcedure) == 1) {
                $schema = strtolower(env("DB_DATABASE"));
                $tabela = strtolower($nomeProcedure[0]);
            } else {
                $schema = strtolower($nomeProcedure[0]);
                $tabela = strtolower($nomeProcedure[1]);
            }
            $str = " lower(TABLE_SCHEMA) = '$schema' and lower(TABLE_NAME) = '$tabela' ";
            foreach ($this->exec("select COLUMN_NAME name
                                            where IS_NULLABLE = 'NO' AND " . $str) as $reg) {
                if ($reg->name !== $classe->getKeyName()) $validar[] = [$reg->name => 'required'];
            }
            return $validar;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function trataErro($msg): string
    {
        $tmp = explode("]", $msg);
        $tmp = explode("(", $tmp[count($tmp) - 1])[0];
        $tmp = str_replace("'", "`", $tmp);
        $tmp = str_replace('"', '`', $tmp);
        return $tmp;
    }
}
