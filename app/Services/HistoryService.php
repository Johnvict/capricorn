<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HistoryService
{
	public static $queryString = "id > 0", $itemData = [];
	/**
	 * Add new sort parameters here according to your needs 
	 * Remove those you do not need to avoid redundancy
	 *  */
	public static function getQueryStructure($field, $key)
	{
		switch ($key) {
			case "receiver":
				$query = "receiver = '$field'";
				break;
			case "provider_id":
				$query = "provider_id = '$field'";
				break;
			case "provider":
				$query = "provider = '$field'";
				break;
			case "payment_reference":
				$query = "payment_reference = '$field'";
				break;
			case "transaction_id":
				$query = "transaction_id = '$field'";
				break;
			case "bank":
				$query = "bank = '$field'";
				break;
			case "account":
				$query = "account = '$field'";
				break;
			case "trace_id":
				$query = "trace_id = '$field'";
				break;
			case "start_date":
				$query = "created_at >= '" . Carbon::parse($field) ."'";
				break;
			case "end_date":
				$query = "created_at <= '" . Carbon::parse($field). "'";
				break;
			case "customer_email":
				$query = "customer_email = '$field'";
				break;
			case "customer_name":
				$query = "customer_name = '$field'";
				break;
			case "bank_name":
				$query = "bank_name = '$field'";
				break;
			case "account_name":
				$query = "account_name = '$field'";
				break;
			case "payment_reference":
				$query = "payment_reference = '$field'";
				break;
			case "transaction_reference":
				$query = "transaction_reference = '$field'";
				break;
			case "reference":
				$query = "reference = '$field'";
				break;
			case "payment_status":
				$query = "payment_status = '$field'";
				break;
			case "amount":
				$query = "amount = '$field'";
				break;
		}

		return $query ?? null;
	}



	
	/**
	 * **USED TO GET HISTORY FROM 3RD PARTY SERVICES FOR ADMIN**
	 * @param Model $model An instance of the Regular table model
	 *	- E.g **new User()**
	 * @param Model $old_model An instance of the Regular table model
	 *	- E.g **new OldUser()**
	 * @param Array $possibleFields **Associative Array of all sort parameters:**
	 *	- E.g **$request->all()**
	 * @param Integer $page **Page Number as from the request body:**
	 *	- E.g **$request->page**
	 */
	public static function fetchHistory(Model $model, Model $old_model, Array $possibleFields, $page)
	{
		$historyData = HistoryService::getHistory($model, $old_model, $possibleFields, $page);

		$items = collect($historyData["items"])
			->map(function ($item) {
				collect($item)->each(function ($value, $key) use ($item) {
					switch ($key) {
						case "client_request":
							$item->$key = json_decode($value);
							break;
						case "client_response":
							$item->$key = json_decode($value);
							break;
						case "api_request":
							$item->$key = json_decode($value);
							break;
						case "api_response":
							$item->$key = json_decode($value);
							break;
						case "client_vend_request":
							$item->$key = json_decode($value);
							break;
						case "client_vend_response":
							$item->$key = json_decode($value);
							break;
						case "api_vend_request":
							$item->$key = json_decode($value);
							break;
						case "api_vend_response":
							$item->$key = json_decode($value);
							break;
						case "webhook_request":
							$item->$key = json_decode($value);
							break;
						case "consumer_request":
							$item->$key = json_decode($value);
							break;
						default:
							$item->$key = $value;
							break;
					}
				});

				return $item;
			});

		return [
			"total_items"	=> $historyData["total"],
			"load_more"		=> $historyData["load_more"],
			"items" 		=> $items,
		];
	}

	public static function getHistory(Model $model, Model $old_model, $possibleFields, $page, $limit = 10)
	{
		HistoryService::getFields($possibleFields);
		$tableName = $model->getTable();
		$old_tableName = $old_model->getTable();

		$skip = ($page - 1) * $limit;
		$skip = $skip < 0 ? 0 : $skip;

		$sqlQuery =  "SELECT * FROM (SELECT * FROM `" . $tableName . "` UNION SELECT * FROM `" . $old_tableName . "`) AS history
			WHERE " . HistoryService::$queryString . " ORDER BY `id` DESC LIMIT " . $limit . " OFFSET " . $skip;

		$history = DB::select(DB::raw($sqlQuery));


		$summary = self::resultSummary($tableName, $old_tableName, $skip + $limit);
		$data = [
			"total"		=> $summary["total"],
			"load_more"	=> $summary["load_more"],
			"items"		=> $history,
		];

		return $data;
	}

	public static function resultSummary($tableName, $old_tableName, $skip)
	{
		$sqlQuery =  "SELECT COUNT(*) as total
			FROM (SELECT * FROM `" . $tableName . "` UNION SELECT * FROM `" . $old_tableName . "`) AS history
			WHERE " . HistoryService::$queryString . " GROUP BY `id` ORDER BY `id` DESC";

		$result = DB::select(DB::raw($sqlQuery));
		$total = count($result);

		return [
			"total"		=> $total,
			"load_more"	=> ($total - ($skip)) <= 0 ? false : true,
		];
	}

	public static function getFields($possibleFields)
	{
		collect($possibleFields)->filter(function ($field) {
			return ($field || $field != null) ? true : false;
		})->map(function ($field, $key) {
			return HistoryService::getQueryStructure($field, $key);
		})->each(function ($field) {
			HistoryService::$queryString = $field ? HistoryService::$queryString." AND $field" : HistoryService::$queryString;
		});
	}
}
