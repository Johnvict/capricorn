<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait OLAPService
{
	public static function migrateOldTransactions(Model $model, Model $old_model)
	{
		$tableName = $model->getTable();
		$old_tableName = $old_model->getTable();

		$aWeekAgo = Carbon::now()->subDays(6)->hour(0)->minute(0)->second(0)->toDateTimeString();     //TIME AT 12 midnight A WEEK AGO
		$aDayAgo = Carbon::now()->subDays(0)->hour(0)->minute(0)->second(0)->toDateTimeString();     //TIME AT 12 midnight TODAY
		$durationToConsider = env('OLAP_DURATION') == 'daily' ? $aDayAgo : $aWeekAgo;


		$tableColumns = implode(", ", Schema::getColumnListing($tableName));


		DB::beginTransaction();
		try {
			$sqlQuery = "INSERT INTO `" . $old_tableName . "` (" . $tableColumns . ") SELECT * FROM `" . $tableName . "` WHERE  `created_at` < ?";
			$sqlQuery2 = "DELETE " . $tableName . " FROM `" . $tableName . "` INNER JOIN `" . $old_tableName . "` ON " . $tableName . ".id = " . $old_tableName . ".id";

			DB::insert($sqlQuery, [$durationToConsider]);
			DB::delete($sqlQuery2);

			DB::commit();


			return ["status" => "00"];
		} catch (Exception $error) {
			Log::error("@Trait OLAPService \n@Method migrateOldTransactions\nERROR OCCURED ON OLAP");
			Log::error($error->getMessage());
			DB::rollBack();

			return ["status" => "02", "message" => $error->getMessage()];
		}
	}

}
