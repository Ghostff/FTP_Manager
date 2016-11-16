<?php

class Ftp
{
    
    public $erros = array();
    
    private $test = false;
    private $host = null;
    private $port = 21;
    private $timeout = 90;
    private $username = null;
    private $password = null;
    private $connection = null;
    private $path = null;
	private $sync = false;
	private $sync_dir;
	private $UI = false;
    
	
	private $connected = false;
	
	private $styled = false;
    
    private function connect()
    {
        @$ftp_stream = ftp_connect($this->host, $this->port, $this->timeout);
        if ( ! $ftp_stream && $this->test) {
            $this->erros[] = 'Couldn\'t connect as ' . $ftp_address;
        }
        else {
            $this->connection = $ftp_stream;
            @$ftp_stream = ftp_login($ftp_stream, $this->username, $this->password);
            if( ! @$ftp_stream && $this->test) {
                $this->erros[] = 'Couldn\'t connect (Wrong username or password)';
            }
			else {
				$this->connected = true;
				return true;
			}
        }
		$this->connected = false;
		return false;
    }
    
    private function isDirectory($dir, $conn_id = null)
    {
        if ( @ ftp_chdir($conn_id ?: $this->connection, $dir)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function __construct($config)
    {
        foreach ($config as $name => $value) {
			$name = trim($name);
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
            else {
                $this->erros[] = 'invalid property ' . $name;
            }
        }
		
       if ($this->connect()) {
			if ($this->sync) {
			
				$DS = DIRECTORY_SEPARATOR;
				$this->sync_dir = __DIR__ . $DS . '..' . $DS . $this->sync_dir;
				
				if ( ! file_exists($this->sync_dir)) {
					mkdir($this->sync_dir);
				}	
			}
	   }
    }
    
    public function __get($name)
    {
        if (strcasecmp($name, 'sysType') == 0) {
            return ftp_systype($this->connection);
        }
    }
    
    public function directories($type = null)
    {
		if ( ! $this->connected) {
			return;	
		}
        /*
        * Switching to passive mode to prevent boolean return cause by server's firewall configuration
        */
        ftp_pasv($this->connection, true);
            
		$files = ftp_rawlist($this->connection, $this->path);
		//remove . and .. from 
		array_shift($files);
		array_shift($files);
		
		$list =  array();
		$ui = array();
		array_map(function($name) use (&$list, $type, &$ui) {
			
			$chunks = preg_split("/\s+/", $name);

			if ($chunks[0][0] == 'd' || $chunks[0][0] == '-') {
				$item = array();
				list(
					$item['permisions'],
					$item['number'],
					$item['user'],
					$item['group'],
					$item['size'],
					$item['month'],
					$item['day'],
					$item['time']
				) = $chunks; 
				
				if ($this->sync) {
					if ($chunks[0][0] == 'd') {
						
						$path = $this->sync_dir . DIRECTORY_SEPARATOR . $chunks[8];
						if ( ! file_exists($path)) {
							mkdir($path);
						}
					}
					else {
						ftp_get(
							$this->connection,
							$this->sync_dir . DIRECTORY_SEPARATOR . $chunks[8],
							$chunks[8],
							FTP_BINARY
						);
					}
				}
				
				if ($type) {
					if (array_key_exists($type, $item)) {
					    if ($this->UI) {
					        $ui[$chunks[8]] = $item;
					    }
						$list[$chunks[8]] = $item[$type];
					}
					else {
						$this->erros[] = 'invalid directories Key ' . $type;	
					}
				}
				else {
					if ($this->UI) {
						$ui[$chunks[8]] = $item;
					}
					$list[$chunks[8]] = $item;	
				}
			}
			
		}, $files);
		
		if ($this->UI) {
		   return $this->UIRender($ui); 
		}
		else {
		    return $list;
		}

    }
    
    private function UIStylesheet()
    {
		if ($this->styled) {
			return;	
		}
        $style = '<style>
					form{
						margin:2px;
					}
                    .dir{
                    	border:1px solid #ddd;
                    	font-family: "Gill Sans", "Gill Sans MT", "Myriad Pro",
						"DejaVu Sans Condensed", Helvetica, Arial, sans-serif;
                    	font-size:13px;
                    	border-radius: 5px;
                    	padding: 2px;
                    	cursor:pointer;
						text-align: left;
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
                    	background: url(\'assets/text_document.png\') no-repeat;
                    	background-position: 0 10px;
                    	background-size: contain;
                    	margin-right: 10px;	
                    }
                </style>' . PHP_EOL;
        
		$this->styled = true;
        return $style;
    }
    
	private function isPost($name, $is_dir)
	{
		$new_name = str_replace('.', '_', $name);
		$dir_ptr = $this->path;
		
		if (isset($_POST[$new_name])) {
			
			$this->path = $name;
			return '<div style="margin-left:20px;">' . $this->directories() . '</div>';
		}
	}
	
    private function UIRender($files)
    {
        $dir = null;
        
         uksort($files,  function ($a, $b) use ($files) {
            if ($files[$a]['permisions'][0] == '-') {
                return 1;
            }
            return -1;
        });
        
        foreach ($files as $names => $attr) {            
            $icon = '';
			$is_dir = false;
            if ($attr['permisions'][0] == 'd') {
				$is_dir = true;
                $icon = '<span class="dir_icon">&#128194;</span>';
            }
            elseif ($attr['permisions'][0] == '-') {
                $icon = '<span class="file_icon"></span>';
            }
            $dir .= '<form method="post">
						<button class="dir" type="submit" name="' . $names .'">
							' . $icon .  $names . 
						'</button>
					 </form>' .  $this->isPost($names, $is_dir);
        }
        return $this->UIStylesheet() . $dir;
    }
	
	public function erros($seperators = '<br />')
	{
		if ( ! empty($this->erros)) {
			return implode($seperators, $this->erros);	
		}
	}
}

