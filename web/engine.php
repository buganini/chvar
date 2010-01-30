<?php
ignore_user_abort(true);
set_time_limit(0);

if(isset($argv[1])){
	$_REQUEST['action']=$argv[1];
}
$func='chvar_'.$_REQUEST['action'];
if(function_exists($func)){
	$db=new mysqli('localhost','chvar','idiot','chvar');
	$db->autocommit(true);
	$db->query('SET NAMES UTF8');
	$func();
}

function hexval($s){
	$s=preg_replace('/[^0-9a-f]/si','',$s);
	$s=ltrim($s,'0');
	return strtoupper($s);
}

function mctime(){
	$t=explode(' ',microtime());
	return $t[0]+$t[1];
}

function chvar_add(){
	global $db;
	$tounicode=bsdconv_create('utf-8,ascii:bsdconv');
	$a=preg_split('/\s+/',trim($_POST['text']));
	$tmp=hexval($a[0]);
	if($tmp==''){
		$tmp=explode(',',bsdconv($tounicode,$a[0]));
		$tmp=substr($tmp[0],2);
	}
	$a[0]=$tmp;
	if($a[0]){
		for($i=1;$i<count($a);++$i){
			$tmp=hexval($a[$i]);
			if($tmp==''){
				$tmp=explode(',',bsdconv($tounicode,$a[$i]));
				$tmp=substr($tmp[0],2);
			}
			$a[$i]=$tmp;
			if($a[$i]){
				$sql=$db->prepare('INSERT INTO `data` (`master`,`slave`,`ctime`) SELECT ?,?,? FROM `axiom` WHERE NOT EXISTS (SELECT 1 FROM `data` WHERE `master`=? AND `slave`=? LIMIT 1)');
				$sql->bind_param('ssdss',$a[0],$a[$i],mctime(),$a[0],$a[$i]);
				$sql->execute();
			}
		}
	}
	bsdconv_destroy($tounicode);
	chvar_fetch();
}

function chvar_addgrp(){
	global $db;
	$tounicode=bsdconv_create('utf-8,ascii:bsdconv');
	$a=preg_split('/\s+/',trim($_POST['text']));
	$tmp=hexval($a[0]);
	if($tmp==''){
		$tmp=explode(',',bsdconv($tounicode,$a[0]));
		$tmp=substr($tmp[0],2);
	}
	$a[0]=$tmp;
	if($a[0]){
		for($i=1;$i<count($a);++$i){
			$tmp=hexval($a[$i]);
			if($tmp==''){
				$tmp=explode(',',bsdconv($tounicode,$a[$i]));
				$tmp=substr($tmp[0],2);
			}
			$a[$i]=$tmp;
			if($a[$i]){
				$sql=$db->prepare('INSERT INTO `map` (`master`,`slave`,`ctime`) ');
				$sql->bind_param('ssdss',$a[0],$a[$i],mctime(),$a[0],$a[$i]);
				$sql->execute();
			}
		}
	}
	bsdconv_destroy($tounicode);
}


function f($s){
	$s=hexval($s);
	if(strlen($s) % 2){
		return '010'.$s;
	}
	return '01'.$s;
}

function pad($s){
	if($s){
		return $s;
	}
	return '&nbsp;';
}

function chvar_info(){
	$toent=bsdconv_create('bsdconv:utf-8,ascii');
	$tocp950=bsdconv_create('bsdconv:cp950,ascii');
	$tochewing=bsdconv_create('bsdconv:chewing:utf-8,ascii');
	$touao=bsdconv_create('bsdconv:uao25,ascii');
	$tounicode=bsdconv_create('utf-8,ascii:bsdconv');
	if(!$toent || !$tochewing || !$touao || !$tocp950 || !$tounicode){
		die('Failed');
	}
	$r=array(
		array('Unicode'),
		array('Entity'),
		array('Glyph'),
		array('Chewing'),
		array('CP950'),
		array('UAO2.5')
	);
	$s=$_REQUEST['text'];
	$s=str_replace('"','',$s);
	$a=preg_split('/\s+/',trim($s));
	for($i=0,$j=1;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',bsdconv($tounicode,$a[$i]));
			$tmp=substr($tmp[0],2);
		}
		$a[$i]=$tmp;
		if($i==0 && empty($a[$i])){
			bsdconv_destroy($toent);
			bsdconv_destroy($tochewing);
			bsdconv_destroy($tocp950);
			bsdconv_destroy($touao);
			bsdconv_destroy($tounicode);
			die('Empty main glyph.');
		}
		if($a[$i]){
			$r[0][$j]='<a'.((strlen($a[$i])>4)?' class="red"':'').'>'.$a[$i].'</a>';
 			$r[1][$j]=bsdconv($toent,f($a[$i]));
 			$r[2][$j]='<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.$a[$i].'"'.(($i && ($a[$i]==$a[0]))?' class="hl"':'').' />';
 			$r[3][$j]=bsdconv($tochewing,f($a[$i]));
 			$r[4][$j]=pad(strtoupper(bin2hex(bsdconv($tocp950,f($a[$i])))));
 			$r[5][$j]=pad(strtoupper(bin2hex(bsdconv($touao,f($a[$i])))));
			++$j;
		}
	}
	echo '<table><tr><td>';
	for($i=0;$i<count($r);++$i){
		if($i){
			echo '</td></tr><tr><td>';
		}
		for($j=0;$j<count($r[$i]);++$j){
			if($j){
				echo '</td><td>';
			}
			echo $r[$i][$j];
		}
	}
	echo '</td></tr></table>';
	bsdconv_destroy($toent);
	bsdconv_destroy($tocp950);
	bsdconv_destroy($tochewing);
	bsdconv_destroy($touao);
	bsdconv_destroy($tounicode);
}

function chvar_fetch(){
	global $db;
	$sql=$db->prepare('SELECT `master`,`slave`,`ctime` FROM `data` WHERE `ctime` > ? ORDER BY `ctime` ASC LIMIT 25');
	$sql->bind_param('d',$_REQUEST['last']);
	$sql->execute();
	$sql->bind_result($m,$s,$c);
	$data=array();
#	$cv=bsdconv_create('bsdconv:utf-8,ascii');
#	while($sql->fetch()){
#		$data[]=array($m,$s,$c,bsdconv($cv,f($m)),bsdconv($cv,f($s)));
#	}
#	bsdconv_destroy($cv);
	echo json_encode($data);
}

function chvar_dump(){
	global $db;
	$res=$db->query('SELECT * FROM `data` ORDER BY `master`,`slave`');
	while($r=$res->fetch_assoc()){
		echo $r['master']."\t".$r['slave']."\n";
	}
}

function chvar_oomap(){
	global $db;
	$m=array();
	$res=$db->query('SELECT * FROM `data` ORDER BY `master`,`slave`');
	while($r=$res->fetch_assoc()){
		$m[$r['slave']][]=$r['master'];
	}
	foreach($m as $k=>$v){
		foreach($v as &$s){
			$last='';
			$c=0;
			while($last!=$s){
				++$c;
				if($c>10){
					die($s);
				}
				$last=$s;
				if(count($m[$s])==1){
					$s=$m[$s][0];
				}
			}
			unset($s);
		}
		if(count($v)==1){
			echo f($k)."\t".f($v[0])."\n";
		}
	}
}
?>
