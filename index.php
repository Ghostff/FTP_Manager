
<style>
.dir{
	border:1px solid #ddd;
	font-family: "Gill Sans", "Gill Sans MT", "Myriad Pro", "DejaVu Sans Condensed", Helvetica, Arial, sans-serif;
	font-size:13px;
	border-radius: 5px;
	max-width: 250px;
	width: 150px;
	margin-bottom: 5px;
	padding: 0px 0 5px 10px;
	cursor:pointer;
}
.dir:hover{
	background: #efefef;
}
.dir .dir_icon {
	margin-right: 10px;	
	color: #C8B327;
}
.dir .file_icon {
	padding: 6px;
	background: url('assets/text_document.png') no-repeat;
	background-position: 0 10px;
	background-size: contain;
	margin-right: 10px;	
}
</style>

<?php

require 'src/Ftp.php';

$config = array(

	'host' 		=> 'ftp.ghostff.com',
	'port'		=> 21,
	'timeout'	=> 90,
	'username'	=> '',
	'password'	=> '',
	'path'		=> '',
	'test'		=> true 
	
);

$ftp = new Ftp($config);

var_dump($ftp->erros);

echo $ftp->listDirectries();