<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EpinPackage extends Model
{
	protected $hidden = ["provider_id", "created_at", "updated_at"];
	protected $fillable = ["amount", "available", "description", "provider_id",];

	public function provider()
	{
		return $this->belongsTo(EpinProvider::class, 'provider_id', 'id');
	}

	public function transactions() {
		return $this->hasMany(EpinTransaction::class, 'package_id', 'id');
	}
}
