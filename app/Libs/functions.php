<?php

function pp($var, $exit=null) {
	echo '<pre>';
//	var_dump($var);
	print_r($var);
	echo '</pre>';
	$exit and exit();
}

function pd($var) {
	pp($var, true);
}

function utf8ToGbk($s) {
	return mb_convert_encoding($s, 'gbk', 'utf-8');
}

