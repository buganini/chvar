<?php
$file=$argv[1];
$table=basename($file);
$table=str_replace('.txt','',$table);

$db=new mysqli('localhost','chvar','idiot','chvar');
$db->autocommit(true);
$db->query('SET NAMES UTF8');

if($table=='attr1' || $table=='attr2'){
	$db->query('DELETE FROM  `'.$table.'`');
	$t=file_get_contents($file);
	$a=explode("\n",trim($t));
	$f=explode("\t",trim($a[0]));
	foreach($f as $k=>$v){
		$f[$k]='`'.strtolower($f[$k]).'`';
	}
	$fn=count($f);
	$f=implode(',',$f);
	for($i=1;$i<count($a);++$i){
		$d=explode("\t",trim($a[$i]));
		foreach($d as $k=>$v){
			$d[$k]='"'.$d[$k].'"';
		}
		for(;$k<$fn;++$k){
			$d[$k]='""';
		}
		$db->query('INSERT INTO `'.$table.'` ('.$f.') VALUES ('.implode(',',$d).')');
	}
}elseif($table=='group1' || $table=='group2'){
	$db->query('DELETE FROM  `'.$table.'`');
	$fp=fopen($file,'r');
	while(($line=fgets($fp))!==false){
		$a=explode("\t",$line);
		if(count($a)!=2)
			continue;
		$id=trim($a[0]);
		$data=trim($a[1]);
		$db->query('INSERT INTO `'.$table.'` (`id`,`data`) VALUES ("'.$id.'","'.$data.'")');
	}
	fclose($fp);
}

$db->close();
?>
