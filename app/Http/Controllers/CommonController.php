<?php

namespace App\Http\Controllers;

use App\Model\AirtimeProvider;
use App\Model\AirtimePackage;
use App\Model\AirtimeTransaction;
use App\Services\APICaller;
use App\Services\ResponseFormat;

use App\Model\DataPackage;
use App\Model\DataProvider;
use App\Model\DataTransaction;
use App\Model\EpinPackage;
use App\Model\EpinProvider;
use App\Model\EpinTransaction;
use App\Model\OldAirtimeTransaction;
use App\Model\OldDataTransaction;
use App\Model\OldEpinTransaction;
use App\Model\OldPowerTransaction;
use App\Model\OldTvTransaction;
use App\Model\PowerPackage;
use App\Model\TvProvider;
use App\Model\PowerProvider;
use App\Model\PowerTransaction;
use App\Model\TvPackage;
use App\Model\TvTransaction;

use Carbon\Carbon;
use Illuminate\Http\Request;

class CommonController extends Controller
{
	// use APICaller;
	// use ResponseFormat;
	private $doneSuccessfully, $type, $provider_id;
	public static $Type, $queryArray = array(), $durationToConsider, $dataToStore = array(), $idArray = array(), $history, $idField;


	public const AIRTIME_PROVIDER = 'airtimeProvider', DATA_PROVIDER = 'dataProvider', EPIN_PROVIDER = 'epinProvider', TV_PROVIDER = 'tvProvider', POWER_PROVIDER = 'powerProvider', COLLECTIONS_PROVIDER = 'collectionsProvider';
	public const AIRTIME_PACKAGE = 'airtimePackage', DATA_PACKAGE = 'dataPackage', EPIN_PACKAGE = 'epinPackage', TV_PACKAGE = 'tvPackage', POWER_PACKAGE = 'powerPackage', COLLECTIONS_PACKAGE = 'collectionsPackage';
	public const OLD_AIRTIME = 'oldAirtimeTransaction', OLD_DATA = 'oldDataTransactions', OLD_POWER = 'oldPowerTransactions', OLD_TV = 'oldTvTransactions';

	public const AIRTIME_TRANSACTION = ['NEW' => 'airtime_transactions', 'OLD' => 'old_airtime_transactions'],
		DATA_TRANSACTION = ['NEW' => 'data_transactions', 'OLD' => 'old_data_transactions'],
		EPIN_TRANSACTION = ['NEW' => 'epin_transactions', 'OLD' => 'old_epin_transactions'],
		POWER_TRANSACTION = ['NEW' => 'power_transactions', 'OLD' => 'old_power_transactions'],
		TV_TRANSACTION = ['NEW' => 'tv_transactions', 'OLD' => 'old_tv_transactions'];

	public static function determineModel($type)
	{
		switch ($type) {
			case self::AIRTIME_PROVIDER:
				$model = new AirtimeProvider();
				break;
			case self::DATA_PROVIDER:
				$model = new DataProvider();
				break;
			case self::EPIN_PROVIDER:
				$model = new EpinProvider();
				break;
			case self::TV_PROVIDER:
				$model = new TvProvider();
				break;
			case self::POWER_PROVIDER:
				$model = new PowerProvider();
				break;
			case self::AIRTIME_PACKAGE:
				$model = new AirtimePackage();
				break;
			case self::DATA_PACKAGE:
				$model = new DataPackage();
				break;
			case self::EPIN_PACKAGE:
				$model = new EpinPackage();
				break;
			case self::POWER_PACKAGE:
				$model = new PowerPackage();
				break;
			case self::TV_PACKAGE:
				$model = new TvPackage();
				break;
			case self::AIRTIME_TRANSACTION['NEW']:
				$model = new AirtimeTransaction();
				break;
			case self::DATA_TRANSACTION['NEW']:
				$model = new DataTransaction();
				break;
			case self::EPIN_TRANSACTION['NEW']:
				$model = new EpinTransaction();
				break;
			case self::TV_TRANSACTION['NEW']:
				$model = new TvTransaction();
				break;
			case self::POWER_TRANSACTION['NEW']:
				$model = new PowerTransaction();
				break;
			case self::AIRTIME_TRANSACTION['OLD']:
				$model = new OldAirtimeTransaction();
				break;
			case self::DATA_TRANSACTION['OLD']:
				$model = new OldDataTransaction();
				break;
			case self::EPIN_TRANSACTION['OLD']:
				$model = new OldEpinTransaction();
				break;
			case self::TV_TRANSACTION['OLD']:
				$model = new OldTvTransaction();
				break;
			case self::POWER_TRANSACTION['OLD']:
				$model = new OldPowerTransaction();
				break;
		}

		return $model;
	}


