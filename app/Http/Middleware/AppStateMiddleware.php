<?php

namespace App\Http\Middleware;

use App\Services\ResponseFormat;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppStateMiddleware
{
	use ResponseFormat;

    public const APP_STATE_ENABLED = 'ENABLED', APP_STATE_DISABLED = 'DISABLED';
    public const APP_STATE = 'APP_STATE';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        $isAppStateAtDisabled = $this->getAppState();

		if ($isAppStateAtDisabled && $request->requestUri != '/api/v2/manager/service/enable') return ResponseFormat::returnServiceDown();

        // Post-Middleware Action
		return $next($request);
    }


    private function setAppState($appState)
	{
        Log::info(Cache::put('status', $appState));
		return Cache::put('status', $appState);;
    }
    

	private function getAppState()
	{
        $check = Cache::has('status');

        if (!$check)
        {
            $check = $this->setAppState(self::APP_STATE_ENABLED);
            return false;
        }

        $appState = Cache::get("status");

		return $appState === self::APP_STATE_DISABLED ? true : false;
	}
}
