<?php
ignore_user_abort(true);
set_time_limit(0);

function clionly(){
	global $argv;
	if(!isset($argv)){
		die('CLI only');
	}
}

function safeonly(){
#	if($_SERVER['REMOTE_ADDR']!='192.168.1.254')
	die();
}

if(isset($argv[1])){
	$_REQUEST['action']=$argv[1];
	$_REQUEST['mode']=$argv[2];
	$_REQUEST['for']=$argv[3];
}
$func='chvar_'.$_REQUEST['action'];
if(function_exists($func)){
	$db=new mysqli('localhost','chvar','idiot','chvar');
	$db->autocommit(true);
	$db->query('SET NAMES UTF8');
	$db->query('LOCK TABLES');
	$toent=new Bsdconv('bsdconv:utf-8');
	$tocp950=new Bsdconv('bsdconv:cp950');
	$tochewing=new Bsdconv('bsdconv:chewing:utf-8');
	$tocp936=new Bsdconv('bsdconv:cp936');
	$togb2312=new Bsdconv('bsdconv:gb2312');
	$togbk=new Bsdconv('bsdconv:gbk');
	$tounicode=new Bsdconv('utf-8:bsdconv');
	$touao250=new Bsdconv('bsdconv:_uao250');
	$func();
	unset($toent);
	unset($tocp950);
	unset($tochewing);
	unset($tocp936);
	unset($togb2312);
	unset($togbk);
	unset($tounicode);
	unset($touao250);
	$db->query('UNLOCK TABLES');
	$db->close();
}

function dac($d){
	$a=array();
	foreach($d as $k=>$v){
		$a[]=$k;
	}
	return $a;
}

function hl($s){
	echo "\033[1m";
	echo $s;
	echo "\033[m";
}

function manual_uniq($t,$d){
	global $toent,$tocp950,$tocp936,$togb2312,$togbk,$tochewing;
	$stdin=fopen('php://stdin','r');
	$in='';
	while(!isset($d[$in])){
		echo 'Choose one for '.$t.":\n";

		foreach($d as $k=>$v){
			echo "\t";
			echo $k;
		}
		echo "\n";

		echo hl('Entity');
		foreach($d as $k=>$v){
			echo "\t";
			echo $toent->conv(f($k));
		}
		echo "\n";

		echo hl('Chewing');
		foreach($d as $k=>$v){
			echo "\t";
			echo $tochewing->conv(f($k));
		}
		echo "\n";

		echo hl('CP950');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex($tocp950->conv(f($k))));
		}
		echo "\n";

		echo hl('CP936');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex($tocp936->conv(f($k))));
		}
		echo "\n";

		echo hl('GB2312');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex($togb2312->conv(f($k))));
		}
		echo "\n";

		echo hl('GBK');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex($togbk->conv(f($k))));
		}
		echo "\n";

		echo '> ';
		$in=strtoupper(trim(fgets($stdin)));
		if($in=='.'){
			$in='';
			break;
		}
	}
	fclose($stdin);
	return array($in);
}

