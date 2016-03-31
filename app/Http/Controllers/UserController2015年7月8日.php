<?php namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller {

	public function getMain()
	{
		dd(User::fields());
//		$this->设置分组();
//		$this->设置报名顺序();
//		$this->设置编号();
		//$this->生成裁判用表();
		//$this->生成裁判用表('组别');
		//$this->生成裁判用表('分组');
		$this->生成分组情况表();
//		$this->test();

		echo 'user/main ok <br>';
		echo $this->runnedTime();
	}

	public function getTest()
	{
		$excelFileName = iconv('utf-8','gbk',base_path().'/生成/裁判用表（默认分页）' . date("Y年m月d日") . '.xlsx');
		$objExcel = \PHPExcel_IOFactory::load( $excelFileName );
		$objSheet = $objExcel->getActiveSheet();
		$objPageSetup = $objSheet->getPageSetup();
			$objPageSetup->setFitToPage(true);
			$objPageSetup->setFitToWidth(1);
			$objPageSetup->setFitToHeight(20);
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objWriter->save('g:/a.xlsx');
	}

	public function getBuildjudge()
	{
		$this->生成裁判用表();
		$this->生成裁判用表('组别');
		$this->生成裁判用表('分组');
		return Redirect::to('/')->with('message', '裁判用表生成完毕！');
	}

	/**
	 * @param null $pageBreak
 	 * 参加$pageBreak 取值：null/组别/分组 表示：无分页/按组别分页/按分组分页
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function 生成裁判用表($pageBreak=null)
	{
		$arrItems = config('my.项目');
		$arrGroups = config('my.组别');
		$arrFirstDataRowNum = config('my.首条数据行号');

		$objExcel = \PHPExcel_IOFactory::load( mb_convert_encoding(config('my.模板.裁判用表'), 'gbk', 'utf-8') );

		//各项目开始循环
		for ($i = 0; $i < count($arrItems); $i++) {
			//项目名称
			$item = array_values($arrItems)[$i];
			//项目简称
			$shorterItem = array_keys($arrItems)[$i];
			//工作表名
			$sheetName = $this->lerrers[$i] . $shorterItem;

			$objSheet = $objExcel->getSheetByName($sheetName);
			$Users = User::where('项目', $item)
						->orderBy('编号')
						->get();
			//第一条数据的行号
			$firstDataRowNum = $arrFirstDataRowNum[$i];
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

					//插入分页符
					if ($pageBreak == '组别' && $templateKey=='组别') {	//根据组别分页
						if (!strlen($lastCellValue)) {
							$lastCellValue = $User->组别;
						}
						if (strlen($lastCellValue) && $lastCellValue != $User->组别) {
							$objSheet->setBreak("A".($j+$firstDataRowNum-1), \PHPExcel_Worksheet::BREAK_ROW);
						}
						$lastCellValue = $User->组别;

					} elseif ($pageBreak == '分组' && $templateKey=='分组') {	//根据分组分页
						if (!strlen($lastCellValue)) {
							$lastCellValue = $User->分组;
						}
						if (strlen($lastCellValue) && $lastCellValue != $User->分组) {
							$objSheet->setBreak("A".($j+$firstDataRowNum-1), \PHPExcel_Worksheet::BREAK_ROW);
						}
						$lastCellValue = $User->分组;
					}

				}
				$j++;
			}

			//页面设置
			$objPageSetup = $objSheet->getPageSetup();
			$objPageSetup->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);//纸张横向
			$objPageSetup->setRowsToRepeatAtTopByStartAndEnd(1, $firstDataRowNum-1);//打印标题行
			$objPageSetup->setHorizontalCentered(true);//水平居中

			$objSheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 '. config('my.比赛名称') . "&\"宋体,常规\"&14（{$item}）" );
			$objSheet->getHeaderFooter()->setOddFooter( '&L&P/&N页'
														.'&R裁判员签名_______________'
														.' 项目裁判长签名_______________'
			);
//			$objPageSetup->setFitToPage(true);
//			$objPageSetup->setFitToWidth(1);
//			$objPageSetup->setFitToHeight(10);
		}

		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$excelFileName = iconv('utf-8','gbk',base_path().'/生成/裁判用表（默认分页）' . date("Y年m月d日") . '.xlsx');
		if ($pageBreak=='分组') {
			$excelFileName = iconv('utf-8','gbk',base_path().'/生成/裁判用表（分组分页）' . date("Y年m月d日") . '.xlsx');
		} elseif ($pageBreak=='组别') {
			$excelFileName = iconv('utf-8','gbk',base_path().'/生成/裁判用表（组别分页）' . date("Y年m月d日") . '.xlsx');
		}
		$objWriter->save($excelFileName);
		echo 'ok';
	}

	public function getBuildteam()
	{
		$this->生成分组情况表();
		return Redirect::to('/')->with('message', '分组情况表生成完毕！');
	}

	/**
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function 生成分组情况表()
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
		echo 'ok';
	}
	
	public function getBuildscore()
	{
		$this->生成成统用表();
		return Redirect::to('/')->with('message', '成统用表生成完毕！');
	}
	/**
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function 生成成统用表()
	{
		$arrItems = config('my.项目');
		$arrGroups = config('my.组别');
		$objExcel = \PHPExcel_IOFactory::load( mb_convert_encoding(config('my.模板.成统用表'), 'gbk', 'utf-8') );
		
		//各项目开始循环
		for ($i = 0; $i < count($arrItems); $i++) {
			//项目名称
			$item = array_values($arrItems)[$i];
			//项目简称
			$shorterItem = array_keys($arrItems)[$i];
			//工作表名
			$sheetName = $this->lerrers[$i] . $shorterItem;
			
			$objSheet = $objExcel->getSheetByName($sheetName);
			$Users = User::where('项目', $item)
				->orderBy('编号')
				->get();
			//第一条数据的行号
			$firstDataRowNum = 3;
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
			
		}
		
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$excelFileName = iconv('utf-8','gbk',base_path().'/生成/成统用表' . date("Y年m月d日") . '.xlsx');
		$objWriter->save($excelFileName);
		echo 'ok';
	}

	public function getSetnum()
	{
		$this->设置编号();
		return \Redirect::to('/')->with('message', '号码编排成功！');
	}
	/**
	 * 编排编号
	 */
	public function 设置编号()
	{
		$arrItems = config('my.项目');
		$arrGroups = config('my.组别');

		for ($i = 0; $i < count($arrItems); $i++) {
			$item = array_values($arrItems)[$i];
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
					$编号 = $this->lerrers[$i] . $this->lerrers[$j] . sprintf("%03d", $n);
					$user->编号 = $编号;
					$user->save();
					$n++;
				}//foreach
			}//for j
		}//for i
	}

	public function getSetteam()
	{
		$this->设置分组();
		return \Redirect::to('/')->with('message', '分组设置成功！');
	}

	/**
	 * 设置分组
	 * 分组前必须先把编号编排好
	 */
	public function 设置分组()
	{
		$User = User::first();
		$User->编号 == '' && exit('[设置分组]前请先[设置编号]!');

		$arrItems = config('my.项目');
		$arrGroups = config('my.组别');

		for ($i = 0; $i < count($arrItems); $i++) {
			$item = array_values($arrItems)[$i];
			for ($j = 0; $j < count($arrGroups); $j++) {
				$group = $arrGroups[$j];

				switch (config('my.分组.方式')) {
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
		$everyTeamNum = config("my.分组.每组人数.$item");
		//第X组
		$teamNum = 1;
		$n=0;
		foreach ($Users as $User) {
//			$User->分组 = str_replace( '[X]', sprintf("%02d", $teamNum), config("my.分组.组名.$item") );
			$User->分组 = str_replace( '[X]', $teamNum, config("my.分组.组名.$item") );
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
		$Users = User::where('项目', $item)
			->where('组别', $group)
			->orderBy('编号')
			->get();
		//区域数量
		$areaCount = count(config("my.分组.区域数组.$item"));
		//每组人数
		$everyTeamNum = config("my.分组.每组人数.$item");
		//每个区域的组数（进一法取整）
		$everyAreaTeamCount = (int)ceil( count($Users) / $areaCount / $everyTeamNum );
		//用户流水号
		$userOrder=0;
		//区域序号
		$areaOrder=0;
		//小组序号
		$teamOrder=1;
		for ($i = 0; $i < $everyAreaTeamCount * $everyTeamNum; $i++) {//这里其实无所谓，用个无限循环就可以了
			for ($j = 0; $j < $everyTeamNum; $j++) {
				$teamName = config("my.分组.区域数组.$item")[$areaOrder]
					. str_replace('[X]', $teamOrder, config("my.分组.组名.$item"));
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

	public function 设置报名顺序()
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

	/**
	 * 下载数据导入模板
	 */
	public function getDownimport()
	{
		return \Response::download(
			base_path().iconv('utf-8', 'gbk', '/模板/import.xlsx'),
			'数据导入'.date('Y-m-d').'.xlsx',
			["Expires:-1","Cache-Control:no-cache","Pragma:no-cache"]
		);
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
		$fields = array_map('trim', $arrData[0]);
		$fields = $arrData[0];
		$insertCount = 0;
		for ($i = 1; $i < count($arrData); $i++) {
			$row = $arrData[$i];
			$arrInsert = [];
			for ($j = 0; $j < count($row); $j++) {
				$field = $fields[$j];
				if ( \Schema::hasColumn('users', $field ) && strlen($row[$j]) ) {
					$arrInsert[$field] = trim($row[$j]);
				}
			}
//			pd($arrInsert);
			if ($arrInsert) {
				User::create($arrInsert);
				$insertCount++;
			}

		}

		return \Redirect::to('/')->with('message', "成功，共导入{$insertCount}条数据！");
	}


	public function runnedTime()
	{
		return microtime(true) - LARAVEL_START;
	}
}