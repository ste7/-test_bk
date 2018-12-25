<?php 

require_once(__DIR__ . '/../core/config.php');

/**
 * Core - core class that contain shared funcionalitie
 *
 *
 */ 
class Core extends Config {
    
    function __construct (){
        parent::__construct();
        $this->testApplication();
    }
     /**
    * Function testApplication goes thrue all necesery documets that need to be tested
    */ 
    function testApplication(){
        $this->testAndModifiDir($this->baseProtectedPath);
        $this->testAndModifiDir($this->baseProtectedPath . $this->storagePath);
        $this->testAndModifiDir($this->baseProtectedPath . $this->jobsPathInput);
        $this->testAndModifiDir($this->baseProtectedPath . $this->jobsPathOutput);
        $this->testAndModifiDir($this->logsPath);
    }
    
    /**
    * Function testAndModifiDir test if all nedded foledrs are presend and if they have apropriate permissions
    *
    * @param path path that need to be checked 
    * 
    */ 
    private function testAndModifiDir($path){
        if (!is_dir($path)){
            mkdir($path, 0755, true);
        }else if(!is_writable($path) || !is_readable($path)){
            chmod($path, 0755);
        }
    }
    
    /**
    * Function jsEncode encodes data recived for JSON input format
    *
    * @param value data that need to be presented
    * @param error status of responce
    * @param message custom message fro responce
    * @param exit option to terminate
    */ 
    function jsEncode ($value = array(),$error = false, $message= '', $exit = false) {
        $results = array(
            'success'=> $error,
            'data'=> $value,
            'message'=> $message
        );
      echo json_encode($results, JSON_UNESCAPED_UNICODE);
      if($exit === false){
        die();
      }
   }
   
    /**
    * Function logData logs given parameters in log file for each day
    *
    * @param logData parameter that will be saved in log
    * future development may have differen logs for api and CLI, error or status
    */ 
    function logData($logData = ''){
        $currentFile = fopen($this->logsPath . date("Y_m_d") . '_' .$this->logFile, "a");

        $db = debug_backtrace();
        $caller = array_shift($db);
        $updated = $caller['file'] . ' - Line:' . $caller['line']. ' = ' . $logData . PHP_EOL;
        
        fwrite($currentFile, $updated);
        fclose($currentFile);
        return;
    }
}


