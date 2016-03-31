<?php namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\SysConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use App\Modules\Excel;
use App\Modules\Score;


class ScoreController extends Controller {

	//excel对象
	protected $excel;
	//sheet对象
	protected $sheet;

	public function getMain() {

	}

	public function action($do)
	{
		$this->$do();
		return Redirect::to('/')->with('message', date("Y-m-d H:i:s")." 完成：$do \n耗时：". $this->runnedTime());
	}

	public function 计算成绩()
	{

	}

}