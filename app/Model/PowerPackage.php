<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PowerPackage extends Model
{
	protected $hidden = ['slug', 'foreign_id', 'created_at', 'updated_at'];
	protected $fillable = ["foreign_id", "name", "slug", "amount"];

	public function getIdAttribute()
	{
		return $this->attributes['foreign_id'];
	}

	public function provider()
	{
		return $this->belongsTo(PowerProvider::class);
	}
}
