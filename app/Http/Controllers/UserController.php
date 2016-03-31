<?php namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Score_2015mianyang_zhifeiji;
use App\Modules\Show;
use App\Modules\SysConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use App\Modules\Excel;
use App\Modules\Score;


class UserController extends Controller {

	//excel对象
	protected $excel;
	//sheet对象
	protected $sheet;

	public function getMain() {
		echo 1000000000-223;die;
		$a='002345671';
		$b=preg_replace_callback('/(\d+)(\d\d)(\d\d)(\d\d)(\d)$/',
				function ($m) {
					return (int)$m[1]."圈 $m[2]分$m[3]秒$m[4] " . Show::标记($m[5], ['X','Y']);
				},
				$a);
		echo $b;

		die;
		//优胜奖
		$users = User::where('奖项','')->get();
		foreach ($users as $user) {
			$c=User::where('姓名',$user->姓名)->where('单位',$user->单位)->where('奖项','!=','')->count();
			if ($c == 0) {
				$user->奖项='优胜奖';
				$user->save();
			}
		}
die;////////////////
		$lines = file('d:/wwwroot/j.txt');
		foreach ($lines as $line) {
//			$line=trim($line);
			list($bh,$jx) = explode("\t", $line);
			$bh=trim($bh);
			$jx=trim($jx);
			$user = User::where('编号', $bh)->first();
			$user->奖项=$jx;
			$user->save();
		}

	}
	public function test($level)
	{

	}//test

	public function action($do)
	{
		$this->$do();
		return Redirect::to('/')->with('message', date("Y-m-d H:i:s")."完成：$do \n耗时：". $this->runnedTime());
	}

	public function score($do)
	{
		$objScore = new Score();
		$objScore->$do();
		return Redirect::to('/')->with('message', date("Y-m-d H:i:s")." 完成：$do \n耗时：". $this->runnedTime());
	}

	public function download($filename)
	{
		$sourceFile = utf8ToGbk(base_path()."/下载/$filename.xlsx");
		$targetFile = storage_path().'/'.md5($filename).'.xlsx';
		if (is_file($targetFile)) {
			@unlink($targetFile);
		}
		copy($sourceFile, $targetFile);
		return \Response::download(
			$targetFile,
			$filename.'.xlsx',
			["Expires:-1","Cache-Control:no-cache","Pragma:no-cache"]
		);
	}

