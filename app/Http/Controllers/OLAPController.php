<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Model\AirtimeTransaction;
use App\Model\DataTransaction;
use App\Model\TvTransaction;
use App\Http\Controllers\CommonController;
use App\Model\EpinTransaction;
use App\Model\OldAirtimeTransaction;
use App\Model\OldDataTransaction;
use App\Model\OldEpinTransaction;
use App\Model\OldPowerTransaction;
use App\Model\OldTvTransaction;
use App\Model\PowerTransaction;
use App\Services\DataHelper;
use App\Services\OLAPService;
use App\Services\ResponseFormat;

class OLAPController extends Controller {

	use DataHelper, ResponseFormat, OLAPService;

	public static $idArray = array();

	public function __construct()
	{
		//
	}

	public function backupAirtime() {
		$response = OLAPService::migrateOldTransactions(new AirtimeTransaction(), new OldAirtimeTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}
	public function backupData() {
		$response = OLAPService::migrateOldTransactions(new DataTransaction(), new OldDataTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}
	public function backupPower() {
		$response = OLAPService::migrateOldTransactions(new PowerTransaction(), new OldPowerTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}
	public function backupTv() {
		$response = OLAPService::migrateOldTransactions(new TvTransaction(), new OldTvTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}
	public function backupEpin() {
		$response = OLAPService::migrateOldTransactions(new EpinTransaction(),  new OldEpinTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}

}