function chvar_buildattr1(){
	clionly();
	global $db,$tocp950,$tocp936,$togb2312,$togbk;
	echo "Building level 1 group attribute...\n";
	$lastid=0;

	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`,`data`');
	$flush=0;
	while(($r=$res->fetch_assoc()) || ($flush=1)){
		if(($lastid!=$r['id'])){
			if($lastid){
				$r2=get_attr(1,$lastid);

				$_data=dac($data);
				$_bmp=dac($bmp);

				#cp950
				if($r2['cp950'] && isset($data[$r2['cp950']])){
					$_cp950=array($r2['cp950']);
				}else{
					$_cp950=dac($cp950);
					if(count($_cp950)>1){
						$_cp950=manual_uniq('CP950',$cp950);
					}
				}

				#tw
				if($r2['tw'] && isset($data[$r2['tw']])){
					$_tw=array($r2['tw']);
				}else{
					if($_cp950[0]){
						$_tw=$_cp950;
					}elseif(count($_data)==1){
						$_tw=$_data;
					}elseif(count($_bmp)==1){
						$_tw=$_bmp;
					}else{
						$_tw=manual_uniq('TW',$data);
					}
				}

				#gb2312
				if($r2['gb2312'] && isset($data[$r2['gb2312']])){
					$_gb2312=array($r2['gb2312']);
				}else{
					$_gb2312=dac($gb2312);
					if(count($_gb2312)>1){
						$_gb2312=manual_uniq('GB2312',$gb2312);
					}
				}
	
				#cp936
				if($r2['cp936'] && isset($data[$r2['cp936']])){
					$_cp936=array($r2['cp936']);
				}else{
					$_cp936=dac($cp936);
					if(count($_cp936)>1){
						if(isset($cp936[$_gb2312[0]])){
							$_cp936=$_gb2312;
						}else{
							$_cp936=manual_uniq('CP936',$cp936);
						}
					}
				}

				#gbk
				if($r2['gbk'] && isset($data[$r2['gbk']])){
					$_gbk=array($r2['gbk']);
				}else{
					$_gbk=dac($gbk);
					if(count($_gbk)>1){
						if(isset($gbk[$_gb2312[0]])){
							$_gbk=$_gb2312;
						}elseif(isset($gbk[$_cp936[0]])){
							$_gbk=$cp936;
						}else{
							$_gbk=manual_uniq('GBK',$gbk);
						}
					}
				}

				#cn
				if($r2['cn'] && isset($data[$r2['cn']])){
					$_cn=array($r2['cn']);
				}else{
					if($_gb2312[0]){
						$_cn=$_gb2312;
					}elseif($_cp936[0]){
						$_cn=$_cp936;
					}elseif($_gbk[0]){
						$_cn=$_gbk;
					}elseif(count($_bmp)==1){
						$_cn=$_bmp;
					}elseif(count($_data)==1){
						$_cn=$_data;
					}else{
						$_cn=manual_uniq('CN',$data);
					}
				}

				$db->query('DELETE FROM `attr1` WHERE `id`='.$lastid);
				$db->query('INSERT INTO `attr1` (`id`,`tw`,`cn`,`cp950`,`cp936`,`gb2312`,`gbk`) VALUES ('.$lastid.',"'.$_tw[0].'","'.$_cn[0].'","'.$_cp950[0].'","'.$_cp936[0].'","'.$_gb2312[0].'","'.$_gbk[0].'")');
			}
			$lastid=$r['id'];
			$bmp=$cn=$tw=$cp950=$cp936=$gb2312=$gbk=$data=array();
		}
		if($flush) break;
		if($tocp950->conv(f($r['data']))){ $cp950[$r['data']]=1; }
		if($tocp936->conv(f($r['data']))){ $cp936[$r['data']]=1; }
		if($togb2312->conv(f($r['data']))){ $gb2312[$r['data']]=1; }
		if($togbk->conv(f($r['data']))){ $gbk[$r['data']]=1; }
		if(strlen($r['data'])<=4){ $bmp[$r['data']]=1; }
		$data[$r['data']]=1;
	}
}

function magic_uniq($s,$d){
	global $tocp950,$tocp936,$togb2312,$togbk;
	$r=array();
	foreach($d as $k=>$v){
		if($tocp950->conv(f($k)) && $tocp936->conv(f($k)) && $togb2312->conv(f($k)) && $togbk->conv(f($k))){
			$r[]=$k;
		}
	}
	if(count($r)==1){
		return $r;
	}
	return manual_uniq($s,$d);
}

function chvar_buildattr2(){
	clionly();
	global $db,$tocp950,$tocp936,$togb2312,$togbk;
	echo "Building level 2 group attribute...\n";
	$lastid=0;

	$res=$db->query('SELECT * FROM `group2` ORDER BY `id`,`data`');
	$flush=0;
	while(($r=$res->fetch_assoc()) || ($flush=1)){
		if(($lastid!=$r['id'])){
			if($lastid){
				$res2=$db->query('SELECT * FROM `attr2` WHERE `id`='.$lastid);
				if($r2=$res2->fetch_assoc()){
				}else{
					$r2=array();
				}

				$_data=dac($data);
				$_bmp=dac($bmp);

				#cp950
				if($r2['cp950'] && isset($data[$r2['cp950']])){
					$_cp950=array($r2['cp950']);
				}else{
					$_cp950=dac($cp950);
					if(count($_cp950)>1){
						$_cp950=magic_uniq('CP950',$cp950);
					}
				}

				#tw
				if($r2['tw'] && isset($data[$r2['tw']])){
					$_tw=array($r2['tw']);
				}else{
					if($_cp950[0]){
						$_tw=$_cp950;
					}elseif(count($_data)==1){
						$_tw=$_data;
					}elseif(count($_bmp)==1){
						$_tw=$_bmp;
					}else{
						$_tw=magic_uniq('TW',$data);
					}
				}

				#gb2312
				if($r2['gb2312'] && isset($data[$r2['gb2312']])){
					$_gb2312=array($r2['gb2312']);
				}else{
					$_gb2312=dac($gb2312);
					if(count($_gb2312)>1){
						$_gb2312=magic_uniq('GB2312',$gb2312);
					}
				}
	
				#cp936
				if($r2['cp936'] && isset($data[$r2['cp936']])){
					$_cp936=array($r2['cp936']);
				}else{
					$_cp936=dac($cp936);
					if(count($_cp936)>1){
						if(isset($cp936[$_gb2312[0]])){
							$_cp936=$_gb2312;
						}else{
							$_cp936=magic_uniq('CP936',$cp936);
						}
					}
				}

				#gbk
				if($r2['gbk'] && isset($data[$r2['gbk']])){
					$_gbk=array($r2['gbk']);
				}else{
					$_gbk=dac($gbk);
					if(count($_gbk)>1){
						if(isset($gbk[$_gb2312[0]])){
							$_gbk=$_gb2312;
						}elseif(isset($gbk[$_cp936[0]])){
							$_gbk=$cp936;
						}else{
							$_gbk=magic_uniq('GBK',$gbk);
						}
					}
				}

				#cn
				if($r2['cn'] && isset($data[$r2['cn']])){
					$_cn=array($r2['cn']);
				}else{
					if($_gb2312[0]){
						$_cn=$_gb2312;
					}elseif($_cp936[0]){
						$_cn=$_cp936;
					}elseif($_gbk[0]){
						$_cn=$_gbk;
					}elseif(count($_bmp)==1){
						$_cn=$_bmp;
					}elseif(count($_data)==1){
						$_cn=$_data;
					}else{
						$_cn=magic_uniq('CN',$data);
					}
				}

				$db->query('DELETE FROM `attr2` WHERE `id`='.$lastid);
				$db->query('INSERT INTO `attr2` (`id`,`tw`,`cn`,`cp950`,`cp936`,`gb2312`,`gbk`) VALUES ('.$lastid.',"'.$_tw[0].'","'.$_cn[0].'","'.$_cp950[0].'","'.$_cp936[0].'","'.$_gb2312[0].'","'.$_gbk[0].'")');
			}
			$lastid=$r['id'];
			$bmp=$cn=$tw=$cp950=$cp936=$gb2312=$gbk=$data=array();
		}
		if($flush) break;
		$attr=get_attr(1,$r['data']);
		if($attr['cp950']){ $cp950[$attr['cp950']]=1; }
		if($attr['cp936']){ $cp936[$attr['cp936']]=1; }
		if($attr['gb2312']){ $gb2312[$attr['gb2312']]=1; }
		if($attr['gbk']){ $gbk[$attr['gbk']]=1; }
		if(strlen($attr['tw'])<=4){ $bmp[$attr['tw']]=1; }
		if(strlen($attr['cn'])<=4){ $bmp[$attr['cn']]=1; }
		$data[$attr['tw']]=1;
		$data[$attr['cn']]=1;
	}

}

function hexval($s){
	$s=preg_replace('/[^0-9a-f]/si','',$s);
	$s=ltrim($s,'0');
	return strtoupper($s);
}

function magic_split($s){
	$l=mb_strlen($s,'UTF-8');
	$r=array();
	$j=-1;
	$f=-1;
	for($i=0;$i<$l;++$i){
		$c=mb_substr($s,$i,1,'UTF-8');
		if(preg_replace('/[[:punct:][:space:]g-z]/si','',$c)==''){
			if($r[$j]){
				$j++;
				continue;
			}			
		}elseif(strlen($c)>1){
			$n=1;
		}else{
			$n=0;
		}
		if($f!=$n || $n){
			$f=$n;
			++$j;
		}
		$r[$j].=$c;
	}
	$ret=array();
	foreach($r as $a){
		if(strlen($a)==mb_strlen($a,'UTF-8')){
			$a=hexval($a);
		}
		if($a){
			$ret[]=$a;
		}
	}
	return array_unique($ret);
}

function chvar_addgrp1(){
	safeonly();
	global $db,$tounicode;
	$a=magic_split($_POST['text']);
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
			$tmp=explode(',',$tounicode->conv($a[$i]));
			$tmp=ltrim(substr($tmp[0],2),'0');
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
	safeonly();
	global $db;
	$a=magic_split($_POST['text']);
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

function z($s){
	$s=hexval($s);
	if(strlen($s) % 2){
		return '0'.$s;
	}
	return $s;
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
	$a=magic_split($_REQUEST['text']);
	$rel=array();
	for($i=0,$j=1;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',$tounicode->conv($a[$i]));
			$tmp=ltrim(substr($tmp[0],2),'0');
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
			echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.$toent->conv(f($r['data'])).'" />]</a>';
		}
		echo '<br />';
	}
}

function chvar_related2(){
	global $db,$tounicode,$toent;
	if(!$tounicode || !$toent){
		die('Failed');
	}
	$a=magic_split($_REQUEST['text']);
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
			$attr=get_attr(1,$r['data']);
			echo ' <a onmouseover="showinfo2(\''.$r['data'].'\')">{<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($attr['tw'],'0').'" />}</a>';
		}
		echo '<br />';
	}
}

function chvar_grp2can(){
	global $db,$tounicode,$toent;
	if(!$tounicode || !$toent){
		die('Failed');
	}
	$a=magic_split($_REQUEST['text']);
	$rel=array();
	for($i=0,$j=1;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',$tounicode->conv($a[$i]));
			$tmp=ltrim(substr($tmp[0],2),'0');
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
			echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.$toent->conv(f($r['data'])).'" />]</a>';
		}
		echo '<br />';
	}
}

function chvar_info2(){
	global $db,$toent;
	$res=$db->query('SELECT * FROM `group1` WHERE `id`='.intval($_POST['text']));
	while($r=$res->fetch_assoc()){
		echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.$toent->conv(f($r['data'])).'" />]</a>';
	}
}

function chvar_info(){
	global $db,$toent,$tochewing,$tocp936,$tocp950,$tounicode,$togb2312,$togbk;
	if(!$toent || !$tochewing || !$tocp936 || !$tocp950 || !$tounicode || !$togb2312 || !$togbk){
		echo 'Failed';
		return;
	}
	$r=array(
		array('Unicode'),
		array('Entity'),
		array('Glyph'),
		array('Chewing'),
		array('CP950'),
		array('CP936'),
		array('GB2312'),
		array('GBK')
	);
	$done=array();
	$a=magic_split($_REQUEST['text']);
	for($i=0,$j=1;$i<count($a);++$i){
		$tmp=hexval($a[$i]);
		if($tmp==''){
			$tmp=explode(',',$tounicode->conv($a[$i]));
			$tmp=ltrim(substr($tmp[0],2),'0');
		}
		$a[$i]=$tmp;
		if($a[$i]){
			if(isset($done[$a[$i]])){
				continue;
			}
			$done[$a[$i]]=1;
			$r[0][$j]='<a'.((strlen($a[$i])>4)?' class="red"':'').'>'.$a[$i].'</a>';
 			$r[1][$j]=$toent->conv(f($a[$i]));
			$style=' style="border:solid 1px #f00;"';
			$res=$db->query('SELECT * FROM `group1` WHERE `data`="'.$a[$i].'" LIMIT 1');
			if($res->fetch_assoc()){
				$style='';
			}
 			$r[2][$j]='<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.$a[$i].'"'.$style.' />';
 			$r[3][$j]=$tochewing->conv(f($a[$i]));
 			$r[4][$j]=pad(strtoupper(bin2hex($tocp950->conv(f($a[$i])))));
 			$r[5][$j]=pad(strtoupper(bin2hex($tocp936->conv(f($a[$i])))));
 			$r[6][$j]=pad(strtoupper(bin2hex($togb2312->conv(f($a[$i])))));
 			$r[7][$j]=pad(strtoupper(bin2hex($togbk->conv(f($a[$i])))));
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

function chvar_dump_group1(){
	global $db;
	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`,`data`');
	while($r=$res->fetch_assoc()){
		echo $r['id']."\t".$r['data'];
		echo "\n";
	}
}

