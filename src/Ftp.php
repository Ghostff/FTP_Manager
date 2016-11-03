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
    
    
    private function connect()
    {
        @$ftp_stream = ftp_connect($this->host, $this->port, $this->timeout);
        if ( ! $ftp_stream && $this->test) {
            $this->erros[] = 'Couldn\'t connect as ' . $ftp_address;
        }
        else {
            
            $this->connection = $ftp_stream;
            @$ftp_stream = ftp_login($ftp_stream, $this->username, $this->password);
            if( ! @ $ftp_stream && $this->test) {
                $this->erros[] = 'Couldn\'t connect (Wrong username or password)';
            }
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
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
            else {
                $this->erros[] = 'invalid property ' . $name;
            }
        }
        
        $this->connect();
    }
    
    public function __get($name)
    {
        if (strcasecmp($name, 'sysType') == 0) {
            return ftp_systype($this->connection);
        }
    }
    
    public function directories($type)
    {
        /*
        * Switching to passive mode to prevent boolean return cause by server's firewall configuration
        */
        ftp_pasv($this->connection, true);
        if ( strcasecmp($type, 'dirs') == 0) {
            
            $files = ftp_rawlist($this->connection, $this->path);
            //remove . and .. from 
            array_shift($files);
            array_shift($files);
            
            $list =  array();
            array_map(function($name) use (&$list) {
                
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
    
                    $list[$chunks[8]] = $item;
                }
                
            }, $files);

            return $list;
        }
        elseif (strcasecmp($type, 'raw') == 0) {
            return ftp_rawlist($this->connection, $this->path);
        }
        else {
            return ftp_nlist($this->connection, $this->path);
        }
    }
    
    
    public function listDirectries()
    {
        $dir = null;
        $files = $this->directories('dirs');


        uksort($files,  function ($a, $b) use ($files) {
            if ($files[$a]['permisions'][0] == '-') {
                return 1;
            }
            return -1;
        });
        
        

        foreach ($files as $names => $attr) {
            
            $icon = '';
            if ($attr['permisions'][0] == 'd') {
                $icon = '<span class="dir_icon">&#128194;</span>';
            }
            elseif ($attr['permisions'][0] == '-') {
                $icon = '<span class="file_icon"></span>';
            }
            $dir .= '<div class="dir">' . $icon .  $names . '</div>';
        }
        return $dir;
    }
}

