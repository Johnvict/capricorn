<?php

namespace App\Http\Controllers;

use App\Model\AirtimeTransaction;
use App\Model\OldAirtimeTransaction;
use App\Services\APICaller;
use App\Services\DataHelper;
use App\Services\HistoryService;
use App\Services\ResponseFormat;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AirtimeController extends Controller
{
	use APICaller;
    use DataHelper;
    use ResponseFormat;
    use HistoryService;
    use ResponseService;

	private $commonCtrl, $admin;

	public function __construct(CommonController $commonCtrl, AdminController $admin)
	{
		$this->admin = $admin;
		$this->commonCtrl = $commonCtrl;
	}

	
	public function getProviders()
	{
		$providers = $this->commonCtrl->getBackupValues($this->commonCtrl::AIRTIME_PROVIDER);
		if($providers == null){
			return self::returnNotFound("service currently unavailable");
		}
		return self::returnSuccess($providers);
	}
	
	
	public function vend(Request $request)
	{
		$isErrored =  self::validateRequest($request, self::$AirtimeValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$transaction = $this->commonCtrl::getOneBackupValue($this->commonCtrl::AIRTIME_TRANSACTION['NEW'], "transaction_id", $request->transaction_id);
        if ($transaction != null && $transaction->status == "fulfilled") return $this->clientResponse($transaction, $request);

		$provider = $this->commonCtrl::getOneBackupValue($this->commonCtrl::AIRTIME_PROVIDER, "shortname", strtolower($request->provider));
		if ($provider == null) return self::returnNotFound("Please provide valid provider");
		
        return $this->createTransaction($request, $provider);
	}


	private function createTransaction($request, $provider)
	{
		$transaction = AirtimeTransaction::create([
			"trace_id" 			=> $request->trace_id,
			"transaction_id" 	=> $request->transaction_id,
			"phone_number" 		=> $request->phone,
			"provider"			=> $request->provider,
			"provider_id"		=> $provider->id,
			"receiver" 			=> $request->receiver,
			"amount" 			=> $request->amount,
			"email" 			=> $request->email,
			"status" 			=> 'incomplete',
			"request_time" 		=> date('D jS M Y, h:i:sA'),
			"client_request" 	=> json_encode($request->all()),
			"payment_reference"	=> $request->payment_reference,
			"date" 				=> date('D jS M Y, h:i:sa')
		]);

        return $this->purchaseAirtime($transaction, $provider);
	}
	

	private function purchaseAirtime($transaction, $provider)
    {
		$vendRequestTime = date('D jS M Y, h:i:sa');
        
        $apiRequest = [
            "agentReference" => $transaction->transaction_id,
            "agentId" => env('CAPRICON_AGENT_ID'),
            "plan" => "prepaid",
            "service_type" => $provider->service_type,
            "amount" => $transaction->amount,
            "phone" => $transaction->phone_number,
        ];
		try {
			$apiResponse = self::post(["AIRTIME", "vend"], $apiRequest);
			Log::info("\n\nRESPONSE FROM 3RD PARTY on vend");
			Log::info("METHOD NAME: `vend()`");
			Log::info(json_encode($apiResponse));
			
			if (isset($apiResponse->code)) {
                if ($apiResponse->code == "BX0023") return self::returnSuccess("Transaction already completed");
                if ($apiResponse->code != 200) return self::returnProviderFailed();

                $status = $apiResponse->code == 200 ? "fulfilled" : "pending";
				if ($status == 'fulfilled') $this->admin->resetBalance($transaction->amount);
				
				$data = [
					"api_request" 		=> json_encode($apiRequest),
					"api_response" 		=> json_encode($apiResponse),
					"request_time" 		=> $vendRequestTime,
					"status" 			=> $status,
				];

				$transaction->update($data);

				return $this->clientResponse($transaction);
			}
			Log::error("\n\nERROR ON VENDING AIRTIME");
			Log::error("METHOD NAME: `vend()`");
			return self::returnFailed("sorry, service currently unavailable");
		} catch (Exception $e) {
			Log::error($e);
			return self::returnFailed($e->getMessage());
		}
    }

	private function clientResponse($transaction)
    {
		$clientResponse = [
			"phone"				=> $transaction->phone_number,
			"receiver"			=> $transaction->receiver,
			"amount"			=> $transaction->amount,
			"email"				=> $transaction->email,
			"transaction_id"	=> $transaction->transaction_id,
			"provider"			=> $transaction->provider,
			"requested_at"		=> $transaction->request_time,
			"status"			=> $transaction->status,
			"payment_reference"	=> $transaction->payment_reference,
			"date"				=> $transaction->date
		];

        $transaction->update([
			"client_response" => json_encode($clientResponse),
			"response_time"  => date('D jS M Y, h:i:sa')
		]);
        
        return self::returnSuccess(self::ResponseThirdParty($clientResponse, 'airtime', 'vend'));
    }

	public function history(Request $request)
	{
		$isErrored =  self::validateRequest($request, self::$TransactionHistoryValidationRule);
		if ($isErrored) return self::returnFailed($isErrored);

		$historyData = self::fetchHistory(new AirtimeTransaction(), new OldAirtimeTransaction(), $request->all(), $request->page);

		return self::returnSuccess($historyData);
	}
}
