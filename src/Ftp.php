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
    
    
    private function connect()
    {
        @$ftp_stream = ftp_connect($this->host, $this->port, $this->timeout);
        if ( ! $ftp_stream && $this->test) {
            $this->erros[] = 'Couldn\'t connect as ' . $ftp_address;
			return false;
        }
        else {
            $this->connection = $ftp_stream;
            @$ftp_stream = ftp_login($ftp_stream, $this->username, $this->password);
            if( ! @ $ftp_stream && $this->test) {
                $this->erros[] = 'Couldn\'t connect (Wrong username or password)';
            }
			return true;
        }
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
        /*
        * Switching to passive mode to prevent boolean return cause by server's firewall configuration
        */
        ftp_pasv($this->connection, true);
            
		$files = ftp_rawlist($this->connection, $this->path);
		//remove . and .. from 
		array_shift($files);
		array_shift($files);
		
		$list =  array();
		array_map(function($name) use (&$list, $type) {
			
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
						$list[$chunks[8]] = $item[$type];
					}
					else {
						$this->erros[] = 'invalid directories Key ' . $type;	
					}
				}
				else {
					$list[$chunks[8]] = $item;	
				}
			}
			
		}, $files);
		
		return $list;

    }
    
    
    public function listDirectries()
    {
        $files = $this->directories('dirs');


        uksort($files,  function ($a, $b) use ($files) {
            if ($files[$a]['permisions'][0] == '-') {
                return 1;
            }
            return -1;
        });
        
        return $files;
    }
}