function chvar_dump_group2(){
	global $db;
	$res=$db->query('SELECT * FROM `group2` ORDER BY `id`,`data`');
	while($r=$res->fetch_assoc()){
		echo $r['id']."\t".$r['data'];
		echo "\n";
	}
}

function chvar_dump_attr1(){
	global $db;
	$res=$db->query('SELECT * FROM `attr1` ORDER BY `id`');
	echo 'ID	TW	CN	CP950	CP936	GB2312	GBK';
	echo "\n";
	while($r=$res->fetch_assoc()){
		echo "{$r['id']}\t{$r['tw']}\t{$r['cn']}\t{$r['cp950']}\t{$r['cp936']}\t{$r['gb2312']}\t{$r['gbk']}";
		echo "\n";
	}
}

function chvar_dump_attr2(){
	global $db;
	$res=$db->query('SELECT * FROM `attr2` ORDER BY `id`');
	echo 'ID	TW	CN	CP950	CP936	GB2312	GBK';
	echo "\n";
	while($r=$res->fetch_assoc()){
		echo "{$r['id']}\t{$r['tw']}\t{$r['cn']}\t{$r['cp950']}\t{$r['cp936']}\t{$r['gb2312']}\t{$r['gbk']}";
		echo "\n";
	}
}

function get_attr($lv,$id){
	global $db,$attr;
	if(isset($attr[$lv][$id])){
		return $attr[$lv][$id];
	}
	$res=$db->query('SELECT * FROM `attr'.$lv.'` WHERE `id`='.$id);
	if($r=$res->fetch_assoc()){
		$attr[$lv][$id]=$r;
		return $r;
	}
	return array();
}

