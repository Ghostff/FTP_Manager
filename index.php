

<?php

require 'src/Ftp.php';

$config = array(

	'host' 		=> 'ftp.ghostff.com',
	'port'		=> 21,
	'timeout'	=> 90,
	'username'	=> 'chrysu76',
	'password'	=> 'Augunus76!',
	'path'		=> '',
	'sync'		=> false,
	'sync_dir'	=> 'ghostffFTP',
	'UI'		=> true,
	'test'		=> true 
);

$ftp = new Ftp($config);

//var_dump($ftp->erros);

echo( $ftp->directories('permisions'));