	public function createBackupProviders($dataSet, $type)
	{
		$this->type = $type;
		$dataSet->each(function ($data) use ($type) {

			if ($type == CommonController::POWER_PROVIDER) {
				$service = $this->getProviderName(strtolower($data->name));
				$dataToStore = [
					"name" => $service["name"],
					"shortname" => $service["fullname"],
					"service_type" => $data->service_type,
					"biller_id" => $data->biller_id,
					"product_id" => $data->product_id,
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now(),
				];
			} else {
				$dataToStore = [
					"name" => $data->name,
					"shortname" => $data->shortname,
					"service_type" => $data->service_type,
					"biller_id" => $data->biller_id,
					"product_id" => $data->product_id,
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now(),
				];
			}
			array_push(CommonController::$dataToStore, $dataToStore);
		});

		// dd(CommonController::$dataToStore);
		$modelInstance = CommonController::determineModel($type);
		$modelInstance::truncate();
		$doneSuccessfully = $modelInstance::insert(CommonController::$dataToStore);
		return $doneSuccessfully;
	}

	public function getProviderName($name)
	{
		if (
			str_contains($name, "abuja") ||
			str_contains($name, 'nassarawa') ||
			str_contains($name, 'Kogi') ||
			str_contains($name, 'Niger') ||
			str_contains($name, 'FCT')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "AEDC Prepaid", "fullname" => "aedc_prepaid"] :
				["name" => "AEDC Postpaid", "fullname" => "aedc_postpaid"];
		}