function acmp($a,$b){
	$r=strcmp($a[0],$b[0]);
	if($r!=0)
		return $r;
	else
		return strcmp($a[1],$b[1]);
}

function chvar_dump_fuzzy(){
	global $db;
	$for=$_REQUEST['for'];
	switch($for){
		case 'tw':
		case 'cn':
			break;
		default:
			return;
	}
	$acc=array(''=>-1);
	$dict=array();
	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`,`data`');
	while($r=$res->fetch_assoc()){
		$res2=$db->query('SELECT * FROM `group2` WHERE `data`='.$r['id']);
		$i=0;
		while($r2=$res2->fetch_assoc()){
			$attr=get_attr(2,$r2['id']);
			$dict[$r['data']][$attr[$for]]=1;
			++$acc[$attr[$for]];
			++$i;
		}
		if($i==0){
			$attr=get_attr(1,$r['id']);
			$dict[$r['data']][$attr[$for]]=1;
			++$acc[$attr[$for]];
		}
	}
	$ret=array();
	foreach($dict as $k=>$v){
		#XXX [(A]<B){C>} situation?
		$max='';
		foreach($v as $k2=>$v2){
			if($acc[$k2]>$acc[$max]){
				$max=$k2;
			}
		}
		$ret[]=array(z($k),z($max));
	}
	usort($ret,'acmp');
	foreach($ret as $a){
		echo $a[0]."\t".$a[1]."\n";		
	}
}

function chvar_dump_trans(){
	global $db,$tocp950,$tocp936,$togb2312,$togbk;
	$for=$_REQUEST['for'];
	switch($for){
		case 'cp950':
			$conv=$tocp950;
			break;
		case 'cp936':
			$conv=$tocp936;
			break;
		case 'gb2312':
			$conv=$togb2312;
			break;
		case 'gbk':
			$conv=$togbk;
			break;
		default:
			return;
	}
	$dict=array();
	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`');
	while($r=$res->fetch_assoc()){
		if($conv->conv(f($r['data']))){
			continue;
		}
		$attr=get_attr(1,$r['id']);
		if($attr[$for]){
			$dict[$r['data']][]=$attr[$for];
		}
	}
	$ret=array();
	foreach($dict as $k=>$v){
		if(count($v)==0){
			$res=$db->query('SELECT * FROM `group1` WHERE `data`="'.$k.'"');
			while($r=$res->fetch_assoc()){
				$res2=$db->query('SELECT * FROM `group2` WHERE `data`='.$r['id']);
				while($r2=$res2->fetch_assoc()){
					$attr=get_attr(2,$r2['id']);
					if($attr[$for]){
						$dict[$k][]=$attr[$for];
					}
				}
			}
		}
#		if(count($v)>1){
#			echo $k."\t".implode(' ',$v)."\n";
#			continue;
#		}
		$ret[]=array(z($k),z(bin2hex($conv->conv(f($v[0])))));
	}
	usort($ret,'acmp');
	foreach($ret as $a){
		echo $a[0]."\t".$a[1]."\n";
	}
}

