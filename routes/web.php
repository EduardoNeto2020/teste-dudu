<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// $router->get('/', function () use ($router) {
//     return $router->app->version();
// });

$router->get('/', function () use ($router) {
    return phpinfo();
});

$router->post('login', 'AuthController@login');

/**
 *  Rotas do núcleo do sistema
 */
// ********************* Rotas de controle da entidade *********************

//Entidade
$router->get('entidade', 'EntidadeController@mostrar');

//Entidade
$router->get('contrato', 'ContratoController@mostrar');
$router->get('empenho', 'EmpenhoController@mostrar');
$router->get('liquidacao', 'LiquidacaoController@mostrar');
$router->get('pagamento', 'PagamentoController@mostrar');

