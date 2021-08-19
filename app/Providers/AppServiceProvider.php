<?php

namespace App\Providers;

use App\Services\APICaller;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
	use APICaller;

    public function register()
    {
		//
	}
	
	public function boot() {
		$this::init();
	}

}
