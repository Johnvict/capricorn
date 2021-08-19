<?php

namespace App\Http\Controllers;

use App\Model\AirtimePackage;
use Carbon\Carbon;

class ProviderPackageController extends Controller
{
	public const APP_STATE = 'APP_STATE', APP_STATE_ENABLED = 'ENABLED', APP_STATE_DISABLED = 'DISABLED';
	public const PROVIDERS_STACK = ["AIRTIME", "DATA", "TV", "POWER", "EPIN"];
	public $modelType, $provider, $packagesToStore, $serviceName, $length,  $commonCtrl;

	public function __construct(CommonController $commonCtrl)
	{
		$this->commonCtrl = $commonCtrl;
		$this->providerId = null;
		return response()->json("App is unavailable");
	}

	/**
	 * ? To load providers for the microservice from 3rdparty service provider
	 * @method GET
	 * @return JSON response with operation status success/failed message
	 */
	public function loadProviders($serviceName, $calledFromHere = false)
	{
		$theyWereSaved = $this->getProvider(strtoupper($serviceName));
		if ($calledFromHere == true) return;

		return $theyWereSaved == true ? self::returnSuccess() : self::returnFailed();
	}

	public function getProvider($serviceToFetch)
	{
		$urlStack = [$serviceToFetch, "providers"];
		switch ($serviceToFetch) {
			case "AIRTIME":
				$modelType = $this->commonCtrl::AIRTIME_PROVIDER;
				break;
			case "DATA":
				$modelType = $this->commonCtrl::DATA_PROVIDER;
				break;
			case "EPIN":
				$modelType = $this->commonCtrl::EPIN_PROVIDER;
				break;
			case "POWER":
				$modelType = $this->commonCtrl::POWER_PROVIDER;
				break;
			case "TV":
				$modelType = $this->commonCtrl::TV_PROVIDER;
				break;
		}
		$data = $this->getPlain($urlStack);
		if ($data != null) {
			if (isset($data->data->providers)) {
				$data = collect($data->data->providers);
				// dd($data);
				$theyWereSavedSuccessfully = $this->commonCtrl->createBackupProviders($data, $modelType);
				
				if ($serviceToFetch == "AIRTIME") $this->createAirtimePackages();
				return $theyWereSavedSuccessfully;
			}
		}

		// ? WE COULD NOT GET THE PROVIDERS
		return false;
	}


	/**
	 * ? To load packages for all providers of the microservice from 3rd Party Microservice
	 * @method GET
	 * @return JSON response with operation status success/failed message
	 */
	public function loadPackages($serviceName, $providerName)
	{
		$providerName = strtolower($providerName);

		$this->serviceName = $serviceName;
		switch ($serviceName) {
			case "data":
				$this->modelType = $this->commonCtrl::DATA_PACKAGE;
				$this->provider = $this->commonCtrl->checkValueExistence('shortname', $providerName, $this->commonCtrl::DATA_PROVIDER);
				break;
			case "epin":
				$this->modelType = $this->commonCtrl::EPIN_PACKAGE;
				$this->provider = $this->commonCtrl->checkValueExistence('shortname', $providerName, $this->commonCtrl::EPIN_PROVIDER);
				break;
			case "tv":
				$this->modelType = $this->commonCtrl::TV_PACKAGE;
				$this->provider = $this->commonCtrl->checkValueExistence('shortname', $providerName, $this->commonCtrl::TV_PROVIDER);
				break;
			default:
				return self::returnFailed("Please send valid service name");
				break;
		}


		if ($this->provider == null) return self::returnFailed("Please send valid provider name");

		$urlStack = [strtoupper($serviceName), "packages"];
		$data = $this->post($urlStack, ["service_type" => $this->provider->shortname]);

		if ($data != null) {
			if (isset($data->code)) {
				$code = $data->code;
				if ($code == 200) {
					$modelInstance = $this->commonCtrl->determineModel($this->modelType);
					$packages = $modelInstance::all();
					$this->packagesToStore = [];

					// ? We need to filter out the EXISTING packages of the newly fetched provider and format each of the remaining packages to match the format we want to restore
					$packages->each(function ($package) {
						if ($package->provider_id != $this->provider->id) {
							if ($this->modelType === $this->commonCtrl::EPIN_PACKAGE) {
								$dataArray = [
									"amount" => $package->amount,
									"available" => $package->available,
									"description" => $package->description,
									"provider_id" => $package->provider_id,
									'created_at' => $package->created_at,
									'updated_at' => $package->updated_at
								];
							} else {
								$dataArray = [
									"name" => $package->name,
									"allowance" => $package->allowance,
									"amount" => $package->amount,
									"validity" => $package->validity,
									"datacode" => $package->datacode,
									"provider_id" => $package->provider_id,
									'created_at' => $package->created_at,
									'updated_at' => $package->updated_at
								];
							}

							if (count($dataArray) > 0) {
								array_push($this->packagesToStore,  $dataArray);
							}
						}
					});


					// ? Now, let's format the newly fetched PACKAGES to look like the format we define for respective models
					$data = collect($data->data)->each(function ($package) {
						if ($this->modelType === $this->commonCtrl::EPIN_PACKAGE) {
							$dataArray = [
								"amount" => $package->amount,
								"available" => $package->available,
								"description" => $package->description,
								"provider_id" => $this->provider->id
							];
						} else if ($this->modelType == $this->commonCtrl::TV_PACKAGE) {
							$detail = $package->availablePricingOptions[0];
							$dataArray = [
								"name" => $package->name,
								"allowance" => null,
								"amount" => $detail->price,
								"validity" => null,
								"datacode" => $package->code,
								"provider_id" => $this->provider->id
							];
						} else {
							$dataArray = [
								"name" => $package->name,
								"allowance" => $package->allowance,
								"amount" => $package->price,
								"validity" => $package->validity,
								"datacode" => $package->datacode,
								"provider_id" => $this->provider->id
							];
						}

						if (count($dataArray) > 0) {
							$dataArray['created_at'] = Carbon::now();
							$dataArray['updated_at'] = Carbon::now();
							array_push($this->packagesToStore, $dataArray);
						}
					});


					if (count($this->packagesToStore) > 0) {
						$modelInstance::truncate();
						$theyWereSavedSuccessfully =  $modelInstance::insert($this->packagesToStore);
						return $theyWereSavedSuccessfully == true ? self::returnSuccess() : self::returnFailed();
					}
					return self::returnFailed();
				}
				if ($data->code == "BX0003") {
					return self::returnFailed("Please send valid provider name");
				}
			}
		}
		return self::returnFailed('Error occured while fetching packages');
	}


