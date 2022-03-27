<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use App\Models\Objeto;
use Illuminate\Support\Facades\Log;
use \Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\ReportsController;

class Controller extends BaseController
{

    public Bool $jsonRet = true;

    /**
     * Classe base modelo.
     */
    protected Model $classe;
    /**
     * Representa a classe que herdou os atributos e métodos do modelo
     *
     * @var Class
     */
    protected String $nomeClasse;

    /**
     * construtor da classe
     *
     * @param Modelo $classe
     * @param Bool $autenticado
     *
     */
    public function __construct(Model &$classe, Bool $autenticado = true, array $authExcept = [])
    {

        $this->classe = $classe;

        //Verifica se o middlware foi fornecido na classe filha

        // TODO Ajustar QRCode
        $authExcept[] = 'login';
        $authExcept[] = 'lerQRCode';

        if (empty($this->middleware) && $autenticado) {
            $this->middleware('jwt.auth', ['except' => $authExcept]);
        }

        $this->nomeClasse = (string) get_class($classe);
    }

    /**
     * Valida campos obrigatórios da reuisição
     *
     * @param Request $request
     * @return void
     */
    public function validar(Request &$request): void
    {
        $this->validate(
            $request,
            $this->classe->getValidar(),
            $this->classe->getMensagemValidar()
        );
    }

    /**
     * Valida campos obrigatórios da reuisição
     *
     * @param Request $request
     * @return void
     */
    public function validarRequest(Request &$request): void
    {
        $this->validate(
            $request,
            $this->classe->getValidarRequest(),
            $this->classe->getMensagemValidar()
        );
    }

