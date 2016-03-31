<?php namespace App\Modules;

use App\Models\User;

class Score extends Base
{
	public function 计算成绩()
	{
		$this->清空成绩();

		$this->纸折飞机直线距离赛();
		$this->纸折飞机奥运五环靶标赛();
		$this->悬浮纸飞机绕标挑战赛();
		$this->风火轮单向积分赛();
		$this->飞翼三角绕标赛();
		$this->电动纸飞机留空计时赛();

		$this->单项排名('纸折飞机直线距离赛','小学男子','升序');
		$this->单项排名('纸折飞机直线距离赛','小学女子','升序');
		$this->单项排名('纸折飞机直线距离赛','中学男子','升序');
		$this->单项排名('纸折飞机直线距离赛','中学女子','升序');
		
		$this->单项排名('电动纸飞机留空计时赛','小学男子','降序');
		$this->单项排名('电动纸飞机留空计时赛','小学女子','降序');
		$this->单项排名('电动纸飞机留空计时赛','中学男子','降序');
		$this->单项排名('电动纸飞机留空计时赛','中学女子','降序');
		
		$this->单项排名('纸折飞机奥运五环靶标赛','小学男子','降序');
		$this->单项排名('纸折飞机奥运五环靶标赛','小学女子','降序');
		$this->单项排名('纸折飞机奥运五环靶标赛','中学男子','降序');
		$this->单项排名('纸折飞机奥运五环靶标赛','中学女子','降序');
		
		$this->单项排名('悬浮纸飞机绕标挑战赛','小学男子','降序');
		$this->单项排名('悬浮纸飞机绕标挑战赛','小学女子','降序');
		$this->单项排名('悬浮纸飞机绕标挑战赛','中学男子','降序');
		$this->单项排名('悬浮纸飞机绕标挑战赛','中学女子','降序');
		
		$this->单项排名('风火轮单向积分赛','小学男子','降序');
		$this->单项排名('风火轮单向积分赛','小学女子','降序');
		$this->单项排名('风火轮单向积分赛','中学男子','降序');
		$this->单项排名('风火轮单向积分赛','中学女子','降序');
		
		$this->单项排名('飞翼三角绕标赛','小学男子','降序');
		$this->单项排名('飞翼三角绕标赛','小学女子','降序');
		$this->单项排名('飞翼三角绕标赛','中学男子','降序');
		$this->单项排名('飞翼三角绕标赛','中学女子','降序');



		$items = [
			'电动纸飞机留空计时赛',
			'纸折飞机奥运五环靶标赛',
			'悬浮纸飞机绕标挑战赛',
			'风火轮单向积分赛',
			'飞翼三角绕标赛',
		];
		foreach ($items as $item) {
			$groups = SysConfig::itemGroups($item);
			foreach ($groups as $group) {
				$this->奖项_按比例分配($item, $group, [ '一等奖'=>'0.2', '二等奖'=>'0.2', '三等奖'=>'0.3' ]);
			}
		}

//		$this->全部排名();
		$this->生成成绩册();
	}
	
	public function 纸折飞机直线距离赛()
	{
		$arrShow = [
			'小学男子'=>[
				4=>'15米以下',
				3=>'15-20米',
				2=>'20-25米',
				1=>'25米以上',
				''=>'',
			],
			'中学女子'=>[
				4=>'15米以下',
				3=>'15-20米',
				2=>'20-25米',
				1=>'25米以上',
				''=>'',
			],
			'小学女子'=>[
				4=>'10米以下',
				3=>'10-15米',
				2=>'15-20米',
				1=>'20米以上',
				''=>'',
			],
			'中学男子'=>[
				4=>'20米以下',
				3=>'20-25米',
				2=>'25-30米',
				1=>'30米以上',
				''=>'',
			],
		];
		$item = explode('::', __METHOD__)[1];
		$users = User::where('项目', $item)->where('原始成绩','!=','')->get();
		foreach ($users as $user) {
			if (strlen($user->原始成绩)) {
				$arrRawScore = unserialize($user->原始成绩);
				$arrScores = [];//保存原始成绩
				$arrScoreShow = [];//保存“成绩显示”
				for ($i = 0; $i < count($arrRawScore); $i++) {
					$currentScore = $arrRawScore[$i];
					if ($currentScore == '') {
						$arrScores[]=4;
					} else {
						$arrScores[]=$currentScore;
					}
					$arrScoreShow[] = $arrShow[$user->组别][$arrRawScore[$i]];
				}
				$user->成绩显示 = serialize($arrScoreShow);

				//奖项
				$arrJiangxiang = [
					1=>'一等奖',
					2=>'二等奖',
					3=>'三等奖',
					4=>'',
				];
				$user->奖项 = $arrJiangxiang[min($arrScores)];


				//补零
				foreach ($arrScores as $k=>$score) {
					$arrScores[$k] = Show::补零($score);
				}
				//排序（升序排列）， 这个有点特殊，一般降序
				sort($arrScores, SORT_STRING);
				//从大到小拼接
				$user->成绩排序 = join(',',$arrScores);

				//保存
				$user->save();
			}
		}
	}
	
