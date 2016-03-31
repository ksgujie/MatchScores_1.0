<?php

for ($i = 0; $i < 200; $i++) {
	echo rand(3, 10).rand(0,1).rand(0,1).rand(0,1);
	echo "\t";
	echo rand(3, 10).rand(0,1).rand(0,1).rand(0,1);
	echo "\t";
	echo rand(3, 10).rand(0,1).rand(0,1).rand(0,1);
	echo "\t";
	echo rand(3, 10).rand(0,1).rand(0,1).rand(0,1);
	echo "\t";
	echo rand(3, 10).rand(0,1).rand(0,1).rand(0,1);
	echo "\t";
	echo rand(3, 10).rand(0,1).rand(0,1).rand(0,1);

	echo "\n";
}

die;///////
for ($ii = 32; $ii <= 42; $ii++) {
	for ($i = 1; $i <= 10; $i++) {
		echo "第{$ii}批\n";
	}
}
die;////////////////
	
	
$hwCount=3;//号位数
$groupCount =7;//每个号位人数

for ($ii = 0; $ii < 20; $ii++) {
	for ($hw = 1; $hw <= $hwCount; $hw++) {
		for ($i = 1; $i <= $groupCount; $i++) {
			$pc= ($groupCount*$ii) + $i;
			$s.= "{$hw}号位\t{$pc}批次\n";
		}
	}
}

file_put_contents('g:/a.txt', $s);