	public function 成绩导入()
	{
		//清空原有成绩
		\DB::update("update users set 原始成绩='', 成绩备注=''");

		$filename = SysConfig::get('生成路径').utf8ToGbk('/成绩录入.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$arrItems = SysConfig::items();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$objSheet = $objExcel->getSheetByName(SysConfig::sheetName());
			//所有数据
			$arrRows=$objSheet->toArray(null,true,false,false);
			//获取firstDataRowNum,如果A1单元格里有数字就取此值，否则就取值3
			$A1 = (int)trim($arrRows[0][0]);
			$firstDataRowNum = $A1>0 ? $A1 : 3;
			//第一行数据（定位成绩在哪几列）
			$firstRow = array_map('trim', $arrRows[0]);
			//记录成绩所在的列数 例：$arrScoreCol[1]=2，1是“成绩1”,2是所在列数（列数从0开始计数）
			$arrScoreCol=[];
			//记录成绩备注所在的列数 例: $arrMarkCol[1]=2，1是备注1，2是所在列数（列数从0开始计数）
			$arrMarkCol=[];
			for ($i = 1; $i < count($firstRow); $i++) {
				$cellValue = trim($firstRow[$i]);
				if (preg_match('/^\d+$/', $cellValue)) {//成绩
					$arrScoreCol[$cellValue] = $i;
				} elseif (preg_match('/^mark(\d+)$/i', $cellValue, $_array)) {//备注
					$arrMarkCol[$_array[1]] = $i;
				}
			}

			//确定编号所在列
			$row = $arrRows[$firstDataRowNum-2];
			for ($i = 0; $i < count($row); $i++) {
				if (trim($row[$i]) == '编号') {
					$serialNumberColNum=$i;
				}
			}

			//按行循环
			for ($i = $firstDataRowNum-1; $i < count($arrRows); $i++) {
				$row = array_map('trim', $arrRows[$i]);
				$User = User::where('编号', $row[$serialNumberColNum])->first();
				if (!$User) {
					pp("编号为：".$row[$serialNumberColNum]." 的数据未找到！");
					pp($arrRows);
					exit();
				}
//				pp($arrScoreCol);/////////////////////

				///////////// 开始：匹配成绩 ////////////
				$arrScores = [];//将所有原始成绩保存在此
				$allScoresIsEmpty = true; //成绩是否全部为空
				foreach ($arrScoreCol as $scoreNum => $colNum) {
					$_score = trim($row[$colNum]);
					//检测成绩是否为空
					if (strlen($_score)) {
						$allScoresIsEmpty = false;
					}
					$arrScores[] = $_score;
				}

				//判断所有成绩是否为空，有一个不是空值就保存
				if (!$allScoresIsEmpty) {
					$User->原始成绩 = serialize($arrScores);
				}
				///////////// 结束：匹配成绩 ////////////

				////////////// 开始：匹配备注////////////
				$arrMarks = [];//保存备注
				$allMarksIsEmpty = true;//成绩备注是否全部为空
				foreach ($arrMarkCol as $markNum => $colNum) {
					$_mark = trim($row[$colNum]);
					//检测备注是否为空
					if (strlen($_mark)) {
						$allMarksIsEmpty = false;
					}
					$arrMarks[] = $_mark;
				}

				//判断所有备注是否为空，有一个不是空值就保存
				if (!$allMarksIsEmpty) {
					$User->成绩备注 = serialize($arrMarks);
				}
				////////////// 结束：匹配备注 ////////////

				//判断成绩、备注是否为空，有一个不为空就要保存
				if (!$allScoresIsEmpty || !$allMarksIsEmpty) {
					$User->save();
				}

			}

		}

//		$objSheet = $objExcel->getActiveSheet();
//		$arrData=$objSheet->toArray();
//		//导入EXCEL文件中的字段
//		$templateFields = array_map('trim', $arrData[0]);
//		//users数据表中的字段
//		$DbTableFields=[];
//		//检测出数据库USERS表中真实的字段
//		foreach ($templateFields as $_field) {
//			if (\Schema::hasColumn('users', $_field )) {
//				$DbTableFields[]=$_field;
//			}
//		}
//
////		$fields = $arrData[0];
//		$insertCount = 0;
//		for ($i = 1; $i < count($arrData); $i++) {
//			$row = $arrData[$i];
//			$arrInsert = [];
//			for ($j = 0; $j < count($row); $j++) {
//				$field = $templateFields[$j];
//				if ( in_array($field, $DbTableFields) && strlen($row[$j]) ) {
//					$arrInsert[$field] = trim($row[$j]);
//				}
//			}
////			pd($arrInsert);
//			if ($arrInsert) {
////				pp($arrInsert);
//				User::create($arrInsert);
//				$insertCount++;
//			}
//		}

		return \Redirect::to('/')->with('message', "成功！耗时：". $this->runnedTime());
	}

	public function 生成裁判用表()
	{
		$this->_生成裁判用表('默认');
		$this->_生成裁判用表('行数');
		$this->_生成裁判用表('组别');
		$this->_生成裁判用表('分组');
	}

	/**
	 * @param null $breakPage 根据XX字段分页，常用值：分组、组别
	 */
	public function _生成裁判用表($breakPage='默认')
	{
		$arrItems = SysConfig::items();
		$objExcel = new Excel();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$Users = User::where('项目', $item)
				->orderBy('编号')
				->get();
			$config = [
				'templateFile'=>SysConfig::template('裁判用表'),
				'sheetName'=>str_replace(',','',SysConfig::item('表名')),
//				'item'=>$item,
				'firstDataRowNum'=>SysConfig::item('首条数据行号'),
				'data'=>$Users,
			];
			$objExcel->setConfig($config);
			$objExcel->make();

			//页眉、页脚
			$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 '. config('my.比赛名称') . "\n&\"宋体,常规\"&14（{$item}）" );
			$objExcel->sheet->getHeaderFooter()->setOddFooter( '&L&P/&N页'
														.'&R裁判员签名_______________'
														.' 项目裁判长签名_______________'
			);

			//插入分页符
			if ($breakPage!='默认') {
				$objExcel->insertPageBreak($breakPage);
			}

		}//foreach items as item