	public function 纸折飞机奥运五环靶标赛()
	{
		$item = explode('::', __METHOD__)[1];
		$users = User::where('项目', $item)->where('原始成绩', '!=', '')->get();
		foreach ($users as $user) {
			if (strlen($user->原始成绩)) {
				$arrRawScore = unserialize($user->原始成绩);
				$arrScores_1 = [];//保存加补零后的成绩 第一轮
				$arrScores_2 = [];//保存加补零后的成绩 第二轮
				$arrScoreShow = [];//保存“成绩显示”
				//第一轮
				for ($i = 0; $i < 3; $i++) {
					$rawScore = $arrRawScore[$i];
					$m =  (int)substr($rawScore, 0, strlen($rawScore)-3); //起飞线。起飞线为10（两位数），就要取原始成绩的前2位，否则取前1位
					$j1 = (int)substr($rawScore,-3,1);//第一次是否进靶
					$j2 = (int)substr($rawScore,-2,1);//第二次是否进靶
					$j3 = (int)substr($rawScore,-1,1);//第三次是否进靶
					$arrScores_1[]=$m * ($j1 + $j2 + $j3);
					$arrScoreShow[] = Show::x标记('米', $rawScore, 3, ['×','√']);
				}
				//第二轮
				for ($i = 3; $i < 6; $i++) {
					$rawScore = $arrRawScore[$i];
					$m =  (int)substr($rawScore, 0, strlen($rawScore)-3); //起飞线。起飞线为10（两位数），就要取原始成绩的前2位，否则取前1位
					$j1 = (int)substr($rawScore,-3,1);//第一次是否进靶
					$j2 = (int)substr($rawScore,-2,1);//第二次是否进靶
					$j3 = (int)substr($rawScore,-1,1);//第三次是否进靶
					$arrScores_2[]=$m * ($j1 + $j2 + $j3);
					$arrScoreShow[] = Show::x标记('米', $rawScore, 3, ['×','√']);
				}

				$user->成绩显示 = serialize($arrScoreShow);

				//2轮总分合并为一个数组
				$arrScores = [array_sum($arrScores_1), array_sum($arrScores_2)];
				//补零
				foreach ($arrScores as $k=>$score) {
					$arrScores[$k] = Show::补零($score);
				}
				//排序
				rsort($arrScores, SORT_STRING);
				//总分 + 降序排列后的各个成绩
				$user->成绩排序 = join(',',$arrScores);
				//保存
				$user->save();
			}
		}
	}

	public function 悬浮纸飞机绕标挑战赛()
	{
		$item = explode('::', __METHOD__)[1];
		$users = User::where('项目', $item)->get();
		foreach ($users as $user) {
			if (strlen($user->原始成绩)) {
				$arrRawScore = unserialize($user->原始成绩);
				$arrScores = [];//保存加补零后的成绩
				$arrScoreShow = [];//保存“成绩显示”
				for ($i = 0; $i < count($arrRawScore); $i++) {
					$rawScore = $arrRawScore[$i];
					if (strlen($rawScore)) {
						$rawScore = Show::补零($rawScore, 9);
						preg_match('/^(\d\d)(\d\d\d\d\d\d)(\d)$/', $rawScore, $arr);
						$arrScores[] = ( $arr[1] + $arr[3] * 2 ) . $arr[2];
						$arrScoreShow[] = $arr[1]."圈 " . Show::分秒($arr[2]) . ' ' . Show::标记($arr[3], ['×','√']);
					} else {
						$arrScores[]=0;
						$arrScoreShow[] = '';
					}

				}
				$user->成绩显示 = serialize($arrScoreShow);

				//补零
				foreach ($arrScores as $k=>$score) {
					$arrScores[$k] = Show::补零($score);
				}
				$user->成绩排序 = $this->排序_得分大用时长($arrScores[0], $arrScores[1]);
				//保存
				$user->save();
			}
		}
	}


