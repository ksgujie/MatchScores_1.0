<?php namespace App\Http\Controllers;

use App\Modules\User;


class ActionController extends Controller {

	public function run($module, $do)
	{
		$objModule = new $module();
		$objModule->$do();

	}
}