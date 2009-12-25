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

function pad($s){
	if($s){
		return $s;
	}
	return '&nbsp;';
}

function chvar_info(){
	$toent=bsdconv_create('bsdconv:utf-8,ascii');
	$tocp950=bsdconv_create('bsdconv:cp950,ascii');
	$tobig5=bsdconv_create('bsdconv:bg5-2003,ascii');
	$touao=bsdconv_create('bsdconv:uao25,ascii');
	if(!$toent || !$tobig5 || !$touao || !$tocp950){
		die('Failed');
	}
	$r=array(
		array('Entity'),
		array('Glyph'),
		array('CP950'),
		array('Big5-2k3'),
		array('UAO2.5')
	);
	$a=explode("\n",trim($_POST['text']));
	for($i=0,$j=1;$i<count($a);++$i,++$j){
		$a[$i]=hexval($a[$i]);
		if($i==0 && empty($a[$i])){
			bsdconv_destroy($toent);
			bsdconv_destroy($tobig5);
			bsdconv_destroy($tocp950);
			bsdconv_destroy($touao);
			die('Empty main glyph.');
		}
		if($a[$i]){
 			$r[0][$j]=bsdconv($toent,f($a[$i]));
 			$r[1][$j]='<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.$a[$i].'"'.(($i && ($a[$i]==$a[0]))?' class="hl"':'').' />';
 			$r[2][$j]=pad(strtoupper(bin2hex(bsdconv($tocp950,f($a[$i])))));
 			$r[3][$j]=pad(strtoupper(bin2hex(bsdconv($tobig5,f($a[$i])))));
 			$r[4][$j]=pad(strtoupper(bin2hex(bsdconv($touao,f($a[$i])))));
		}
	}
	echo '<table><tr><td>';
	for($i=0;$i<count($r);++$i){
		if($i){
			echo '</td></tr><tr><td>';
		}
		for($j=0;$j<count($r);++$j){
			if($j){
				echo '</td><td>';
			}
			echo $r[$i][$j];
		}
	}
	echo '</td></tr></table>';
	bsdconv_destroy($toent);
	bsdconv_destroy($tocp950);
	bsdconv_destroy($tobig5);
	bsdconv_destroy($touao);
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
