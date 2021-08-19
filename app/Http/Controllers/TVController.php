<?php

namespace App\Http\Controllers;
// use Illuminate\Support\Collection;
use App\Services\APICaller;
use App\Services\ResponseFormat;
use App\Http\Controllers\CommonController;
use App\Model\OldTvTransaction;
use App\Model\TvTransaction;
use App\Services\DataHelper;
use App\Services\HistoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TVController extends Controller
{
	private $commonCtrl, $providerId, $package, $dataJson = '[';

	public function __construct(CommonController $commonCtrl)
	{
		$this->providerId = null;
		$this->commonCtrl = $commonCtrl;
	}

	public function getProviders()
	{
		$provider = $this->commonCtrl->getBackupValues($this->commonCtrl::TV_PROVIDER);
		return self::returnSuccess($provider);
	}


	public function validateProvider($providerId)
	{
		return $this->commonCtrl->checkValueExistence('id', $providerId, CommonController::TV_PROVIDER);
	}

	public function getPackages($providerName)
	{
		$provider = CommonController::getOneBackupValue(CommonController::TV_PROVIDER, 'shortname', $providerName);
		if ($provider != null) {
			$packages = $provider->packages;
			return $this->returnSuccess($packages);
		} else {
			return $this->returnNotFound("Please provide valid provider name");
		}

		// $this->length = count($data);
		// $data->each(function ($item, $count) {

		// 	$detail = collect($item->availablePricingOptions)->filter(function($itemPlan) {
		// 		return $itemPlan->monthsPaidFor == 1 ? true : false;
		// 	});

		// 	if (count($detail) > 0) {
		// 		$detail = $detail[0];
		// 		$this->dataJson = $this->dataJson . '{"provider_id": '.$this->providerId . ',"name": '. "\"".$item->name."\"".',"allowance": null, "validity": null, "price": '.$detail->price .', "datacode":'."\"".$item->code."\"".'}';
		// 		if ($count < $this->length - 1) {
		// 			$this->dataJson = $this->dataJson . ',';
		// 		}
		// 	}
		// });

		// $this->dataJson = $this->dataJson . ']';
		// $data = collect((json_decode($this->dataJson)));

		// $this->commonCtrl->createBackupPackages($data, CommonController::TV_PACKAGE);
	}


	public function create(Request $data): TvTransaction
	{
		$newTransaction = new TvTransaction();
		$newTransaction->trace_id = $data->trace_id;
		$newTransaction->transaction_id = time() . rand(100, 1000);
		$newTransaction->phone_number = $data->phone;
		$newTransaction->package_id = $data->package_id;
		$newTransaction->provider_id = $this->package->provider->id;
		$newTransaction->receiver = $data->receiver;
		$newTransaction->amount = $this->package->amount;
		$newTransaction->email = $data->email;
		$newTransaction->status = 'incomplete';
		$newTransaction->request_time = $date = date('D jS M Y, h:i:sa');
		$newTransaction->client_request = json_encode($data->all());
		$newTransaction->date = $date;

		$newTransaction->save();

		return $newTransaction;
	}

	public function update($values, $transactionId)
	{
		$transaction = TvTransaction::find($transactionId);

		collect($values)->each(function ($value, $key) use ($transaction) {
			$transaction->$key = $value;
		});

		$transaction->update();
	}

	public function start(Request $request)
	{
		$isErrored =  self::validateRequest($request, DataHelper::$TvValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);
		$this->package = CommonController::getOneBackupValue(CommonController::TV_PACKAGE, "id", $request->package_id);

		// ? Package not found. We could have gone to confirm from coralPay,
		// ? but we assume that this package id was sent as a selection from a list of packages we sent to the user
		if ($this->package == null) return self::returnNotFound("Please provide valid package id");
		$transaction = $this->create($request);

		$apiRequest = [
			"service_type" => $transaction->package->provider->service_type,
			"account_number" => $transaction->receiver,
			"agentId" => env('CAPRICON_AGENT_ID'),
		];

		$apiResponse = self::post(["GENERAL", "verify"], $apiRequest);

		if ($apiResponse->code != 200) return self::returnProviderFailed();
		if (isset($apiResponse->data->user->name) == false) {
			return self::returnFailed("Invalid reciever");
		}

		$customerName = $apiResponse->data->user->name;
		$clientResponse = [
			"phone" => $transaction->phone_number,
			"receiver" => $transaction->receiver,
			"customer_name" => $customerName,
			"amount" => $this->package->amount,
			"email" => $transaction->email,
			"transaction_id" => $transaction->transaction_id,
			"package_id" => $transaction->package_id,
			"provider" => $transaction->provider->name,
			"package" => $transaction->package->name,
			"requested_at" => $date = $transaction->request_time,
			"status" => $transaction->status,
			"date"	=> $date
		];

		$dataToUpdate = [
			"api_request" => json_encode($apiRequest),
			"api_response" => json_encode($apiResponse),
			"client_response" => json_encode($clientResponse),
			"customer_name" => $customerName,
			"response_time" => date('D jS M Y, h:i:sa'),
			"date"	=> $date
		];

		$this->update($dataToUpdate, $transaction->id);
		return self::returnSuccess($clientResponse);
	}

	public function vend(Request $request)
	{
		$vendRequestTime = date('D jS M Y, h:i:sa');
		$isErrored =  self::validateRequest($request, DataHelper::$TvVendValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transaction = CommonController::getOneBackupValue(CommonController::TV_TRANSACTION['NEW'], "transaction_id", $request->transaction_id);
		if ($transaction == null) return self::returnNotFound("Please provide valid transaction id");
        if ($transaction->status == "fulfilled") return $this->clientVendResponse($transaction, $request);


		try {
			$apiVendRequest = [
				"total_amount" => $transaction->package->amount,
				"product_monthsPaidFor" => "1",
				"product_code" => $transaction->package->datacode,
				"smartcard_number" => $transaction->receiver,
				"agentReference" => $transaction->transaction_id,
				"agentId" => env('CAPRICON_AGENT_ID'),
				"service_type" => $transaction->package->provider->service_type
			];

			$apiVendResponse = self::post(["TV", "vend"], $apiVendRequest);
			if ($apiVendResponse->code == "BX0023") return self::returnSuccess("Transaction already completed");
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
			"phone" => $transaction->phone_number,
			"receiver" => $transaction->receiver,
			"amount" => $transaction->amount,
			"email" => $transaction->email,
			"customer_name" => $transaction->customer_name,
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

        return self::returnSuccess(self::ResponseThirdParty($clientVendResponse, "tv", "vend"));
    }


	public function history(Request $request)
	{
		$isErrored =  self::validateRequest($request, DataHelper::$TransactionHistoryValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transactionHistory = HistoryService::fetchHistory(new TvTransaction(), new OldTvTransaction(), $request->all(), $request->page);
		return self::returnSuccess($transactionHistory);
	}
}