	public function 风火轮单向积分赛()
	{
		$item = explode('::', __METHOD__)[1];
		$users = User::where('项目', $item)->get();
		foreach ($users as $user) {
			if (strlen($user->原始成绩)) {
				$arrRawScore = unserialize($user->原始成绩);
				$arrScores = []; //保存加补零后的成绩，这里先为总分占个位
				//$arrScores[0] = 0;//这里先为总分占个位
				$arrScoreShow = [];//保存“成绩显示”
				for ($i = 0; $i < count($arrRawScore); $i++) {
					$rawScore = $arrRawScore[$i];
					$arrScores[] = $rawScore;
					$arrScoreShow[] = $rawScore.'分';
				}
				$user->成绩显示 = serialize($arrScoreShow);
				//算总分
				//$arrScores[0] = array_sum($arrScores);
				//补零
				foreach ($arrScores as $k=>$score) {
					$arrScores[$k] = Show::补零($score);
				}
				//排序
				rsort($arrScores, SORT_STRING);
				//降序排列后的各个成绩
				$user->成绩排序 = join(',',$arrScores);
				//保存
				$user->save();
			}
		}
	}


	public function 飞翼三角绕标赛()
	{
		$item = explode('::', __METHOD__)[1];
		$users = User::where('项目', $item)->get();
		foreach ($users as $user) {
			if (strlen($user->原始成绩)) {
				$arrRawScore = unserialize($user->原始成绩);
				$arrScores = []; //保存加补零后的成绩，这里先为总分占个位
				$arrScoreShow = [];//保存“成绩显示”
				for ($i = 0; $i < count($arrRawScore); $i++) {
					$rawScore = $arrRawScore[$i];
					$arrScores[] = $rawScore;
					$arrScoreShow[] = strlen($rawScore) ? $rawScore . '圈' : '';
				}
				$user->成绩显示 = serialize($arrScoreShow);

				//补零
				foreach ($arrScores as $k=>$score) {
					$arrScores[$k] = Show::补零($score);
				}
				//排序
				rsort($arrScores, SORT_STRING);
				//降序排列后的各个成绩
				$user->成绩排序 = join(',',$arrScores);
//				$user->成绩排序 = $this->排序_积分用时($arrScores[0], $arrScores[1]);
				//保存
				$user->save();
			}
		}
	}


	public function 电动纸飞机留空计时赛()
	{
		$item = explode('::', __METHOD__)[1];
		$users = User::where('项目', $item)->get();
		foreach ($users as $user) {
			if (strlen($user->原始成绩)) {
				$arrRawScore = unserialize($user->原始成绩);
				$arrScores = []; //保存加补零后的成绩，这里先为总分占个位
				$arrScoreShow = [];//保存“成绩显示”
				for ($i = 0; $i < count($arrRawScore); $i++) {
					$rawScore = $arrRawScore[$i];
					$arrScores[] = $rawScore;
					$arrScoreShow[] = Show::分秒($rawScore);
				}
				$user->成绩显示 = serialize($arrScoreShow);
				//补零
				foreach ($arrScores as $k=>$score) {
					$arrScores[$k] = Show::补零($score);
				}
				//排序
				rsort($arrScores, SORT_STRING);
				//降序排列后的各个成绩
				$user->成绩排序 = join(',',$arrScores);
				//保存
				$user->save();
			}
		}
	}

