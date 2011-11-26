<?php
$file=$argv[1];
$table=basename($file);
$table=str_replace('.txt','',$table);

$db=new mysqli('localhost','chvar','idiot','chvar');
$db->autocommit(true);
$db->query('SET NAMES UTF8');

if($table=='attr1' || $table=='attr2'){

}elseif($table=='group1' || $table=='group2'){
	$fp=fopen($file,'r');
	while(($line=fgets($fp))!==false){
		$a=explode("\t",$line);
		if(count($a)!=2)
			continue;
		$id=trim($a[0]);
		$data=trim($a[1]);
		$db->query('INSERT INTO `'.$table.'` (`id`,`data`) VALUES ("'.$id.'","'.$data.'")');
	}
}

$db->close();
?>
