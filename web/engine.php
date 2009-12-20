<?php
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
	$a=explode("\n",trim($_POST['text']));
	$a[0]=hexval($a[0]);
	if($a[0]){
		for($i=1;$i<count($a);++$i){
			$a[$i]=hexval($a[$i]);
			if($a[$i]){
				$sql=$db->prepare('INSERT INTO `data` (`master`,`slave`,`ctime`) SELECT ?,?,? FROM `axiom` WHERE NOT EXISTS (SELECT 1 FROM `data` WHERE `master`=? AND `slave`=? LIMIT 1)');
				$sql->bind_param('ssdss',$a[0],$a[$i],mctime(),$a[0],$a[$i]);
				$sql->execute();
			}
		}
	}
	chvar_fetch();
}

function f($s){
	$s=hexval($s);
	if(strlen($s) % 2){
		return '010'.$s;
	}
	return '01'.$s;
}

function chvar_fetch(){
	global $db;
	$sql=$db->prepare('SELECT `master`,`slave`,`ctime` FROM `data` WHERE `ctime` > ? ORDER BY `ctime` ASC LIMIT 25');
	$sql->bind_param('d',$_REQUEST['last']);
	$sql->execute();
	$sql->bind_result($m,$s,$c);
	$data=array();
	$cv=bsdconv_create('bsdconv:utf-8,ascii');
	while($sql->fetch()){
		$data[]=array($m,$s,$c,bsdconv($cv,f($m)),bsdconv($cv,f($s)));
	}
	bsdconv_destroy($cv);
	echo json_encode($data);
}

function chvar_dump(){
	global $db;
	$res=$db->query('SELECT * FROM `data` ORDER BY `master`,`slave`');
	while($r=$res->fetch_assoc()){
		echo $r['master']."\t".$r['slave']."\n";
	}
}
?>
