

<?php

require 'src/Ftp.php';

$config = array(

	'host' 		=> 'ftp.ghostff.com',
	'port'		=> 21,
	'timeout'	=> 90,
	'username'	=> 'chrysu76',
	'password'	=> 'Augunus76!',
	'path'		=> '',
	'sync'		=> true,
	'sync_dir'	=> 'ghostffFTP',
	'test'		=> true 
);

$ftp = new Ftp($config);

//var_dump($ftp->erros);

var_dump( $ftp->directories('permisions'));