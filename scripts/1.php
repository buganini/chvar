<?php
$db=new mysqli('localhost','chvar','idiot','chvar');
$db->autocommit(true);
$db->query('SET NAMES UTF8');
$res=$db->query('SELECT * FROM `data`');
$acc=array();
$map=array();
$counted_master=array();
while($r=$res->fetch_assoc()){
	if(!isset($counted_master[$r['master']])){
		$counted_master[$r['master']]=1;
		$acc[$r['master']]++;
	}
	$acc[$r['slave']]++;
	$list[$r['master']][]=$r['slave'];
}
$id=0;
foreach($list as $master=>$slaves){
	$in=true;
	if($acc[$master]>1){
		$in=false;
	}else{
		foreach($slaves as $slave){
			if($acc[$slave]>1){
				$in=false;
				break;
			}
		}
	}
	if($in){
		++$id;
		$db->query('INSERT INTO `group` (`id`,`data`) VALUES ('.$id.',"'.$master.'")');
		foreach($slaves as $slave){
			$db->query('INSERT INTO `group` (`id`,`data`) VALUES ('.$id.',"'.$slave.'")');
		}
	}else{
		if($acc[$master]>1){
			echo '"';
			echo $master;
			echo '"';
		}else{
			echo $master;
		}
		foreach($slaves as $slave){
			echo ' ';
			if($acc[$slave]>1){
				echo '"';
				echo $slave;
				echo '"';
			}else{
				echo $slave;
			}
		}
		echo "\n";
	}
}
?>
