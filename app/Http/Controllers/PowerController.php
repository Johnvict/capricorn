<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CommonController;
use App\Model\OldPowerTransaction;
use App\Model\PowerTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PowerController extends Controller
{
	private $commonCtrl, $providerId;

	public function __construct(CommonController $commonCtrl)
	{
		$this->providerId = null;
		$this->commonCtrl = $commonCtrl;
	}

	public function getProviders()
	{
		$providers = $this->commonCtrl->getBackupValues($this->commonCtrl::POWER_PROVIDER);

		// $isThere = str_contains("abuja electricity prepaid", "Abuja");
		// $isPrepaid = str_contains("abuja electricity prepaid", "postpaid");
		// return self::returnSuccess(["isItThere" => $isThere, "isPrepaid" => $isPrepaid, "isAEDC"=>$isThere && $isPrepaid]);

		// $providers = $providers->where(["shortname", "LIKE", "%abuja%"])->get();
		// $providers = $providers->where([["shortname", "LIKE", "%abuja%"], ["shortname", "LIKE", "%postpaid%"]])->first();

		$providersModified = $providers->map( function($provider) {
			$providerData =  explode("_", $provider->shortname);

			return [
				"id" => $provider->id,
				"disco" => $providerData[0],
				"type"	=> $providerData[1],
				"name"	=> $provider->name
			];
		});
		return self::returnSuccess($providersModified);
		// return self::returnSuccess($providers);
	}

	/**
	 * @param Request $request - Request body as received from the calling service
	 * @return PowerTransaction - Returns an instance of type PowerTransaction
	 */
	// ? We create a new transaction
	private function create(Request $data): PowerTransaction
	{
		$newTransaction = new PowerTransaction();
		$newTransaction->trace_id = $data->trace_id;
		$newTransaction->transaction_id = time() . rand(100, 1000);
		$newTransaction->phone_number = $data->phone_number;
		$newTransaction->provider_id = $this->provider->id;
		$newTransaction->meter_number = $data->meter_number;
		$newTransaction->amount = $data->amount;
		$newTransaction->email = $data->email;
		$newTransaction->meter_type = $data->meter_type;
		$newTransaction->status = 'incomplete';
		$newTransaction->request_time = $date = date('D jS M Y, h:i:sa');
		$newTransaction->client_request = json_encode($data->all());
		$newTransaction->date = $date;

		$newTransaction->save();

		return $newTransaction;
	}

	/**
	 * ? To update an existing transaction, created previously
	 * @param Mixed $values - Values to update in the transaction record
	 * @param number $tranactionId - Id (Primary key) of the transaction to update
	 */
	private function update($values, $transactionId)
	{
		$transaction = PowerTransaction::find($transactionId);

		/**
		 * ? We do not have a specific field to update, so we have to change the object to an iterable,
		 * ? then iterate over its values and key to pick fileds to updatein the transaction instance
		 */
		collect($values)->each(function ($value, $key) use ($transaction){
			$transaction->$key = $value;
		});

		$transaction->update();
	}

	/**
	 * ? To initiate a new Power Transaction
	 * @param Request $request - A request body to initiate a new transaction
	 */
	public function start(Request $request)
	{
		$isErrored =  self::validateRequest($request, self::$PowerValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		// ? We need to confirm if the provider exists
		// ! We have to use product id here too, instead of id.
		$this->provider = $this->commonCtrl::getOneBackupValue($this->commonCtrl::POWER_PROVIDER, "shortname", $request->disco."_".$request->meter_type);
		// dd($this->provider);
		Log::info("\n\n".$this->provider->service_type);
		Log::info($this->provider->shortname);

		/**
		 * ? Package not found. We could have gone to confirm from Capricorn,
		 * ? but we assume that this provider id was sent as a selection from a list of providers we sent to the user
		 * */
		if ($this->provider == null) return self::returnNotFound("Please provide valid provider id");
		$transaction = $this->create($request);

		$apiRequest = [
			"service_type" => $transaction->provider->service_type,
			"account_number" => $transaction->meter_number,
			"agentId" => env('CAPRICON_AGENT_ID')
		];

		// ? We need to call the 3rd party API to verify receiver account (Meter Number)
		$apiResponse = self::post(["GENERAL", "verify"], $apiRequest);

		Log::info("RESPONSE FROM 3RD PARTY");
		Log::info(json_encode($apiResponse));

		if ($apiResponse->code != 200) return self::returnProviderFailed();
		if (isset($apiResponse->data->user->name) == false) {
			return self::returnFailed("Invalid reciever");
		}

		$customerName = $apiResponse->data->user->name;
		$customerAddress = $apiResponse->data->user->address;
		$clientResponse = [
			"phone" => $transaction->phone_number,
			"meter_number" => $transaction->meter_number,
			"meter_type" => $transaction->meter_type,
			"customer_name" => $customerName,
			"customer_address" => $customerAddress,
			"amount" => $transaction->amount,
			"email" => $transaction->email,
			"transaction_id" => $transaction->transaction_id,
			"provider" => $transaction->provider->name,
			"requested_at" => $date = $transaction->request_time,
			"status" => $transaction->status,
			"date"	=> $date
		];

		$dataToUpdate = [
			"api_request" => json_encode($apiRequest),
			"api_response" => json_encode($apiResponse),
			"client_response" => json_encode($clientResponse),
			"customer_name" => $customerName,
			"customer_address" => $customerAddress,
			"response_time" => date('D jS M Y, h:i:sa'),
			"date"	=> $date
		];

		$this->update($dataToUpdate, $transaction->id);
		return self::returnSuccess($clientResponse);
	}

	public function vend(Request $request)
	{
		$vendRequestTime = date('D jS M Y, h:i:sa');
		$isErrored =  self::validateRequest($request, self::$PowerVendValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transaction = $this->commonCtrl::getOneBackupValue($this->commonCtrl::POWER_TRANSACTION['NEW'], "transaction_id", $request->transaction_id);
		if ($transaction == null) return self::returnNotFound("Please provide valid transaction id");
        if ($transaction->status == "fulfilled") return $this->clientVendResponse($transaction, $request);

		try {
			$apiVendRequest = [
				"phone" => $transaction->phone_number,
				"amount" => $transaction->amount,
				"account_number" =>  $transaction->meter_number,
				"service_type" => $transaction->provider->service_type,
				"agentReference" => $transaction->transaction_id,
				"agentId" => env('CAPRICON_AGENT_ID')
			];

			$apiVendResponse = self::post(["POWER", "vend"], $apiVendRequest);
			Log::info("\n\nRESPONSE FROM VEND POWER: " . json_encode($apiVendResponse));
			if ($apiVendResponse->code == "BX0023") return self::returnSuccess("Transaction already completed");
			if ($apiVendResponse->code == "EXC00131") return self::returnFailed("Same meter number cannot be re-used for 30 minutes after last transaction");
			if ($apiVendResponse->code != 200) return self::returnProviderFailed();

			$status = $apiVendResponse->code == 200 ? "fulfilled" : "pending";
			if ($status == 'fulfilled') self::resetBalance($transaction->amount);

			$dataToUpdate = [
				"payment_reference" => $request->payment_reference,
				"client_vend_request" => json_encode($request->all()),
				"api_vend_request" => json_encode($apiVendRequest),
				"api_vend_response" => json_encode($apiVendResponse),
				"vend_request_time" => $vendRequestTime,
				"status" => $status,
				"vend_response_time" => date('D jS M Y, h:i:sa'),
			];

			// ? These values are only attached if the tranaction type is prepaid
			if ($transaction->meter_type == "prepaid") {
				$dataToUpdate["unit"] = $apiVendResponse->data->amountOfPower;
				$dataToUpdate["token"] = $apiVendResponse->data->tokenCode;

				$clientVendResponse["unit"] = $apiVendResponse->data->amountOfPower;
				$clientVendResponse["token"] = $apiVendResponse->data->tokenCode;
			}

			$this->update($dataToUpdate, $transaction->id);
			
			return $this->clientVendResponse($transaction);
		} catch (Exception $e) {
			Log::error($e->getMessage());
			return self::returnSystemFailure();
		}
	}


	private function clientVendResponse ($transaction)
    {
		$clientVendResponse = [
			"token" => $transaction->token,
			"units" => $transaction->unit,
			"phone" => $transaction->phone_number,
			"meter_number" => $transaction->meter_number,
			"amount" => $transaction->amount,
			"meter_type" => $transaction->meter_type,
			"email" => $transaction->email,
			"customer_name" => $transaction->customer_name,
			"customer_address" => $transaction->customer_address,
			"transaction_id" => $transaction->transaction_id,
			"provider" => $transaction->provider->name,
			"requested_at" => $transaction->request_time,
			"status" => $transaction->status,
			"payment_reference" => $transaction->payment_reference,
			"date"	=> $transaction->date
		];

        $transaction->update([
			"vend_response_time" => date('D jS M Y, h:i:sA'),
            "client_vend_response"	=> json_encode($clientVendResponse),
		]);

        return self::returnSuccess(self::ResponseThirdParty($clientVendResponse, "power", "vend"));
    }


	public function history(Request $request)
	{
		$isErrored =  self::validateRequest($request, self::$TransactionHistoryValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transactionHistory = self::fetchHistory(new PowerTransaction(), new OldPowerTransaction(), $request->all(), $request->page);

		return self::returnSuccess($transactionHistory);
	}

}
