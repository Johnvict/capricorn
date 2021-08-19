<?php

namespace App\Http\Controllers;

use App\Services\APICaller;
use App\Services\DataHelper;
use App\Services\ResponseFormat;
use App\Http\Controllers\CommonController;
use App\Model\EpinTransaction;
use App\Model\OldEpinTransaction;
use App\Services\HistoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EpinController extends Controller
{
	use APICaller, ResponseFormat, DataHelper, HistoryService;
	private $commonCtrl, $providerName, $transaction, $package;

	public function __construct(CommonController $commonCtrl)
	{
		$this->providerName = null;
		$this->commonCtrl = $commonCtrl;
	}

	public function getProviders()
	{
		$provider = $this->commonCtrl->getBackupValues($this->commonCtrl::EPIN_PROVIDER);
		return self::returnSuccess($provider);
	}

	public function validateProvider($providerName)
	{
		return $this->commonCtrl->checkValueExistence('shortname', $providerName, $this->commonCtrl::EPIN_PROVIDER);
	}

	public function getPackages($providerName)
	{
		$provider = $this->validateProvider($providerName);
		if ($provider != null) {
			$packages = collect($provider->packages);
			return $this->returnSuccess($packages);
		} else {
			return $this->returnNotFound("Please provide valid provider name");
		}
	}

	private function create(Request $data): EpinTransaction
	{
		$amount = ($data->unit ?? 1) * $this->package->amount;

		$newTransaction = new EpinTransaction();
		$newTransaction->trace_id = $data->trace_id;
		$newTransaction->transaction_id = time() . rand(100, 1000);
		$newTransaction->phone_number = $data->phone;
		$newTransaction->package_id = $data->package_id;
		$newTransaction->provider_id = $this->package->provider->id;
		$newTransaction->receiver = $data->receiver;
		$newTransaction->unit = $data->unit ?? 1;
		$newTransaction->amount = $amount;
		$newTransaction->email = $data->email;
		$newTransaction->status = 'incomplete';
		$newTransaction->request_time = $date = date('D jS M Y, h:i:sa');
		$newTransaction->client_request = json_encode($data->all());
		$newTransaction->date = $date;
		
		$newTransaction->save();

		return $newTransaction;
	}

	private function update($values, $transactionId)
	{
		$this->transaction = EpinTransaction::find($transactionId);

		collect($values)->each(function ($value, $key) {
			$this->transaction->$key = $value;
		});

		$this->transaction->update();
	}

	public function start(Request $request)
	{
		$isErrored =  self::validateRequest($request, DataHelper::$EpinValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);
		$this->package = CommonController::getOneBackupValue(CommonController::EPIN_PACKAGE, "id", $request->package_id);
		// ? Package not found. We could have gone to confirm from coralPay,
		// ? but we assume that this package id was sent as a selection from a list of packages we sent to the user
		if ($this->package == null) return self::returnNotFound("Please provide valid package id");

		$transaction = $this->create($request);

		$clientResponse = [
			"phone" => $transaction->phone_number,
			"receiver" => $transaction->receiver,
			"amount" => $transaction->amount,
			"unit" => $transaction->unit,
			"email" => $transaction->email,
			"transaction_id" => $transaction->transaction_id,
			"package_id" => $transaction->package_id,
			"provider" => $transaction->provider->name,
			"package" => $transaction->package->description,
			"requested_at" => $date = $transaction->request_time,
			"status" => $transaction->status,
			"date"	=> $date
		];

		$dataToUpdate = [
			// "api_request" => json_encode($apiRequest),
			// "api_response" => json_encode($apiResponse),
			"client_response" => json_encode($clientResponse),
			"response_time" => date('D jS M Y, h:i:sa'),
			"date"	=> $date
		];

		$this->update($dataToUpdate, $transaction->id);
		return self::returnSuccess($clientResponse);
	}

	public function vend(Request $request)
	{
		// return self::returnSuccess($request->all());
		$vendRequestTime = date('D jS M Y, h:i:sa');
		$isErrored =  self::validateRequest($request, DataHelper::$DataVendValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transaction = CommonController::getOneBackupValue(CommonController::EPIN_TRANSACTION['NEW'], "transaction_id", $request->transaction_id);
		if ($transaction == null) return self::returnNotFound("Please provide valid transaction id");
        if ($transaction->status == "fulfilled") return $this->clientVendResponse($transaction, $request);

		try {
			$apiVendRequest = [
				"numberOfPins" =>  $transaction->unit,
				"service_type" => $transaction->provider->service_type,
				"pinValue" => $transaction->package->amount,
				"amount" => $transaction->amount,
				"agentReference" => $transaction->transaction_id,
				"agentId" => env('CAPRICON_AGENT_ID')
			];

			$apiVendResponse = self::post(["EPIN", "vend"], $apiVendRequest);
			Log::info("\n\nRESPONSE FROM VEND EPIN: " . json_encode($apiVendResponse));
			
			if (isset($apiVendResponse->code)) {
				if ($apiVendResponse->code == "BX0023") return self::returnSuccess("Transaction already completed");
				if ($apiVendResponse->code == "BX0003") return self::returnFailed("Selected number of unit is not available");
				// if ($apiVendResponse->code == "EXC00131") return self::returnProviderFailed("Same meter number cannot be re-used for 30 minutes after last transaction");
				if ($apiVendResponse->code != 200) return self::returnProviderFailed();

				$status = $apiVendResponse->code == 200 ? "fulfilled" : "pending";
				if ($status == 'fulfilled') self::resetBalance($transaction->amount);

				if (isset($apiVendResponse->data->pins)) {
					$pins = collect($apiVendResponse->data->pins);
					$pins = $pins->map(function ($pin) {
						return ["pin" => $pin->pin, "expires_on" => $pin->expiresOn, "serialNumber" => $pin->serialNumber];
					});
				}

				$dataToUpdate = [
					"payment_reference" => $request->payment_reference,
					"pins" => json_encode($pins),
					"client_vend_request" => json_encode($request->all()),
					"api_vend_request" => json_encode($apiVendRequest),
					"api_vend_response" => json_encode($apiVendResponse),
					"vend_request_time" => $vendRequestTime,
					"status" => $status,
					"vend_response_time" => date('D jS M Y, h:i:sa'),
				];

				$transaction->update($dataToUpdate);
				
				return $this->clientVendResponse($transaction);
			}
			Log::error("@Class DataProvider \n@Method vend");
			Log::error("Error occured while fetching data from API");
			return self::returnProviderFailed();
		} catch (Exception $e) {
			Log::error($e->getMessage());
			return self::returnSystemFailure();
		}
	}


	private function clientVendResponse ($transaction)
    {
		$clientVendResponse = [
			"phone" => $transaction->phone_number,
			"receiver" => $transaction->receiver,
			"amount" => $transaction->amount,
			"unit" => $transaction->unit,
			"pins" => $transaction->pins,
			"email" => $transaction->email,
			"transaction_id" => $transaction->transaction_id,
			"package_id" => $transaction->package_id,
			"provider" => $transaction->provider->name,
			"package" => $transaction->package->description,
			"requested_at" => $transaction->request_time,
			"status" => $transaction->status,
			"payment_reference" => $transaction->payment_reference,
			"date" => $transaction->date = date('D jS M Y, h:i:sa')
		];

        $transaction->update([
			"vend_response_time" => date('D jS M Y, h:i:sA'),
            "client_vend_response"	=> json_encode($clientVendResponse),
		]);

        return self::returnSuccess(self::ResponseThirdParty($clientVendResponse, "education", "vend"));
    }


	public function history(Request $request)
	{
		$isErrored =  self::validateRequest($request, self::$TransactionHistoryValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transactionHistory = self::fetchHistory(new EpinTransaction(), new OldEpinTransaction(), $request->all(), $request->page);

		return self::returnSuccess($transactionHistory);
	}
}
