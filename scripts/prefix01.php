<?php
function form($s){
	$s=ltrim($s,'0');
	if(strlen($s)%2){
		return '010'.$s;
	}else{
		return '01'.$s;
	}
}
$s=file_get_contents($argv[1]);
$s=preg_replace('/([0-9a-f]+)/sie','form("\1")',$s);
echo $s;
?>
