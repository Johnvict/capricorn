<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AirtimePackage extends Model
{
	protected $hidden = ['amount', 'slug', 'foreign_id', 'created_at', 'updated_at'];
	protected $fillable = ["foreign_id", "name", "slug", "amount"];
	// protected $with = ["transactions"];

	public function provider()
	{
		return $this->belongsTo(AirtimeProvider::class, 'provider_id', 'id');
	}

	public function transactions() {
		return $this->hasMany(AirtimeTransaction::class, 'package_id', 'id');
	}
}
