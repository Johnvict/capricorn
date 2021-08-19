<?php

namespace App\Http\Controllers;

use App\Services\APICaller;
use App\Services\DataHelper;
use App\Services\ResponseFormat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class AdminController extends Controller
{
	use ResponseFormat, DataHelper, APICaller;
	public const APP_STATE = 'APP_STATE', APP_STATE_ENABLED = 'ENABLED', APP_STATE_DISABLED = 'DISABLED';


	public function __construct()
	{
		$this->providerId = null;
		// return response()->json("App is unavailable");
	}

	/**
	* To enable this microservice to be active/accessible
	*
	* @method GET
	* @return JSON response with status of the app and success message
	*/
	public function enable() 
	{
        $this->setAppState(self::APP_STATE_ENABLED);
        Log::info($this->setAppState(self::APP_STATE_ENABLED));
        
        return ResponseFormat::returnSuccess(["app_status" => "enabled"]);
	}

	/**
	* Disable this microservice
	* 
	* @method GET
	* @return JSON response with app status and success message
	*/
	public function disable() 
	{
        $this->setAppState(self::APP_STATE_DISABLED);
        Log::info($this->setAppState(self::APP_STATE_DISABLED));
        
        return ResponseFormat::returnSuccess(["app_status" => "disabled"]);
	}


	/**
	* To check the health of the app
	*
	* @method GET
	* @return JSON - empty with 200 header response
	*/
	public function health() 
	{
		return response()->json();
	}
	

	/**
	* To set the state of the Microservice by saving a file on the local directory with the status value
	*
	* @param String $appStatus - "ENABLED" || "DISABLED" 
	*/
	private function setAppState($appStatus)
	{
        return Cache::put('status', $appStatus);
	}



	/**
	 * ? To get account balance from the 3rd party microservice
	 * @method GET
	 * @return JSON response  
	 */
	public function balance()
	{
		$balance = self::get(["GENERAL", "balance"]);

		// ? If we could not get response we return the local backup value
		if ($balance == null) return $this->balanceFromLocal();
		if ($balance->code != 200 || isset($balance->data->balance) == false)  return $this->balanceFromLocal();
		
		$newBalance = $balance->data->balance;

		// * Located at DataHelper.php trait
		self::setBalance($newBalance);

		return self::returnSuccess(['balance' => $newBalance]);
	}
	
	/**
	 * ? We load the transaction from the backup local file
	 * @return JSON response
	 */
	private function balanceFromLocal() 
	{
		$balanceFromLocal = self::getBalance();
		return self::returnSuccess($balanceFromLocal);
	}
}
