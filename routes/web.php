<?php

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

$router->get('/', function () {
	return redirect()->route('health');
});


$router->group(['prefix' => 'api/v2'], function () use ($router) {
	// ? APP STATUS
	$router->get('/health', [
		'as' => 'health',
		'uses' => 'AdminController@health',
		'middleware' => 'appState'
	]);

	// ? ADMIN ROUTES
	$router->group(['prefix' => 'manager/service'], function () use ($router) {
		$router->get('/enable', 'AdminController@enable');
		$router->get('/disable', 'AdminController@disable');
		$router->get('/balance', 'AdminController@balance');

		$router->get('/load-providers/{serviceName}', 'ProviderPackageController@loadProviders');
		$router->get('/load-packages/{serviceName}/{providerName}', 'ProviderPackageController@loadPackages');
	});
	// ? OLAP/OLTP
	$router->group(['prefix' => '/olap'], function () use ($router) {
		$router->get('/airtime', 'OLAPController@backupAirtime');
		$router->get('/data', 'OLAPController@backupData');
		$router->get('/power', 'OLAPController@backupPower');
		$router->get('/tv', 'OLAPController@backupTv');
		$router->get('/epin', 'OLAPController@backupEpin');
	});

	// ? AIRTIME TRANSACTIONS
	$router->group(['prefix' => '/airtime', 'middleware' => 'appState'], function () use ($router) {
		$router->get('/providers', 'AirtimeController@getProviders');
		$router->get('/packages/{providerName}', 'AirtimeController@getPackages');
		$router->post('/start', 'AirtimeController@start');
		$router->post('/vend', 'AirtimeController@vend');
		$router->post('/history', 'AirtimeController@history');
	});

	// ? DATA TRANSACTIONS
	$router->group(['prefix' => '/data', 'middleware' => 'appState'], function () use ($router) {
		$router->get('/providers', 'DataController@getProviders');
		$router->get('/packages/{providerName}', 'DataController@getPackages');
		$router->post('/start', 'DataController@start');
		$router->post('/vend', 'DataController@vend');
			$router->post('/history', 'DataController@history');
	});

	// ? EPIN TRANSACTIONS
	$router->group(['prefix' => '/epin', 'middleware' => 'appState'], function () use ($router) {
		$router->get('/providers', 'EpinController@getProviders');
		$router->get('/packages/{providerName}', 'EpinController@getPackages');
		$router->post('/start', 'EpinController@start');	
		$router->post('/vend', 'EpinController@vend');	
		$router->post('/history', 'EpinController@history');	
	});

	// ? TV TRANSACTIONS
	$router->group(['prefix' => '/tv', 'middleware' => 'appState'], function () use ($router) {
		$router->get('/providers', 'TVController@getProviders');
		$router->get('/packages/{providerName}', 'TVController@getPackages');
			$router->post('/start', 'TVController@start');
			$router->post('/vend', 'TVController@vend');
			$router->post('/history', 'TVController@history');
	});

	// ? POWER TRANSACTIONS
	$router->group(['prefix' => '/power', 'middleware' => 'appState'], function () use ($router) {
		$router->get('/providers', 'PowerController@getProviders');
		$router->get('/packages/{providerId}', 'PowerController@getPackages');
		$router->post('/start', 'PowerController@start');
		$router->post('/vend', 'PowerController@vend');
		$router->post('/history', 'PowerController@history');
	});
});