function chvar_dump_norml(){
	global $db;
	$for=$_REQUEST['for'];
	switch($for){
		case 'tw':
		case 'cn':
			break;
		default:
			return;
	}
	$dict=array();
	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`');
	while($r=$res->fetch_assoc()){
		$attr=get_attr(1,$r['id']);
		if($attr[$for]){
			$dict[$r['data']][]=$attr[$for];
		}
	}
	$ret=array();
	foreach($dict as $k=>$v){
		if(count($v)>1){
			continue;
		}
		if($k==$v[0]){
			continue;
		}
		$ret[]=array(z($k),z($v[0]));
	}
	usort($ret,'acmp');
	foreach($ret as $a){
		echo $a[0]."\t".$a[1]."\n";
	}
}

function quote($s){
	return '"'.$s.'"';
}

function chvar_query(){
	global $db,$tounicode,$toent,$tochewing,$tocp950,$tocp936,$togb2312,$togbk,$touao250;
	if(!$tounicode || !$toent){
		die('Failed');
	}
	$wanteds=magic_split($_REQUEST['text']);
	foreach($wanteds as &$g){
		$tmp=hexval($g);
		if($tmp==''){
			$tmp=explode(',',$tounicode->conv($g));
			$tmp=ltrim(substr($tmp[0],2),'0');
		}
		$g=$tmp;
		unset($g);
	}
	$orphans=$wanteds;

	$dict=array();
	$group1=array();

	$res=$db->query('SELECT * FROM `group1` WHERE `data` in ('.implode(',',array_map('quote',$orphans)).')');
	while($r=$res->fetch_assoc()){
		$group1[$r['id']]=array();
		unset($orphans[array_search($r['data'],$orphans)]);
	}
	$orphans=array_values($orphans);
	$orphan_group1=array_keys($group1);


	$res=$db->query('SELECT * FROM `group2` WHERE `data` in ('.implode(',',array_map('quote',array_keys($group1))).')');
	$group2=array();
	while($r=$res->fetch_assoc()){
		$group2[$r['id']]=array();
	 	unset($orphan_group1[array_search($r['data'],$orphan_group1)]);
	}
	$orphan_group1=array_values($orphan_group1);

	if($group2){
		$res=$db->query('SELECT * FROM `group2` WHERE `id` in ('.implode(',',array_map('quote',array_keys($group2))).')');
		while($r=$res->fetch_assoc()){
			$group1[$r['data']]=array();
			$group2[$r['id']][]=$r['data'];
		}
	}

	$res=$db->query('SELECT * FROM `group1` WHERE `id` in ('.implode(',',array_map('quote',array_keys($group1))).')');
	while($r=$res->fetch_assoc()){
		$group1[$r['id']][]=$r['data'];
		$dict[$r['data']]=1;
	}

	foreach($orphans as $orphan){
		$dict[$orphan]=1;
	}

	$attr1=array();
	$res=$db->query('SELECT * FROM `attr1` WHERE `id` in ('.implode(',',array_map('quote',array_keys($group1))).')');
	while($r=$res->fetch_assoc()){
		$attr1[$r['id']]=$r;
	}

	$attr2=array();
	if($group2){
		$res=$db->query('SELECT * FROM `attr2` WHERE `id` in ('.implode(',',array_map('quote',array_keys($group2))).')');
		while($r=$res->fetch_assoc()){
			$attr2[$r['id']]=$r;
		}
	}

	foreach($dict as $k=>$v){
		$dict[$k]=array(
			'Chewing'=>$tochewing->conv(f($k)),
			'CP950'=>strtoupper(bin2hex($tocp950->conv(f($k)))),
			'CP936'=>strtoupper(bin2hex($tocp936->conv(f($k)))),
			'GB2312'=>strtoupper(bin2hex($togb2312->conv(f($k)))),
			'GBK'=>strtoupper(bin2hex($togbk->conv(f($k)))),
			'UAO250'=>strtoupper(bin2hex($touao250->conv(f($k)))),
		);
	}

	$dat=array(
		'wanteds'=>$wanteds,
		'orphans'=>$orphans,
		'orphan_group1'=>$orphan_group1,
		'group1'=>$group1,
		'group2'=>$group2,
		'attr1'=>$attr1,
		'attr2'=>$attr2,
		'dict'=>$dict,
	);
	echo json_encode($dat);
}
?>
