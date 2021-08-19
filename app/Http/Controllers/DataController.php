<?php

namespace App\Http\Controllers;
use App\Services\APICaller;
use App\Services\DataHelper;
use App\Services\ResponseFormat;
use App\Http\Controllers\CommonController;
use App\Model\DataTransaction;
use App\Model\OldDataTransaction;
use App\Services\HistoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
	use APICaller, ResponseFormat, DataHelper, HistoryService;
	private $commonCtrl, $transaction, $package;

	public function __construct(CommonController $commonCtrl)
	{
		$this->commonCtrl = $commonCtrl;
	}

	public function getProviders()
	{
		$provider = $this->commonCtrl->getBackupValues($this->commonCtrl::DATA_PROVIDER);
		return self::returnSuccess($provider);
	}

	public function getPackages($providerName)
	{
		$providerName = strtolower($providerName);
		$provider = $this->commonCtrl->checkValueExistence('shortname', $providerName, $this->commonCtrl::DATA_PROVIDER);

		if ($provider != null) {
			$packages = $provider->packages;
			return $this->returnSuccess($packages);
		} else {
			return $this->returnNotFound("Please provide valid provider name");
		}
	}

	private function create(Request $data): DataTransaction
	{
		$newTransaction = new DataTransaction();
		$newTransaction->trace_id = $data->trace_id;
		$newTransaction->transaction_id = time() . rand(100, 1000);
		$newTransaction->phone_number = $data->phone;
		$newTransaction->package_id = $data->package_id;
		$newTransaction->provider_id = $this->package->provider->id;
		$newTransaction->receiver = $data->receiver;
		$newTransaction->amount = $this->package->amount;
		$newTransaction->email = $data->email;
		$newTransaction->status = 'incomplete';
		$newTransaction->request_time = $date =date('D jS M Y, h:i:sa');
		$newTransaction->client_request = json_encode($data->all());
		$newTransaction->date = $date;

		$newTransaction->save();

		return $newTransaction;
	}

	private function update($values, $transactionId)
	{
		$transaction = DataTransaction::find($transactionId);

		collect($values)->each(function ($value, $key) use ($transaction) {
			$transaction->$key = $value;
		});

		$transaction->update();
	}

	public function start(Request $request)
	{
		// return self::returnSuccess($request->all());
		$providerId = strtolower($request->package_id);
		$isErrored =  self::validateRequest($request, DataHelper::$DataValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);
		$this->package = CommonController::getOneBackupValue(CommonController::DATA_PACKAGE, "id", $providerId);

		// ? Package not found. We could have gone to confirm from coralPay,
		// ? but we assume that this package id was sent as a selection from a list of packages we sent to the user
		if ($this->package == null) return self::returnNotFound("Please provide valid package id");

		$transaction = $this->create($request);

		$clientResponse = [
			"phone" => $transaction->phone_number,
			"receiver" => $transaction->receiver,
			"amount" => $this->package->amount,
			"email" => $transaction->email,
			"transaction_id" => $transaction->transaction_id,
			"package_id" => $transaction->package_id,
			"provider" => $transaction->provider->name,
			"package" => $transaction->package->name,
			"requested_at" => $date = $transaction->request_time,
			"status" => $transaction->status,
			"date" => $date = date('D jS M Y, h:i:sa')
		];

		$dataToUpdate = [
			// "api_request" => json_encode($apiRequest),
			// "api_response" => json_encode($apiResponse),
			"client_response"	=> json_encode($clientResponse),
			"response_time"		=> date('D jS M Y, h:i:sa'),
			"date"				=> $date
		];

		$this->update($dataToUpdate, $transaction->id);
		return self::returnSuccess($clientResponse);
	}

	public function vend(Request $request)
	{
		$vendRequestTime = date('D jS M Y, h:i:sa');
		$isErrored =  self::validateRequest($request, DataHelper::$DataVendValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transaction = CommonController::getOneBackupValue(CommonController::DATA_TRANSACTION['NEW'], "transaction_id", $request->transaction_id);
		if ($transaction == null) return self::returnNotFound("Please provide valid transaction id");
        if ($transaction->status == "fulfilled") return $this->clientVendResponse($transaction, $request);

		try {
			$apiVendRequest = [
				"agentReference" => $transaction->transaction_id,
				"agentId" => env('CAPRICON_AGENT_ID'),
				"datacode" => $transaction->package->datacode,
				"service_type" => $transaction->provider->service_type,
				"amount" => $transaction->package->amount,
				"phone" => $transaction->receiver
			];

			$apiVendResponse = self::post(["DATA", "vend"], $apiVendRequest);
			Log::info("\n\nRESPONSE ON VEND DATA: " . json_encode($apiVendRequest));

			if (isset($apiVendResponse->code)) {
				if ($apiVendResponse->code == "BX0023") return self::returnSuccess("Transaction already completed");
				if ($apiVendResponse->code != 200) return self::returnProviderFailed();
				// if ($apiVendResponse->code == "EXC00131") return self::returnProviderFailed("Same meter number cannot be re-used for 30 minutes after last transaction");

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
			"email" => $transaction->email,
			"transaction_id" => $transaction->transaction_id,
			"package_id" => $transaction->package_id,
			"provider" => $transaction->provider->name,
			"package" => $transaction->package->name,
			"requested_at" => $transaction->request_time,
			"status" => $transaction->status,
			"payment_reference" => $transaction->payment_reference,
			"date"	=> $transaction->date
		];

        $transaction->update([
			"vend_response_time" => date('D jS M Y, h:i:sA'),
            "client_vend_response"	=> json_encode($clientVendResponse),
		]);

        return self::returnSuccess(self::ResponseThirdParty($clientVendResponse, "data", "vend"));
    }


	public function history(Request $request)
	{
		$isErrored =  self::validateRequest($request, DataHelper::$TransactionHistoryValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transactionHistory = HistoryService::fetchHistory(new DataTransaction(), new OldDataTransaction(), $request->all(), $request->page);

		return self::returnSuccess($transactionHistory);
	}
}
