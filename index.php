

<?php

require 'src/Ftp.php';

$config = array(

	'host' 		=> 'ftp.ghostff.com',
	'port'		=> 21,
	'timeout'	=> 90,
	'username'	=> '...',
	'password'	=> '...',
	'path'		=> '',
	'sync'		=> false,
	'sync_dir'	=> 'ghostffFTP',
	'UI'		=> true,
	'test'		=> true 
);

$ftp = new Ftp($config);

echo $ftp->erros();
echo $ftp->directories();