    /**
     * Retorna lista de registros cadastrados
     *
     * @param Request $request - Parâmetros de busca
     * @param String $campos - Lista de campos para filtro
     * @param Bool $name Bool $completo - indica se serão retornados os objetos dependentes
     * @return Json
     *
     */
    public function mostrar(Request $request, String $campos = '', Bool $completo = true): jsonResponse| Response
    {

        $tabela = $this->classe->getTable();
        $resul = null;

        try {
            $this->classe->setUsuario($request->session()->get('user'));
            $json = new \App\Models\JsonStore([], true, $request->session()->get('user'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $json = new \App\Models\JsonStore();
        }

        $json->origem = $tabela;

        try {
            $this->validarRequest($request);
        } catch (ValidationException $e) {
            return response()->json($e->errors(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $parametro = [];
            if (count($request->all())) {
                if ($request->has('completo')) {
                    $completo = filter_var($request->input('completo'), FILTER_VALIDATE_BOOLEAN);
                }
                $parametro = null;
                if (count($request->all()) == 1 && $request->has('filtro')) {
                    $strparametro = '';
                    $temParam = false;
                    $filtro = $request->input('filtro');
                    $parametros_banco = $this->classe->retornaParametros();
                    foreach ($parametros_banco as $parametro_banco) {
                        $nome = $parametro_banco->name;
                        if ($nome == 'criterio') {
                            $temParam = true;
                        }
                        if (is_numeric(strpos($campos, $nome))) {
                            switch ($parametro_banco->tipo) {
                                case 'date':
                                case 'datetime':
                                case 'numeric':
                                case 'int':
                                    if (is_numeric($filtro)) {
                                        $strparametro .= "$tabela.$nome = $filtro or ";
                                        break;
                                    }
                                default:
                                    $strparametro .= "$tabela.$nome like ''%$filtro%'' or ";
                            }
                        }
                    }

                    if (strlen($strparametro) > 0 && $temParam) {
                        $strparametro = substr($strparametro, 0, strlen($strparametro) - 3);
                        $parametro = ['criterio' => $strparametro];
                    }
                } else {
                    $parametro = $request->all();
                }
            }

            $tmp =  $this->classe->getTable();

            try {
                $json->buscarJson($tmp, $parametro);
                $resul = [];
                if (strlen($json->jsonContent) > 2 && $this->jsonRet) {

                    return response($json->jsonContent, Response::HTTP_OK);
                }
            } catch (\Exception $th) {
                if (env("DEBUG")) {
                    Log::error($th->getMessage());
                }
            }

            $classes = $this->classe->buscar($parametro, $completo);


            $strJson = [];
            if (count($classes) > 0) {
                foreach ($classes as $cls) {
                    $strJson[] = json_encode($cls);
                }
                $json->jsonContent = "[" . implode(",", $strJson) . "]";
                try {
                    $json->salvar();
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            }

            if ($classes) {
                $resul =  response()->json($classes, Response::HTTP_OK);
            } else {
                $resul = response()->json([], Response::HTTP_OK);
            }

            return $resul;
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Persiste os dados da tela
     *
     * @param Request $request - dados a serem persistidos
     * @return Json
     *
     */
    public function salvar(Request $request): JsonResponse
    {
        try {
            $this->classe->setUsuario($request->session()->get('user'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        try {
            $this->validar($request);
        } catch (ValidationException $e) {
            return response()->json($e->errors(), Response::HTTP_BAD_REQUEST);
        }
        try {
            $this->classe->salvar((array) $request->all());
            $pk = $this->classe->getKeyName();
            $this->classe->carrega([$pk => $this->classe->getAttributeValue($pk)]);
            return response()->json($this->classe, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Exclui um registro
     *
     * @param Request $request - Dados a serem excluidos
     * @return Json
     *
     */
    public function deletar(Request $request): JsonResponse
    {
        try {
            $this->classe->setUsuario($request->session()->get('user'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        try {
            $this->classe->definir((array) $request->all());
            $this->classe->deletar();
            return response()->json([], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Carrega dados a partir de um arquivo JSON
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lerArquivo(Request $request): JsonResponse
    {

        $resul = [];

        if (count($request->file()) == 0) {
            return response()->json(["erro" => "Necessário enviar arquivo válido"], Response::HTTP_BAD_REQUEST);
        }

        foreach ($request->file() as $file) {
            $fp = file_get_contents($file->getRealPath());
            foreach (json_decode($fp)->data as $linha) {

                $tmp = new $this->nomeClasse;
                if (count($tmp->uniqueKeys) > 0) {
                    $param = [];
                    foreach ($tmp->uniqueKeys[0] as $key) {
                        try {
                            $param[$key] = $linha->$key;
                        } catch (\Throwable $th) {
                        }
                    }
                    if (count($param) > 0) {
                        $tmp->carrega($param);
                    }
                }
                $tmp->definir((array) $linha);
                try {
                    $tmp->salvar(salvarFilhos: false);
                    $resul[] = $tmp;
                } catch (\Throwable $th) {
                    $resul[] = ["Erro" => $th->getMessage(), "linha" => (array) $linha];
                }
            }
        }

        return response()->json($resul, Response::HTTP_OK);
    }



    // TODO Ajustar o imprimir
    public function imprimir(Request $request): Response | JsonResponse
    {
        $reportName = str_replace("/", "_", $request->path());
        $reportFormat = 'PDF';

        if ($request->has('reportName')) {
            $reportName = $request->input('reportName');
        }

        if ($request->has('reportFormat')) {
            $reportFormat = $request->input('reportFormat');
        }

        $rel = new ReportsController();
        return $rel->gerar($reportName, (string) $this->nomeClasse, $reportFormat, null, $request);
    }

    /**
     * executa a script informada
     *
     * @param Request $request
     * @return Json
     */
    public function executar(Request $request): JsonResponse
    {
        try {
            $this->classe->setUsuario($request->session()->get('user'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        try {
            $script = $request->input('script');
            $request->request->remove('script');
            $cls = $this->classe->exec($script, (array) $request->all());
            return response()->json($cls, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // TODO Ajustar o gerarQRCode
    public function gerarQRCode(Request $request): Response
    {
        try {
            $this->classe->setUsuario($request->session()->get('user'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        $return = $request->getHttpHost();
        $tmp = $this->classe->buscar((array) $request->all())[0];
        $pk = $tmp->getAttributeValue($this->classe->getKeyName());
        $Classe = strtolower(str_replace('App\\', '', (string) $this->nomeClasse));

        $dt = new \DateTime();
        $p1 = $dt->format('sY');
        $p2 = $dt->format('Hs');

        $qr_code = ""; # new QrCode();

        $image = $qr_code::format('png')
            ->size(300)->errorCorrection('H')
            ->generate("$return/$Classe/qrcode/$p1" . "a" . "$pk" . "z" . "$p2");
        return response($image)->header('Content-type', 'image/png');
    }

    // TODO Ajustar o lerQRCode
    public function lerQRCode($qrcode)
    {

        $pk = explode("a", $qrcode);
        $pk = explode("z", $pk[1]);
        $pk = $pk[0];
        /* $Classe = strtolower(str_replace('App\\', '', (String) $this->nomeClasse));

        $vcard = new VCard();
        $vcard->carrega(['objeto' => $Classe, 'chave' => $pk]);
        $vcard = $vcard->cartao;

        return response($vcard)
            ->header('Content-Type', 'text/vcard')
            ->header('lang', 'pt-BR')
            ->header('Content-Disposition', 'inline; filename="card.vcf"');
        */
    }

    public function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null,
        ], 200);
    }

    public function imagem($fileName): Response
    {

        $path = url('/') . "/img/$fileName";

        if (File::exists($path)) {

            $filetype = mime_content_type($path);
            $file = file_get_contents($path);
            $name = basename($path);


            return response($file, 200)
                ->header('Content-Type', $filetype)
                ->header('Content-Disposition', 'inline; filename="' . $name . '"');
        }
    }

    public function arquivoLocal($path, $fileName): Response
    {
        $path = url('/') . "/$path/$fileName";

        if (File::exists($path)) {

            $filetype = mime_content_type($path);
            $file = file_get_contents($path);
            $name = basename($path);


            return response($file, 200)
                ->header('Content-Type', $filetype)
                ->header('Content-Disposition', 'inline; filename="' . $name . '"');
        }
    }

    //TODO implementar Objeto
    public function bancoArquivo(Request $request): Response
    {

        try {
            $obj = new Objeto();
            try {
                $obj->setUsuario($request->session()->get('user'));
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }

            $obj = $obj->buscar($request->all())[0];

            $tmp = substr(strval($obj->objeto), 2);

            return response(hex2bin($tmp), 200)
                ->header('Content-Type', $obj->tipoObjeto)
                ->header('Content-Disposition', 'inline; filename="' . $obj->nomeObjeto . '"');
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    //TODO implementar Objeto
    public function arquivoBanco(Request $request): bool
    {

        try {

            $obj = new Objeto();
            try {
                $obj->setUsuario($request->session()->get('user'));
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }

            if (!isset($_FILES["myfile"])) {
                return false;
            }

            if (is_array($_FILES["myfile"]["name"])) {
                return false;
            }

            ini_set('memory_limit', '-1');

            $file = $_FILES["myfile"];
            $tamanho = $file["size"];
            if ($tamanho >= 419430400) {
                return response()->json(['Este arquivo é muito grande'], Response::HTTP_BAD_REQUEST);
            }

            $arquivo_temp = $file["tmp_name"];
            $nome_arquivo = $file["name"];

            if (file_exists($arquivo_temp)) {

                $fp = fopen($arquivo_temp, "r");

                $dados_documento = fread($fp, filesize($arquivo_temp));

                $dados = strtoupper(bin2hex($dados_documento));

                $md5 = strtoupper(md5_file($arquivo_temp));

                fclose($fp);

                if (!$request->has('origemObjeto')) {
                    $request['origemObjeto'] = $this->nomeClasse;
                }
                if (!$request->has('idOrigem')) {
                    $request['idOrigem'] = $request->input($this->classe->primaryKey());
                }
                if (!$request->has('nomeObjeto')) {
                    $request['nomeObjeto'] = str_replace("'", "`", $nome_arquivo);
                }
                if (!$request->has('descricaoObjeto')) {
                    $request['descricaoObjeto'] = $request->has('descricaoObjeto') ? $request->has('descricaoObjeto') : "Arquivo de $this->nomeClasse";
                }
                $request['md5'] = $md5;
                $request['objeto'] = "0x" . $dados;
                if (!$request->has('comentario')) {
                    $request['comentario'] = "Arquivo de " . $this->nomeClasse;
                }

                $obj = new Objeto($request->all());
                $obj->salvar();

                ini_set('memory_limit', '400M');

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()], Response::HTTP_EXPECTATION_FAILED);
        }
    }
}
