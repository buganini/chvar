<?php
ignore_user_abort(true);
set_time_limit(0);

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
	$toent=bsdconv_create('bsdconv:utf-8,ascii');
	$tocp950=bsdconv_create('bsdconv:cp950,ascii');
	$tochewing=bsdconv_create('bsdconv:chewing:utf-8,ascii');
	$tocp936=bsdconv_create('bsdconv:cp936,ascii');
	$togb2312=bsdconv_create('bsdconv:gb2312,ascii');
	$togbk=bsdconv_create('bsdconv:gbk,ascii');
	$tounicode=bsdconv_create('utf-8,ascii:bsdconv');
	$func();
	bsdconv_destroy($toent);
	bsdconv_destroy($tocp950);
	bsdconv_destroy($tochewing);
	bsdconv_destroy($tocp936);
	bsdconv_destroy($togb2312);
	bsdconv_destroy($togbk);
	bsdconv_destroy($tounicode);
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
			echo bsdconv($toent,f($k));
		}
		echo "\n";

		echo hl('Chewing');
		foreach($d as $k=>$v){
			echo "\t";
			echo bsdconv($tochewing,f($k));
		}
		echo "\n";

		echo hl('CP950');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex(bsdconv($tocp950,f($k))));
		}
		echo "\n";

		echo hl('CP936');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex(bsdconv($tocp936,f($k))));
		}
		echo "\n";

		echo hl('GB2312');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex(bsdconv($togb2312,f($k))));
		}
		echo "\n";

		echo hl('GBK');
		foreach($d as $k=>$v){
			echo "\t";
			echo strtoupper(bin2hex(bsdconv($togbk,f($k))));
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

function chvar_buildattr(){
	global $db,$tocp950,$tocp936,$togb2312,$togbk;
	echo "Building level 1 group attribute...\n";
	$lastid=0;

	$res=$db->query('SELECT * FROM `group1` ORDER BY `id`,`data`');
	$flush=0;
	while(($r=$res->fetch_assoc()) || ($flush=1)){
		if(($lastid!=$r['id'])){
			if($lastid){
				$res2=$db->query('SELECT * FROM `attr1` WHERE `id`='.$lastid);
				if($r2=$res2->fetch_assoc()){
				}else{
					$r2=array();
				}

				$_data=dac($data);
				$_bmp=dac($bmp);

				#cp950
				if(count($_cp950)==count($_data)){
					$_cp950=array('');
				}elseif($r2['cp950'] && isset($data[$r2['cp950']])){
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
				if(count($_gb2312)==count($_data)){
					$_gb2312=array('');
				}elseif($r2['gb2312'] && isset($data[$r2['gb2312']])){
					$_gb2312=array($r2['gb2312']);
				}else{
					$_gb2312=dac($gb2312);
					if(count($_gb2312)>1){
						$_gb2312=manual_uniq('GB2312',$gb2312);
					}
				}
	
				#cp936
				if(count($_cp936)==count($_data)){
					$_cp936=array('');
				}elseif($r2['cp936'] && isset($data[$r2['cp936']])){
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
				if(count($_gbk)==count($_data)){
					$_gbk=array('');
				}elseif($r2['gbk'] && isset($data[$r2['gbk']])){
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
					}elseif(count($_data)==1){
						$_cn=$_data;
					}elseif(count($_bmp)==1){
						$_cn=$_bmp;
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
		if(bsdconv($tocp950,f($r['data']))){ $cp950[$r['data']]=1; }
		if(bsdconv($tocp936,f($r['data']))){ $cp936[$r['data']]=1; }
		if(bsdconv($togb2312,f($r['data']))){ $gb2312[$r['data']]=1; }
		if(bsdconv($togbk,f($r['data']))){ $gbk[$r['data']]=1; }
		if(strlen($r['data'])<=4){ $bmp[$r['data']]=1; }
		$data[$r['data']]=1;
	}
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
			echo ' <a onmouseover="showinfo(\''.$r['data'].'\')">[<img src="http://www.unicode.org/cgi-bin/refglyph?24-'.ltrim($r['data'],'0').'" title="'.bsdconv($toent,f($r['data'])).'" />]</a>';
		}
		echo '<br />';
	}
}

function chvar_info2(){
	global $db,$toent;
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
			$tmp=ltrim(substr($tmp[0],2),'0');
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
	return;
}

function chvar_dump_fuzzy(){

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
		if(bsdconv($conv,f($r['data']))){
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
		$ret[]=array($k,$v[0]);
	}
	sort($ret);
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
		$ret[]=array($k,$v[0]);
	}
	sort($ret);
	foreach($ret as $a){
		echo $a[0]."\t".$a[1]."\n";
	}
}
?>