	public function 计算成绩_备份四川绵阳()
	{
		$this->清空成绩();

		//A1
		$item = '纸折飞机直线距离赛';
		SysConfig::setItem($item);
		$arrGroups=SysConfig::item('组别');
		foreach ($arrGroups as $group) {
			$users = User::whereRaw("项目=? and 组别=? and (成绩1!='' or 成绩2!='')", [$item, $group])->get();
			foreach ($users as $user) {
				//最终成绩
				$user->最终成绩1=$user->成绩1;
				$user->最终成绩2=$user->成绩2;
				//最好成绩
				$user->最好成绩 = max($user->最终成绩1, $user->最终成绩2);
				//显示
				$user->显示_最终成绩1 = $user->最终成绩1;
				$user->显示_最终成绩2 = $user->最终成绩2;
				$user->显示_最好成绩 = max($user->最终成绩1, $user->最终成绩2);
				//成绩排序
				$max=max((float)$user->最终成绩1, (float)$user->最终成绩2);
				$min=min((float)$user->最终成绩1, (float)$user->最终成绩2);
				$max = sprintf("%06s", $max);
				$min = sprintf("%06s", $min);
				$user->成绩排序 =  $max.$min;


				$user->save();
			}
		}

		//排名
		$this->全部排名();
		//奖项
		SysConfig::setItem($item);
		$arrGroups=SysConfig::item('组别');
		foreach ($arrGroups as $group) {
			$users = User::where('项目', $item)
				->where('组别', $group)
				->where('排名', '>', 0)
				->where('排名', '<=', 6)
				->get();
			foreach ($users as $user) {
				$user->奖项="第".ChineseNumber($user->排名).'名';
				$user->save();
			}
		}

		//B1-3
		$this->B('纸折飞机悬浮留空时间赛');
		$this->B('风火轮悬浮留空时间赛');
		$this->B('飞翼悬浮留空时间赛');
		//C1
		$item = '纸折飞机投掷靶标赛';
		SysConfig::setItem($item);
		$arrGroups=SysConfig::item('组别');
		foreach ($arrGroups as $group) {
//			$users = User::whereRaw("项目=? and 组别=? and 成绩1!='')", [$item, $group])->get();
			$users = User::where('项目',$item)
						->where('组别', $group)
						->where('成绩1', '<>', '')
						->get();
			foreach ($users as $user) {
				//最终成绩
				$user->最终成绩1=$user->成绩1;
				//最好成绩
				$user->最好成绩 = $user->成绩1;
				//显示
				$user->显示_最终成绩1 = $this->C1_显示_成绩($user->最终成绩1);
				$user->显示_最好成绩 = $this->C1_显示_成绩($user->最终成绩1);
				//成绩排序
				$user->成绩排序 = sprintf("%08s", $user->成绩1);

				$user->save();
			}
		}

		//排名
		$this->全部排名();
		//奖项
		SysConfig::setItem($item);
		$arrGroups=SysConfig::item('组别');
		foreach ($arrGroups as $group) {
			$users = User::where('项目', $item)
				->where('组别', $group)
				->where('排名', '>', 0)
				->where('排名', '<=', 6)
				->get();
			foreach ($users as $user) {
				$user->奖项="第".ChineseNumber($user->排名).'名';
				$user->save();
			}
		}

		//D1
		$this->B('手掷纸折留空时间赛');
	}//计算成绩

	public function C1_显示_成绩($score)
	{
		//距离
		$m=(int)($score/1000);
		$strJing=substr($score,-3,3);
		$jing='';
		for ($i = 0; $i < strlen($strJing); $i++) {
			$jing .= $strJing[$i]==0 ? '×' : '√';
		}
		return "{$m}米 $jing";
	}

	public function B($item)
	{
		//Bn
		SysConfig::setItem($item);
		$arrGroups=SysConfig::item('组别');
		foreach ($arrGroups as $group) {
			$users = User::whereRaw("项目=? and 组别=? and (成绩1!='' or 成绩2!='')", [$item, $group])->get();
			foreach ($users as $user) {
				$user->最终成绩1=$user->成绩1;
				$user->最终成绩2=$user->成绩2;
				//最好成绩
				$user->最好成绩 = max($user->最终成绩1, $user->最终成绩2);
				//显示
				$user->显示_最终成绩1 = $this->分秒($user->最终成绩1);
				$user->显示_最终成绩2 = $this->分秒($user->最终成绩2);
				$user->显示_最好成绩 = $this->分秒(max($user->最终成绩1, $user->最终成绩2));
				//成绩排序
				$max=max((float)$user->最终成绩1, (float)$user->最终成绩2);
				$min=min((float)$user->最终成绩1, (float)$user->最终成绩2);
				$max = sprintf("%08s", $max);
				$min = sprintf("%08s", $min);
				$user->成绩排序 =  $max.$min;

				$user->save();
			}
		}

		//排名
		$this->全部排名();
		//奖项
		SysConfig::setItem($item);
		$arrGroups=SysConfig::item('组别');
//		pp(SysConfig::$item);
//		pd($arrGroups);
		foreach ($arrGroups as $group) {
			$users = User::where('项目', $item)
				->where('组别', $group)
				->where('排名', '>', 0)
				->where('排名', '<=', 6)
				->get();
			foreach ($users as $user) {
				$user->奖项="第".ChineseNumber($user->排名).'名';
				$user->save();
			}
		}
	}



}