		//$objExcel->save(SysConfig::saveExcelDir().utf8ToGbk("/裁判用表（{$breakPage}分页）.xlsx"));
		$objExcel->saveToPDF(SysConfig::saveExcelDir().utf8ToGbk("/裁判用表（{$breakPage}分页）.pdf"));
	}

	/**
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function 生成分组情况表()
	{
		$firstDataRowNum=2;
		$objExcel=new Excel();
		$Users = User::orderBy('报名顺序')
			->orderBy('单位')
			->orderBy('队名')
			->orderBy('编号')
			->get();
		$config = [
			'templateFile'=>SysConfig::template('分组情况'),
			'sheetName'=>'分组',
			'firstDataRowNum'=>$firstDataRowNum,
			'data'=>$Users,
		];
		$objExcel->setConfig($config);
		$objExcel->make();

		//页面设置
		$objPageSetup = $objExcel->sheet->getPageSetup();
		//$objPageSetup->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);//纸张横向
		$objPageSetup->setRowsToRepeatAtTopByStartAndEnd(1, $firstDataRowNum-1);//打印标题行
		$objPageSetup->setHorizontalCentered(true);//水平居中

		$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 '. config('my.比赛名称') . "\n各参赛队分组情况" );
		$objExcel->sheet->getHeaderFooter()->setOddFooter( '- &P/&N -&R打印时间：&D');
//			$objPageSetup->setFitToPage(true);
		$objPageSetup->setFitToWidth(1);
		$objPageSetup->setFitToHeight(32767);

		$objExcel->save(SysConfig::saveExcelDir().utf8ToGbk("/分组情况.xlsx"));
	}

	public function 生成检录用表()
	{
		$this->_生成检录用表('默认');
		$this->_生成检录用表('行数');
		$this->_生成检录用表('分组');
		$this->_生成检录用表('组别');
	}

	/**
	 * @param null $breakPage 根据XX字段分页，常用值：分组、组别
	 */
	public function _生成检录用表($breakPage='默认')
	{
		$arrItems = SysConfig::items();
		$objExcel = new Excel();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$Users = User::where('项目', $item)
				->orderBy('编号')
				->get();
			$config = [
				'templateFile'=>SysConfig::template('检录用表'),
				'sheetName'=>str_replace(',','',SysConfig::item('表名')),
				'firstDataRowNum'=>SysConfig::item('首条数据行号'),
				'data'=>$Users,
			];
			$objExcel->setConfig($config);
			$objExcel->make();

			//页眉、页脚
			$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 '. config('my.比赛名称') . " &\"宋体,常规\"&16 检录" );
			$objExcel->sheet->getHeaderFooter()->setOddFooter( '&L&P/&N页');

			//插入分页符
			if ($breakPage!='默认') {
				$objExcel->insertPageBreak($breakPage);
			}
		}//foreach items as item

		$objExcel->save(SysConfig::saveExcelDir().utf8ToGbk("/检录（{$breakPage}分页）.xlsx"));
	}

	/**
	 * 该函数已经不再使用，只留作代码参考使用
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function 生成分组情况表_无用只作备份()
	{

		$objExcel = \PHPExcel_IOFactory::load( mb_convert_encoding(config('my.模板.分组情况'), 'gbk', 'utf-8') );
		$objSheet = $objExcel->getActiveSheet();
		$objSheet->setTitle('分组情况');
		$Users = User::orderBy('报名顺序')
			->orderBy('单位')
			->orderBy('队名')
			->orderBy('编号')
			->get();
		//第一条数据的行号
		$firstDataRowNum = 2;
		//获取行高
		$rowHeight = $objSheet->getRowDimension($firstDataRowNum)->getRowHeight();
		//第一次按行循环，将模板中的第一条数据行往下复制
		for ($j = 1; $j < count($Users); $j++) {//这里不用<=因为已经有一条模板数据行了，复制的行数比实际数据数减一
			//设置行高
			$objSheet->getRowDimension($firstDataRowNum + $j)->setRowHeight($rowHeight);
			//总列数
			$colCount = \PHPExcel_Cell::columnIndexFromString($objSheet->getHighestColumn());
			//按列循环
			for ($k = 0; $k < $colCount; $k++) {
				//获取模板数据行各单元格对象
				$templateCell = $objSheet->getCellByColumnAndRow($k, $firstDataRowNum);
				//设置模板数据行各单元格对象属性：字体变小以适应宽
				$templateCell->getStyle()->getAlignment()->setShrinkToFit(true);
				//当前单元格的值写入到下一行、对应列的单元格中
				$objSheet->setCellValueByColumnAndRow($k, $firstDataRowNum+$j, $templateCell->getValue());
				//读取模板行单元格格式
				$templateCellStyle = $objSheet->getCellByColumnAndRow($k, $firstDataRowNum)->getStyle();
				//生成当前单元格名称
				$cellName = \PHPExcel_Cell::stringFromColumnIndex($k) . ($firstDataRowNum+$j);
				//在当前单元格写入模板数据行对应单元格格式
				$objSheet->duplicateStyle($templateCellStyle,"$cellName:$cellName");
			}
		}
		//第二次按行循环，将数据库中的值写入各单元格
		//上一行“组别”或“分组”单元格值，供插入分页符时使用
		$lastCellValue = '';
		$j=0;//行号
		foreach ($Users as $User) {
			//总列数
			$colCount = \PHPExcel_Cell::columnIndexFromString($objSheet->getHighestColumn());
			for ($k = 0; $k < $colCount; $k++) {
				//读取当前单元格
				$objCurrentCell = $objSheet->getCellByColumnAndRow($k, $firstDataRowNum + $j);
				//当前单元格的值
				$strCurrentCellValue = trim($objCurrentCell->getValue());
				//将当前用户（对象）的值转化为数组
				$arrUser = $User->toarray();
				//去除模板键前后中拨号，转化为字段名
				$templateKey =str_replace(['[',']'], '', $strCurrentCellValue);
				//检测是否为模板键格式并且数据库中存在该字段
				if (preg_match('/^\[.+?\]$/', $strCurrentCellValue) && isset($arrUser[$templateKey])) {
					//当前单元格的值写入相应值
					$objSheet->setCellValueByColumnAndRow($k, $firstDataRowNum+$j, $arrUser[$templateKey]);
				}
			}
			$j++;
		}

		//页面设置
		$objPageSetup = $objSheet->getPageSetup();
		//$objPageSetup->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);//纸张横向
		$objPageSetup->setRowsToRepeatAtTopByStartAndEnd(1, $firstDataRowNum-1);//打印标题行
		$objPageSetup->setHorizontalCentered(true);//水平居中

		$objSheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 '. config('my.比赛名称') . "\n各参赛队分组情况" );
		$objSheet->getHeaderFooter()->setOddFooter( '- &P/&N -&R打印时间：&D');
//			$objPageSetup->setFitToPage(true);
		$objPageSetup->setFitToWidth(1);
		$objPageSetup->setFitToHeight(100);

		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$excelFileName = iconv('utf-8','gbk',base_path().'/生成/参赛队分组情况' . date("Y年m月d日") . '.xlsx');
		$objWriter->save($excelFileName);
	}

	/**
	 * 正常情况下$firstDataRowNum的值为3，如果不是3,请在A1单元格输入具体数字（为了不让这人数字显示，其颜色可以设置为白色）  该功能还未完成
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function 生成成绩录入表()
	{
		$arrItems = SysConfig::items();
		$objExcel = new Excel();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$Users = User::where('项目', $item)
				->orderBy('编号')
				->get();
			$config = [
				'templateFile'=>SysConfig::template('成绩录入'),
				'sheetName'=>SysConfig::sheetName(),
				'firstDataRowNum'=>3,
				'data'=>$Users,
			];
			$objExcel->setConfig($config);
			$objExcel->make();
		}//foreach items as item

		$objExcel->save(SysConfig::saveExcelDir().utf8ToGbk("/成绩录入.xlsx"));
	}


	/**
	 * 编排编号
	 */
	public function 编号()
	{
		$arrItems = SysConfig::items();
		for ($i = 0; $i < count($arrItems); $i++) {
			$item = $arrItems[$i];
			SysConfig::setItem($item);
			$arrGroups = SysConfig::item('组别');
			for ($j = 0; $j < count($arrGroups); $j++) {
				$group = $arrGroups[$j];
				$Users = User::where('项目', $item)
					->where('组别', $group)
					->orderBy('报名顺序')
					->orderBy('单位')
					->orderBy('队名')
					->orderBy('id')
					->get();

				$n=1;
				foreach ($Users as $user) {
					$firstSheetName = SysConfig::firstSheetName();
					$编号 = $firstSheetName . $this->lerrers[$j] . sprintf("%03d", $n);

					$user->编号 = $编号;
					$a=$user->save();

					$n++;
				}//foreach
			}//for j
		}//for i
	}

	/**
	 * 设置分组
	 * 分组前必须先把编号编排好
	 */
	public function 分组()
	{
		$User = User::first();
		$User->编号 == '' && exit('[设置分组]前请先[设置编号]!');

		$arrItems = SysConfig::items();
		for ($i = 0; $i < count($arrItems); $i++) {
			$item = $arrItems[$i];
			SysConfig::setItem($item);
			$arrGroups = SysConfig::item('组别');
			for ($j = 0; $j < count($arrGroups); $j++) {
				$group = $arrGroups[$j];

				switch (SysConfig::item('分组.方式')) {
					case '固定数量':
						$this->分组_固定数量($item, $group);
						break;

					case '区域/固定数量':
						$this->分组_区域_固定数量($item, $group);
						break;
				}
			}//for j
		}//for i
	}

	private function 分组_固定数量($item, $group)
	{
		$Users = User::where('项目', $item)
			->where('组别', $group)
			->orderBy('编号')
			->get();
		//每组数量
		SysConfig::setItem($item);
		$everyTeamNum = SysConfig::item('分组.每组人数');
		//第X组
		$teamNum = 1;
		$n=0;
		foreach ($Users as $User) {
//			$User->分组 = str_replace( '[X]', sprintf("%02d", $teamNum), config("my.分组.组名.$item") );
			$User->分组 = str_ireplace( '[X]', $teamNum, SysConfig::item(分组.组名) );
			$User->save();

			$n++;
			if ($n == $everyTeamNum) {
				$teamNum++;
				$n=0;
			}
		}
	}

	private function 分组_区域_固定数量($item, $group)
	{
		SysConfig::setItem($item);

		$Users = User::where('项目', $item)
			->where('组别', $group)
			->orderBy('编号')
			->get();
		//区域数量
//		$areaCount = count(config("my.分组.区域数组.$item"));
		$areaCount = count(SysConfig::item('分组.区域'));
		//每组人数
//		$everyTeamNum = config("my.分组.每组人数.$item");
		$everyTeamNum = SysConfig::item('分组.每组人数');

//		if ($areaCount == 0) {
//			return ;
//		}
		$everyAreaTeamCount = (int)ceil( count($Users) / $areaCount / $everyTeamNum );//每个区域的组数（进一法取整）
		$userOrder=0;//用户流水号
		$areaOrder=0;//区域序号
		$teamOrder=1;//小组序号
		for ($i = 0; $i < $everyAreaTeamCount * $everyTeamNum; $i++) {//这里其实无所谓，用个无限循环就可以了
			for ($j = 0; $j < $everyTeamNum; $j++) {
//				$teamName = config("my.分组.区域数组.$item")[$areaOrder]
//				. str_replace('[X]', $teamOrder, config("my.分组.组名.$item"));
				$teamName = SysConfig::item('分组.区域')[$areaOrder]
				. str_ireplace('[X]', $teamOrder, SysConfig::item('分组.组名'));
				$Users[$userOrder]->分组 = $teamName;
				$Users[$userOrder]->save();

				//超出用户总数就停止
				$userOrder++;
				if ($userOrder>=count($Users)) {
					return;
				}
			}

			$teamOrder++;
			if ($teamOrder > $everyAreaTeamCount) {
				$areaOrder++;
				$teamOrder=1;
			}
		}
	}

	public function 报名顺序()
	{
		$Users = User::orderBy('id')->get();
		$arrUsers = [];
		foreach ($Users as $user) {
			if (!isset($arrUsers[$user->单位])) {
				$arrUsers[$user->单位] = $user;
			}
		}

		foreach ($arrUsers as $user) {
			\DB::update("update users set 报名顺序=? where 单位=?", [$user->id, $user->单位]);
		}
	}


	public function postImport()
	{
		$filename = storage_path().'/import.xlsx';
		if (!move_uploaded_file($_FILES['excel']['tmp_name'], $filename)) {
			return \Redirct::to('/')->with('message','上传失败！');
		}
		$objExcel = \PHPExcel_IOFactory::load( $filename );
		$objSheet = $objExcel->getActiveSheet();
		$arrData=$objSheet->toArray();
		//导入EXCEL文件中的字段
		$templateFields = array_map('trim', $arrData[0]);
		//users数据表中的字段
		$DbTableFields=[];
		//检测出数据库USERS表中真实的字段
		foreach ($templateFields as $_field) {
			if (\Schema::hasColumn('users', $_field )) {
				$DbTableFields[]=$_field;
			}
		}

//		$fields = $arrData[0];
		$insertCount = 0;
		for ($i = 1; $i < count($arrData); $i++) {
			$row = $arrData[$i];
			$arrInsert = [];
			for ($j = 0; $j < count($row); $j++) {
				$field = $templateFields[$j];
				if ( in_array($field, $DbTableFields) && strlen($row[$j]) ) {
					$arrInsert[$field] = trim($row[$j]);
				}
			}
//			pd($arrInsert);
			if ($arrInsert) {
				User::create($arrInsert);
				$insertCount++;
			}
		}

		return \Redirect::to('/')->with('message', "成功，共导入{$insertCount}条数据！耗时：". $this->runnedTime());
	}

	/**
	 * 比赛完后有学生姓名不正确的
	 */
	public function 更改姓名()
	{
		$file = SysConfig::get('生成路径'). utf8ToGbk('/更改姓名.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $file );
		$objSheet = $objExcel->getActiveSheet();
		$arrData=$objSheet->toArray();
		for ($i = 1; $i < count($arrData); $i++) {
			\DB::update("update users set 姓名=? where 编号=? limit 1", [$arrData[$i][1], $arrData[$i][0]]);
		}
	}

	public function 添加名单()
	{
		$file = SysConfig::get('生成路径'). utf8ToGbk('/添加名单.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $file );
		$objSheet = $objExcel->getActiveSheet();
		$arrData=$objSheet->toArray();
		for ($i = 1; $i < count($arrData); $i++) {

			$data=[
				'单位'=>$arrData[$i][0],
				'姓名'=>$arrData[$i][1],
				'组别'=>$arrData[$i][2],
				'项目'=>$arrData[$i][3],
				'教练'=>$arrData[$i][4],
			];
			$data = array_map('trim', $data);
			$data['编号']=$this->新编号($data['项目'], $data['组别']);
			User::create($data);

			//保存以显示结果
			echo $data['编号']."\t".$data['姓名']."\n";
		}

		echo '<h1>以上名单添加成功</h1>';
		rename($file, SysConfig::get('生成路径'). utf8ToGbk('/添加名单_已添加.xlsx'));
		exit;
	}

	public function 新编号($item, $group)
	{
		$user = User::where('项目', $item)
			->where('组别', $group)
			->orderBy('编号','desc')
			->first();
		$编号 = $user->编号;
		preg_match('/(\d+)$/', $编号, $arr);
		$num=(int)$arr[1] + 1;
		$prefix = preg_replace('/\d+$/', '', $编号);
		return $prefix.sprintf('%03d', $num);
	}


}