		if (
			str_contains($name, 'kaduna') ||
			str_contains($name, 'sokoto') ||
			str_contains($name, 'kebbi') ||
			str_contains($name, 'zamfara')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "KAEDCO Prepaid", "fullname" => "kaedco_prepaid"] :
				["name" => "KAEDCO Postpaid", "fullname" => "kaedco_postpaid"];
		}

		if (
			str_contains($name, 'plateau') ||
			str_contains($name, 'bauchi') ||
			str_contains($name, 'benue') ||
			str_contains($name, 'gombe') ||
			str_contains($name, 'jos')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "JED Prepaid", "fullname" => "jed_prepaid"] :
				["name" => "JED Postpaid", "fullname" => "jed_postpaid"];
		}

		if (
			str_contains($name, 'kano') ||
			str_contains($name, 'jigawa') ||
			str_contains($name, 'katsina')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "KEDCO Prepaid", "fullname" => "kedco_prepaid"] :
				["name" => "KEDCO Postpaid", "fullname" => "kedco_postpaid"];
		}
		if (
			str_contains($name, 'oyo') ||
			str_contains($name, 'ibadan') ||
			str_contains($name, 'osun') ||
			str_contains($name, 'ogun') ||
			str_contains($name, 'kwara')
		) {

			return str_contains($name, 'prepaid') ?
				["name" => "IBEDC Prepaid", "fullname" => "ibedc_prepaid"] :
				["name" => "IBEDC Postpaid", "fullname" => "ibedc_postpaid"];
		}
		if (
			str_contains($name, 'rivers') ||
			str_contains($name, 'port harcourt') ||
			str_contains($name, 'cross river') ||
			str_contains($name, 'akwa ibom') ||
			str_contains($name, 'bayelsa')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "PHED Prepaid", "fullname" => "phed_prepaid"] :
				["name" => "PHED Postpaid", "fullname" => "phed_postpaid"];
		}

		if (
			str_contains($name, 'delta') ||
			str_contains($name, 'edo') ||
			str_contains($name, 'ekiti') ||
			str_contains($name, 'ondo')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "BEDC Prepaid", "fullname" => "bedc_prepaid"] :
				["name" => "BEDC Postpaid", "fullname" => "bedc_postpaid"];
		}

		if (
			str_contains($name, 'abia') ||
			str_contains($name, 'enugu') ||
			str_contains($name, 'ebonyi') ||
			str_contains($name, 'anambra') ||
			str_contains($name, 'imo')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "EEDC Prepaid", "fullname" => "eedc_prepaid"] :
				["name" => "EEDC Postpaid", "fullname" => "eedc_postpaid"];
		}
		if (str_contains($name, 'eko')) {
			return str_contains($name, 'prepaid') ?
				["name" => "EKEDC Prepaid", "fullname" => "ekedc_prepaid"] :
				["name" => "EKEDC Postpaid", "fullname" => "ekedc_postpaid"];
		}

		if (str_contains($name, 'ikeja')) {
			return str_contains($name, 'prepaid') ?
				["name" => "IKEDC Prepaid", "fullname" => "ikedc_prepaid"] :
				["name" => "IKEDC Postpaid", "fullname" => "ikedc_postpaid"];
		}
	}

	public function createBackupPackages($dataSet, $type, $provider_id = null)
	{
		$this->type = $type;
		$this->provider_id = $provider_id;

		CommonController::$dataToStore = [];

		$dataSet->each(function ($data) {
			if ($this->type == CommonController::EPIN_PACKAGE) {
				$data = [
					"amount" => $data->amount,
					"available" => $data->available,
					"description" => $data->description,
					"provider_id" => $this->provider_id ?? $data->provider_id,
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now(),
				];
			} else {
				$data = [
					"name" => $data->name,
					"allowance" => $data->allowance ?? null,
					"amount" => $data->price,
					"validity" => $data->validity,
					"datacode" => $data->datacode,
					"provider_id" => $this->provider_id ?? $data->provider_id,
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now(),
				];
			}
			array_push(CommonController::$dataToStore, $data);
		});

		$modelInstance = CommonController::determineModel($type);
		$modelInstance::truncate();
		$doneSuccessfully = $modelInstance::insert(CommonController::$dataToStore);
		return $doneSuccessfully;
	}

	public function checkValueExistence($fieldName = 'id', $fieldValue, $type = null)
	{
		$modelInstance = CommonController::determineModel($type ?? $this->type);
		$data = $modelInstance ? $modelInstance::where($fieldName, $fieldValue)->first() : null;
		return $data;
	}

	public static function getBackupValues($type, $providerId = null)
	{
		$modelInstance = CommonController::determineModel(CommonController::$type ?? $type);

		switch ($type) {
			case self::AIRTIME_PROVIDER:
			case self::DATA_PROVIDER:
			case self::EPIN_PROVIDER:
			case self::TV_PROVIDER:
			case self::POWER_PROVIDER:
				$data = $modelInstance::all();
				break;
			case self::AIRTIME_PACKAGE:
			case self::DATA_PACKAGE:
			case self::POWER_PACKAGE:
			case self::TV_PACKAGE:
			case self::EPIN_PACKAGE:
				$data = $modelInstance::where('provider_id', $providerId)->get();
				break;
		}

		return $data;
	}

	public static function getOneBackupValue($type, $fieldName, $fieldValue)
	{
		$modelInstance = CommonController::determineModel($type);
		return $modelInstance::where($fieldName, $fieldValue)->first();
	}

	// public static function getFields(Request $request, $idField = null)
	// {
	// 	CommonController::$idField = $idField;
	// 	CommonController::$queryArray = [];

	// 	return collect([
	// 		"last_id" => $request->last_id,
	// 		"start_date" => $request->start_date,
	// 		"end_date" => $request->end_date,
	// 		"receiver" => $request->receiver,
	// 		"provider_id" => $request->provider_id,
	// 	])->filter(function ($field) {
	// 		return $field == null ? false : true;
	// 	})->map(function ($field, $key) {
	// 		switch ($key) {
	// 			case "last_id":
	// 				$query = [CommonController::$idField == null ? "id" : 'old_id', ">", CommonController::$idField == null ? $field : CommonController::$idField];
	// 				break;
	// 			case "start_date":
	// 				$query = ["created_at", ">=", Carbon::parse($field)];
	// 				break;
	// 			case "end_date":
	// 				$query = ["created_at", "<=", Carbon::parse($field)];
	// 				break;
	// 			case "receiver":
	// 				$query = ["receiver", "=", $field];
	// 				break;
	// 			case "provider_id":
	// 				$query = ["provider_id", "=", $field];
	// 				break;
	// 			case "bank":
	// 				$query = ["bank", "=", $field];
	// 				break;
	// 			case "account":
	// 				$query = ["account", "=", $field];
	// 				break;
	// 		}

	// 		return $query;
	// 	})
	// 		->each(function ($field) {
	// 			array_push(CommonController::$queryArray, [$field[0], $field[1], $field[2]]);
	// 		});
	// }
}
