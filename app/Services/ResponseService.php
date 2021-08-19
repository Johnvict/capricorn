<?php

namespace App\Services;

use Exception;

trait ResponseService
{
	public static $Response3rdParty = [
		"create"	=> [
			"data"	=> [
				"phone",
				"receiver",
				"amount",
				"email",
				"transaction_id",
				"provider",
				"package",
				"requested_at",
				"status",
				"date",
			],
			"tv"	=> [
				"phone",
				"card_number",
				"amount",
				"email",
				"customer_name",
				"transaction_id",
				"provider",
				"package",
				"requested_at",
				"due_date",
				"date",
				"status"
			],
			"power"	=> [
				"phone",
				"meter_number",
				"meter_type",
				"customer_name",
				"customer_address",
				"amount",
				"email",
				"transaction_id",
				"provider",
				"requested_at",
				"date",
				"status",
			],
			"transfer" => [
				"phone",
				"account_number",
				"account_name",
				"amount",
				"email",
				"transaction_id",
				"bank",
				"requested_at",
				"status",
				"date"
			],
		],

		"vend"		=> [
			"airtime"	=> [
				"phone",
				"receiver",
				"amount",
				"email",
				"transaction_id",
				"provider",
				"requested_at",
				"status",
				"payment_reference",
				"date",
			],
			"data"		=> [
				"phone",
				"receiver",
				"amount",
				"email",
				"transaction_id",
				"provider",
				"package",
				"requested_at",
				"status",
				"payment_reference",
				"date",
			],
			"tv"		=> [
				"phone",
				"receiver",
				"customer_name",
				"amount",
				"email",
				"transaction_id",
				"provider",
				"package",
				"requested_at",
				"status",
				"date",
			],
			"power"		=> [
				"phone",
				"amount",
				"email",
				"customer_name",
				"transaction_id",
				"provider",
				"payment_reference",
				"meter_number",
				"customer_address",
				"token",
				"units",
				"status",
				"date",
				"requested_at",
			],
			"transfer"	=> [
				"phone",
				"receiver",
				"account_name",
				"amount",
				"email",
				"transaction_id",
				"provider",
				"bank",
				"requested_at",
				"status",
				"payment_reference",
				"date"
			],

			"education" => [
				"phone",
				"receiver",
				"amount",
				"unit",
				"pins",
				"email",
				"transaction_id",
				"package_id",
				"provider",
				"package",
				"requested_at",
				"status",
				"payment_reference",
				"date",
			]
		],
	];

	public static $ResponseMain = [
		"create"	=> [
			"airtime"	=> [
				"amount"
			],
			"data"		=> [
				"amount"
			],
			"tv"		=> [
				"amount"
			],
			"power"		=> [
				"amount"
			],
			"transfer"	=> [
				"amount"
			]
		],
		"vend"		=> [
			"airtime"	=> [
				"amount"
			],
			"data"		=> [
				"amount"
			],
			"tv"		=> [
				"amount"
			],
			"power"		=> [
				"amount"
			],
			"transfer"	=> [
				"amount"
			]
		]
	];

	public static function stopOnError($message)
	{
		dd("YOUR RESPONSE NEEDS MORE DATA KEY(S)", $message);
	}

	/**
	 * **USED TO CHECK FORMAT FOR 3RD PARTY MICROSERVICES**
	 * @param array $response Response body
	 * @param string $service **Must be any of the services:**
	 *  - **airtime**
	 * 	- **data**
	 * 	- **tv**
	 * 	- **power**
	 * 	- **transfer**
	 * @param string $stage  **Must be any of the transaction request stage:**
	 *  - **create**
	 * 	- **vend**
	 */
	public static function ResponseThirdParty(array $response, $service, $stage)
	{
		try {

			collect(ResponseService::$Response3rdParty[$stage][$service])->each(function ($keyValue) use ($response) {
				if (!array_key_exists($keyValue, $response)) throw new Exception("'$keyValue' must be added to response body");
			});
			return $response;
		} catch (Exception $error) {
			ResponseService::stopOnError($error->getMessage());
		}
	}


	/**
	 * **USED TO CHECK FORMAT FOR MAIN MICROSERVICES**
	 * @param array $response Response body
	 * @param string $service **Must be any of the services:**
	 *  - **airtime**
	 * 	- **data**
	 * 	- **tv**
	 * 	- **power**
	 * 	- **transfer**
	 * @param string $stage **Must be any of the transaction request stage:**
	 *  - **create**
	 * 	- **vend**
	 */
	public static function ResponseMain(array $response, $service, $stage)
	{
		try {
			collect(ResponseService::$ResponseMain[$stage][$service])->each(function ($keyValue) use ($response) {
				if (!array_key_exists($keyValue, $response)) throw new Exception("'$keyValue' must be added to response body");
			});
			return $response;
		} catch (Exception $error) {
			ResponseService::stopOnError($error->getMessage());
		}
	}
}
