<?php

namespace App\Model;
use Illuminate\Database\Eloquent\Model;
// use phpDocumentor\Reflection\Types\This;

class TvProvider extends Model {
	protected $hidden = ["shortname", "service_type", "biller_id", "product_id", 'created_at', 'updated_at'];
	protected $fillable = [ "name", "shortname", "service_type", "biller_id", "product_id"];

	public function getNameAttribute() {
		return $this->attributes['shortname'];
	}
	public function packages()
	{
		return $this->hasMany(TvPackage::class, 'provider_id', 'id');
	}
}