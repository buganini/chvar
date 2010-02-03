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
	$db->query('LOCK TABLES');
	$toent=bsdconv_create('bsdconv:utf-8,ascii');
	$tocp950=bsdconv_create('bsdconv:cp950,ascii');
	$tochewing=bsdconv_create('bsdconv:chewing:utf-8,ascii');
	$tocp936=bsdconv_create('bsdconv:cp936,ascii');
	$togb2312=bsdconv_create('bsdconv:gb2312,ascii');
	$tounicode=bsdconv_create('utf-8,ascii:bsdconv');
	$func();
	bsdconv_destroy($toent);
	bsdconv_destroy($tocp950);
	bsdconv_destroy($tochewing);
	bsdconv_destroy($tocp936);
	bsdconv_destroy($togb2312);
	bsdconv_destroy($tounicode);
	$db->query('UNLOCK TABLES');
	$db->close();
}

function hexval($s){
	$s=preg_replace('/[^0-9a-f]/si','',$s);
	$s=ltrim($s,'0');
	return strtoupper($s);
}


function chvar_addgrp1(){
	global $db,$tounicode;
	$a=preg_split('/\s+/',trim(str_replace('"','',$_POST['text'])));
	$nid=intval($_POST['id']);
	if(!$nid){
		$res=$db->query('SELECT `id` FROM `group1` ORDER BY `id` DESC LIMIT 1');
		if($r=$res->fetch_assoc()){
			$nid=$r['id']+1;
		}else{
			$nid=1;
		}
	}
	$done=array();
	for($i=0;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',bsdconv($tounicode,$a[$i]));
			$tmp=substr($tmp[0],2);
		}
		$a[$i]=$tmp;
		if($a[$i] && !isset($done[$a[$i]])){
			$done[$a[$i]]=0;
			$sql=$db->prepare('INSERT INTO `group1` (`id`,`data`) SELECT ?,? FROM `axiom` WHERE NOT EXISTS (SELECT 1 FROM `group1` WHERE `id`=? AND `data`=? LIMIT 1)');
			$sql->bind_param('isis',$nid,$a[$i],$nid,$a[$i]);
			$sql->execute();
		}
	}
}

function chvar_addgrp2(){
	global $db;
	$a=preg_split('/\s+/',trim(str_replace('"','',$_POST['text'])));
	$nid=intval($_POST['id']);
	if(!$nid){
		$res=$db->query('SELECT `id` FROM `group2` ORDER BY `id` DESC LIMIT 1');
		if($r=$res->fetch_assoc()){
			$nid=$r['id']+1;
		}else{
			$nid=1;
		}
	}
	$done=array();
	for($i=0;$i<count($a);++$i){
		if($a[$i] && !isset($done[$a[$i]])){
			$done[$a[$i]]=0;
			$sql=$db->prepare('INSERT INTO `group2` (`id`,`data`) SELECT ?,? FROM `axiom` WHERE NOT EXISTS (SELECT 1 FROM `group2` WHERE `id`=? AND `data`=? LIMIT 1)');
			$sql->bind_param('iiii',$nid,$a[$i],$nid,$a[$i]);
			$sql->execute();
		}
	}
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

function chvar_related1(){
	global $db,$tounicode,$toent;
	if(!$tounicode || !$toent){
		die('Failed');
	}
	$s=$_REQUEST['text'];
	$s=str_replace('"','',$s);
	$a=preg_split('/\s+/',trim($s));
	$rel=array();
	for($i=0,$j=1;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',bsdconv($tounicode,$a[$i]));
			$tmp=substr($tmp[0],2);
		}
		$a[$i]=$tmp;
		$res=$db->query('SELECT * FROM `group1` WHERE `data`="'.$a[$i].'"');
		while($r=$res->fetch_assoc()){
			$rel[$r['id']]=1;
		}
	}
	echo '<input type="radio" name="id" checked="checked" onClick="nid=0" /> &lt;New ID&gt;<br />';
	$a=array();
	foreach($rel as $k=>$v){
		$a[]=$k;
	}
	sort($a);
	foreach($a as $k){
		echo '<input type="radio" name="id" onClick="nid='.$k.'" /> <'.$k.'>';
		$res=$db->query('SELECT * FROM `group1` WHERE `id`="'.$k.'"');
		while($r=$res->fetch_assoc()){
			echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.bsdconv($toent,f($r['data'])).'" />]</a>';
		}
		echo '<br />';
	}
}