	/**
	 * ? We have to simulate storage of airtime packages in our system
	 */
	public function createAirtimePackages()
	{
		$providers = CommonController::getBackupValues(CommonController::AIRTIME_PROVIDER);
		$this->length = count($providers);

		// ? We need to simulate creation of packages
		$this->packagesToStore = [];
		$providers->each(function ($item) {
			$dataArray = [
				"name" => $item->name . " VTU",
				"allowance" => null,
				"amount" => null,
				"validity" => null,
				"datacode" => null,
				"provider_id" => $item->id,
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now()
			];
			array_push($this->packagesToStore, $dataArray);
		});

		AirtimePackage::truncate();
		return AirtimePackage::insert($this->packagesToStore);
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
			str_contains($name, 'Kaduna') ||
			str_contains($name, 'Sokoto') ||
			str_contains($name, 'Kebbi') ||
			str_contains($name, 'Zamfara')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "KAEDCO Prepaid", "fullname" => "kaedco_prepaid"] :
				["name" => "KAEDCO Postpaid", "fullname" => "kaedco_postpaid"];
		}

		if (
			str_contains($name, 'Plateau') ||
			str_contains($name, 'Bauchi') ||
			str_contains($name, 'Benue') ||
			str_contains($name, 'Gombe')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "JED Prepaid", "fullname" => "jed_prepaid"] :
				["name" => "JED Postpaid", "fullname" => "jed_postpaid"];
		}

		if (
			str_contains($name, 'Kano') ||
			str_contains($name, 'Jigawa') ||
			str_contains($name, 'Katsina')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "KEDCO Prepaid", "fullname" => "kedco_prepaid"] :
				["name" => "KEDCO Postpaid", "fullname" => "kedco_postpaid"];
		}
		if (
			str_contains($name, 'Oyo') ||
			str_contains($name, 'Ibadan') ||
			str_contains($name, 'Osun') ||
			str_contains($name, 'Ogun') ||
			str_contains($name, 'Kwara')
		) {

			return str_contains($name, 'prepaid') ?
				["name" => "IBEDC Prepaid", "fullname" => "ibedc_prepaid"] :
				["name" => "IBEDC Postpaid", "fullname" => "ibedc_postpaid"];
		}
		if (
			str_contains($name, 'Rivers') ||
			str_contains($name, 'Cross River') ||
			str_contains($name, 'Akwa Ibom') ||
			str_contains($name, 'Bayelsa')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "PHED Prepaid", "fullname" => "phed_prepaid"] :
				["name" => "PHED Postpaid", "fullname" => "phed_postpaid"];
		}

		if (
			str_contains($name, 'Delta') ||
			str_contains($name, 'Edo') ||
			str_contains($name, 'Ekiti') ||
			str_contains($name, 'Ondo')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "BEDC Prepaid", "fullname" => "bedc_prepaid"] :
				["name" => "BEDC Postpaid", "fullname" => "bedc_postpaid"];
		}

		if (
			str_contains($name, 'Abia') ||
			str_contains($name, 'Enugu') ||
			str_contains($name, 'Ebonyi') ||
			str_contains($name, 'Anambra') ||
			str_contains($name, 'Imo')
		) {
			return str_contains($name, 'prepaid') ?
				["name" => "EEDC Prepaid", "fullname" => "eedc_prepaid"] :
				["name" => "EEDC Postpaid", "fullname" => "eedc_postpaid"];
		}
		if (str_contains($name, 'Eko')) {
			return str_contains($name, 'prepaid') ?
				["name" => "EKEDC Prepaid", "fullname" => "ekedc_prepaid"] :
				["name" => "EKEDC Postpaid", "fullname" => "ekedc_postpaid"];
		}

		if (str_contains($name, 'Ikeja')) {
			return str_contains($name, 'prepaid') ?
				["name" => "IKEDC Prepaid", "fullname" => "ikedc_prepaid"] :
				["name" => "IKEDC Postpaid", "fullname" => "ikedc_postpaid"];
		}
	}
}
