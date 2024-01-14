<?php

defined( 'ABSPATH' ) || exit;

class Play_Stream {

    private $path = "";
    private $stream = "";
    private $buffer = 10240; // 10k
    private $start  = -1;
    private $end    = -1;
    private $size   = 0;
    private $preview = false;

    public function __construct($filePath, $preview = false) {
        $this->preview = $preview;
        $this->path = $filePath;
    }
    
    private function open() {
        if (!($this->stream = fopen($this->path, 'rb', false, stream_context_create()))) {
            die('Could not open stream for reading');
        }
    }
    
    private function setHeader() {
        ob_get_clean();
        header("Content-Type: ". mime_content_type($this->path));
        header("Cache-Control: max-age=2592000, public");
        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
        $this->start = 0;
        $this->size  = filesize($this->path);
        $this->end   = $this->size - 1;

        // Stream preview
        if($this->preview){
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            $data = wp_read_audio_metadata($this->path);
            if( $this->preview < $data['length'] ){
                $this->size = intval( intval($this->preview) / $data['length'] * $this->size );
                $this->end = $this->size - 1;
                header("Content-Length: " . $this->size);
                //return;
            }
        }

        header("Accept-Ranges: 0-" . $this->end);
        
        if (isset($_SERVER['HTTP_RANGE'])) {
  
            $c_start = $this->start;
            $c_end = $this->end;
 
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            if ($range == '-') {
                $c_start = $this->size - substr($range, 1);
            }else{
                $range = explode('-', $range);
                $c_start = $range[0];
                 
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }
            $c_end = ($c_end > $this->end) ? $this->end : $c_end;
            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/".$this->size);
        }
        else
        {
            header("Content-Length: ".$this->size);
        }
    }
    
    private function end() {
        fclose($this->stream);
        exit;
    }
     
    private function stream() {
        $i = $this->start;
        set_time_limit(0);
        while(!feof($this->stream) && $i <= $this->end && connection_aborted() == 0) {
            $bytesToRead = $this->buffer;
            if(($i+$bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = stream_get_contents($this->stream, $bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }
    
    public function start() {
        session_write_close();
        $this->open();
        $this->setHeader();
        $this->stream();
        $this->end();
    }
}
