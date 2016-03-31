<?php namespace App\Modules;

class SysConfig {

	//项目名称
	public static $item;
//	protected static $item;

	public static function get($key)
	{
		return config('my.'.$key);
	}

	public static function template($key)
	{
		$key = "模板.$key";
		return self::get($key);
	}

	/**
	 * 设置“项目”名称，为调用 self::item() 作准备
	 * @param $item
	 */
	public static function setItem($item)
	{
		self::$item = $item;
	}

	/**
	 * @param $item
	 * @return mixed 返回　项目.组别
	 */
	public static function itemGroups($item)
	{
		return self::item('组别', $item);
	}
	/**
	 * 调用某个项目的配置文件，在使用前必须先用 self::setItem()设定项目名称
	 * @param $key
	 */
	public static function item($key, $item=null)
	{
		if (is_null($item)) {
			$key = '项目.'.self::$item.'.'.$key;
		} else {
			$key = '项目.'.$item.'.'.$key;
		}
		return self::get($key);
	}

	/**
	 * 返回某个项目的EXCEL表格名称
	 * @param null $item
	 * @return mixed　完整的表名
	 */
	public static function sheetName($item=null)
	{
		if (!is_null($item)) {
			$name = self::get("$item.表名");
		} else {
			$name = self::item('表名');
		}
		return str_replace(',','',$name);
	}

	/**
	 * 返回某个项目EXCEL表名的前面部分，如：在配置文件中完整的表名是“A1,悬浮”，返回“A1”，主要用于设置编号前缀
	 * @param null $item
	 * @return mixed 字母表名 An
	 */
	public static function firstSheetName($item = null)
	{
		if (!is_null($item)) {
			$name = self::get("$item.表名");
		} else {
			$name = self::item('表名');
		}
		list($first, $nickname) = explode(',', $name, 2);
		return $first;
	}
	/**
	 * 返回所有项目
	 * @return array
	 */
	public static function items () {
		$r = array_keys(config('my.项目'));
		return $r;
	}

	/**
	 * 返回EXCEL生成路径，返回前会自动判断是否存在，如果不存在则自动创建
	 */
	public static function saveExcelDir()
	{
		$dir=rtrim(self::get('生成路径'), '/');
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		return $dir;
	}

}//class