<?php
$file=$argv[1];
$table=basename($file);
$table=str_replace('.txt','',$table);

$db=new mysqli('localhost','chvar','idiot','chvar');
$db->autocommit(true);
$db->query('SET NAMES UTF8');

if($table=='attr1' || $table=='attr2'){
	$fp=fopen($file,'r');
	$i=0;
	while(($line=fgets($fp))!==false){
		$a=explode("\t",$line);
		if($i==0){
			for($j=0;$j<count($a);++$j)
				$a[$j]='`'.trim($a[$j]).'`';
			$fields=implode(',',$a);
		}else{
			for($j=0;$j<count($a);++$j)
				$a[$j]='"'.trim($a[$j]).'"';
			$db->query('INSERT INTO `'.$table.'` ('.$fields.') VALUES ('.implode(',',$a).')');
		}
		$i+=1;
	}

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
