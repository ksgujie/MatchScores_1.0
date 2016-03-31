<?php namespace App\Http\Controllers;

set_time_limit(600);
require_once app_path() . '/Libs/PHPExcel/PHPExcel.php';

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

	public $lerrers = array ( 0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F', 6 => 'G', 7 => 'H', 8 => 'I', 9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X', 24 => 'Y', 25 => 'Z',);
	
	public function __construct()
	{
//		\DB::connection()->enableQueryLog();
	}

	public function runnedTime()
	{
		return microtime(true) - LARAVEL_START;
	}


}