function chvar_related2(){
	global $db,$tounicode,$toent;
	if(!$tounicode || !$toent){
		die('Failed');
	}
	$s=$_REQUEST['text'];
	$s=str_replace('"','',$s);
	$a=preg_split('/\s+/',trim($s));
	$rel=array();
	for($i=0,$j=1;$i<count($a);++$i){
		$res=$db->query('SELECT * FROM `group2` WHERE `data`="'.$a[$i].'"');
		while($r=$res->fetch_assoc()){
			$rel[$r['id']]=1;
		}
	}
	echo '<input type="radio" name="id2" checked="checked" onClick="nid2=0" /> &lt;New ID&gt;<br />';
	$a=array();
	foreach($rel as $k=>$v){
		$a[]=$k;
	}
	sort($a);
	foreach($a as $k){
		echo '<input type="radio" name="id2" onClick="nid2='.$k.'" /> <'.$k.'>';
		$res=$db->query('SELECT * FROM `group2` WHERE `id`="'.$k.'"');
		while($r=$res->fetch_assoc()){
			echo ' <a onmouseover="showinfo2(\''.$r['data'].'\')">'.$r['data'].'</a>';
		}
		echo '<br />';
	}
}

function chvar_grp2can(){
	global $db,$tounicode,$toent;
	if(!$tounicode || !$toent){
		die('Failed');
	}
	$s=$_REQUEST['text'];
	$s=str_replace('"','',$s);
	$a=preg_split('/\s+/',trim($s));
	$rel=array();
	for($i=0,$j=1;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',bsdconv($tounicode,$a[$i]));
			$tmp=substr($tmp[0],2);
		}
		$a[$i]=$tmp;
		$res=$db->query('SELECT * FROM `group1` WHERE `data`="'.$a[$i].'"');
		while($r=$res->fetch_assoc()){
			$rel[$r['id']]=1;
		}
	}
	$a=array();
	foreach($rel as $k=>$v){
		$a[]=$k;
	}
	sort($a);
	foreach($a as $k){
		echo '<input type="checkbox" name="can2[]" checked="checked" value="'.$k.'" onClick="showrelated2()" /> <'.$k.'>';
		$res=$db->query('SELECT * FROM `group1` WHERE `id`="'.$k.'"');
		while($r=$res->fetch_assoc()){
			echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.bsdconv($toent,f($r['data'])).'" />]</a>';
		}
		echo '<br />';
	}
}

function chvar_info2(){
	global $db;
	$res=$db->query('SELECT * FROM `group1` WHERE `id`='.intval($_POST['text']));
	while($r=$res->fetch_assoc()){
		echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.bsdconv($toent,f($r['data'])).'" />]</a>';
	}
}

function chvar_info(){
	global $toent,$tochewing,$tocp936,$tocp950,$tounicode,$togb2312;
	if(!$toent || !$tochewing || !$tocp936 || !$tocp950 || !$tounicode || !togb2312){
		die('Failed');
	}
	$r=array(
		array('Unicode'),
		array('Entity'),
		array('Glyph'),
		array('Chewing'),
		array('CP950'),
		array('CP936'),
		array('GB2312')
	);
	$done=array();
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
		if($a[$i]){
			if(isset($done[$a[$i]])){
				continue;
			}
			$done[$a[$i]]=1;
			$r[0][$j]='<a'.((strlen($a[$i])>4)?' class="red"':'').'>'.$a[$i].'</a>';
 			$r[1][$j]=bsdconv($toent,f($a[$i]));
 			$r[2][$j]='<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($a[$i],'0').'" />';
 			$r[3][$j]=bsdconv($tochewing,f($a[$i]));
 			$r[4][$j]=pad(strtoupper(bin2hex(bsdconv($tocp950,f($a[$i])))));
 			$r[5][$j]=pad(strtoupper(bin2hex(bsdconv($tocp936,f($a[$i])))));
 			$r[6][$j]=pad(strtoupper(bin2hex(bsdconv($togb2312,f($a[$i])))));
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
}

function chvar_dump(){
	$func='chvar_dump_'.$_REQUEST['mode'];
	if(function_exists($func)){
		$func();
	}
}

function chvar_dump_level1(){
	global $db;
	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`,`data`');
	while($r=$res->fetch_assoc()){
		echo $r['id']."\t".$r['data'];
		echo "\n";
	}
}

function chvar_dump_level2(){
	global $db;
	$res=$db->query('SELECT * FROM `group2` ORDER BY `id`,`data`');
	while($r=$res->fetch_assoc()){
		echo $r['id']."\t".$r['data'];
		echo "\n";
	}
}
?>
