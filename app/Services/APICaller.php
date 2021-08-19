<?php

namespace App\Services;

trait APICaller {
    public static $coralUrl, $authorization, $requestUrl, $urlSet;


	/**
	 * ? WE DEFINE some variables that can change if the 3rd Party Service decides to change them in future
	 * ? so that we can get them changed much easily from here
	 * !! NOTE THAT: This function is called from the App\Providers\AppServiceProvider boot function
	 * !! That means, it is called every time a request hits this microservice.. We can't afford to use updated values
	 */
    public static function init() {
        APICaller::$coralUrl = env('CAPRICON_URL');
        APICaller::$authorization = env('CAPRICON_AUTH');
        APICaller::$urlSet = [
			"TV" => [
				"providers" => "/services/cabletv/providers",
				"packages" => "/services/multichoice/list",
				"vend" => "/services/multichoice/request"
			],
			"AIRTIME" => [
				"providers" => "/services/airtime/providers",
				"vend" => "/services/airtime/request"
			],
			"DATA" => [
				"providers" => "/services/databundle/providers",
				"packages" => "/services/databundle/bundles",
				"vend" => "/services/databundle/request"
			],
			"POWER" => [
				"providers" => "/services/electricity/billers",
				"vend" => "/services/electricity/request"
			],
			"EPIN" => [
				"providers" => "/services/epin/providers",
				"packages" => "/services/epin/bundles",
				"vend" => "/services/epin/request"
			],
			"GENERAL" => [
				"verify" => "/services/namefinder/query",
				"balance" => "/superagent/account/balance"
			]
        ];

    }


	/**
	 * ? The URLs we need to use are mapped from the array in init() method above
	 * @param Array $urlKeys - This is the indices of the $urlSet of how we can get the needed URL
	 * e.g. To get https://payments.baxipay.com.ng/api/baxipay/superagent/account/balance $urlKeys will be ["GENERAL", "balance"]
	 */
    public static function getUrl(array $urlKeys) {
        APICaller::$requestUrl = APICaller::$urlSet;
        $count = count($urlKeys);
        for ($i = 0; $i < $count; $i++) {
            APICaller::$requestUrl = APICaller::$requestUrl[$urlKeys[$i]];
        }

        APICaller::$requestUrl = APICaller::$coralUrl.APICaller::$requestUrl;
    }

    public static function get(array $urlStack) {
		APICaller::getUrl($urlStack);
		// dd(APICaller::$requestUrl);

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => APICaller::$requestUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Accept: application/json",
			"baxi-date: " .date(env('CAPRICON_TIME_FORMAT')),
			"Authorization: ".APICaller::$authorization
          ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            // return $error;
        } else {
            // success
            return json_decode($response);
        }
    }
    public static function post($urlStack, $data) {
        APICaller::getUrl($urlStack);

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => APICaller::$requestUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => json_encode($data),
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
			"Accept: application/json",
			"Connection: keep-alive",
			"Cache-Control: no-cache",
			"baxi-date: " .date(env('CAPRICON_TIME_FORMAT')),
			"Authorization: ".APICaller::$authorization
          ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return $error;
        } else {
            // success
            return json_decode($response);
        }
    }

    public static function getPlain($urlStack, $id = null) {
		APICaller::getUrl($urlStack);

		if ($id != null) APICaller::$requestUrl = APICaller::$requestUrl . '/' . $id;
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => APICaller::$requestUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"baxi-date: " .date(env('CAPRICON_TIME_FORMAT')),
            "Authorization: ".APICaller::$authorization
          ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            // return $error;
        } else {
            // success
            return json_decode($response);
        }
    }

}

