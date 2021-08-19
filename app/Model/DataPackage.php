<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DataPackage extends Model
{
	protected $hidden = ["provider_id", "allowance", "validity", "datacode", "created_at", "updated_at"];
	protected $fillable = ["name", "allowance", "amount", "validity", "datacode", "provier_id",];

	public function provider()
	{
		return $this->belongsTo(DataProvider::class, 'provider_id', 'id');
	}

	public function transactions()
	{
		return $this->hasMany(DataTransaction::class, 'package_id', 'id');
	}
}
