<?php

namespace App\Http\Controllers;

use App\Services\APICaller;
use App\Services\DataHelper;
use App\Services\HistoryService;
use App\Services\ResponseFormat;
use App\Services\ResponseService;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
	use APICaller;
    use ResponseFormat;
    use DataHelper;
    use HistoryService;
    use ResponseService;
    
}
