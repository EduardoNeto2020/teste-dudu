<?php
/* cSpell:disable */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;
use PHPJasper\PHPJasper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

/**
 * Classe para emissão de relatórios com uso do Jasper Reports
 */
class ReportsController extends BaseController
{

  /**
   * Gerar o relatório e disponibiliza para download
   *
   * @param String nome do arquivo do relarório
   * @param Model Classe de modelo de onde virão os dados
   * @param String tipo de arquivo de saída (PDF,XLS,CSV,DOCX,RTF,ODT,ODS e XLSX)
   * @param JSON conjunto de dados para geração do relatório
   * @param Request Paramentros para busca
   * @return void
   */
  public function gerar(String $nome, String $classe, String $formato, $json = null, Request $request = null): Response | JsonResponse
  {
    $formatos["PDF"] = "application/pdf";
    $formatos["XLS"] =  "application/vnd.ms-excel";
    $formatos["CSV"] = "text/csv";
    $formatos["DOCX"] = "application/msword";
    $formatos["RTF"] = "application/rtf";
    $formatos["ODT"] = "application/vnd.oasis.opendocument.text";
    $formatos["ODS"] = "application/vnd.oasis.opendocument.spreadsheet";
    $formatos["XLSX"] = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

    $nome = strtolower($nome);

    $input = app()->basePath('public/') . "/reports/$nome.jrxml";
    $output = app()->basePath('public/') . "/reports/" . time() . "relatorio";
    $data_file = app()->basePath('public/') . "/reports/" . time() . "dados.json";

    if ($json == null) {
        try {
            $tmp = new $classe(usuario: $request->session()->get('user'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $tmp = new $classe();
        }
      $tmp = new $classe;
      $tmp->completo = true;
      $resul = $tmp->buscar($request->all());
      $json = json_encode(["dados" => $resul]);
    }

    file_put_contents($data_file, $json);

    $formato = strtoupper($formato);

    switch ($formato) {
      case 'XLS':
        $options = [
          'format' => ["xls"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
      case 'CSV':
        $options = [
          'format' => ["csv"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
      case 'DOCX':
        $options = [
          'format' => ["docx"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
      case 'RTF':
        $options = [
          'format' => ["rtf"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
      case 'ODT':
        $options = [
          'format' => ["odt"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
      case 'ODS':
        $options = [
          'format' => ["ods"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
      case 'XLSX':
        $options = [
          'format' => ["xlsx"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;

      default:
        $options = [
          'format' => ["pdf"],
          'params' => [],
          'locale' => 'pt_BR',
          'db_connection' => [
            'driver' => 'json',
            'data_file' => $data_file,
            'json_query' => 'dados'
          ]
        ];
        break;
    }

    try {
      $report = new PHPJasper();

      $report->process(
        $input,
        $output,
        $options
      )->execute();
    } catch (\Exception  $e) {
      Log::error($e->getMessage());
      return response()->json([$e->getMessage()], Response::HTTP_BAD_REQUEST);
    }

    $file = $output . '.' . strtolower($formato);
    $path = $file;

    if (!file_exists($file)) {
      Log::error("Arquivo $file não localizado.");
      return response()->json(["erro" => "Erro ao gerar retatório"], Response::HTTP_BAD_REQUEST);
    }

    $file = file_get_contents($file);

    unlink($path);
    unlink($data_file);

    return response($file, 200)
      ->header('Content-Type', $formatos[$formato])
      ->header('lang', 'pt-BR')
      ->header('Content-Disposition', 'inline; filename="' . $nome . '."' . $formato);
  }
}
