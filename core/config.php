<?php
/**
 * Config - config class contain all necessary info for proper funcionality
 *
 *
 */ 
class Config {
    
    //debug option on/off
    public $debugStatus = false;
    // protected dir that stores all XML and JOBS
    public $baseProtectedPath = __DIR__ . '/../protected/';
    // dir that contains all logs
    public $logsPath = __DIR__ . '/../logs/';
    // name of log file hat will have prefix of curent day ex:  2018_11_18_log.txt
    public $logFile = 'log.txt';
    // protected dir that stores all XML
    public $storagePath = 'storage/';
    // protected dir that stores all input JOBS that need to be proccessed
    public $jobsPathInput = 'jobs/input/';
    // protected dir that stores all output JOBS that has been proccessed
    public $jobsPathOutput = 'jobs/output/';
    // element that is being looked for in XML files
    public $elementCode = 'ZZ_JD_MATLIEFNRNUM';
    /* list of job codes that are awailable:
    * 1 - Query the API with the unique ID to get the result of processing the XML, if there is a SEGMENT called "ZZ_JD_MATLIEFNRNUM": true, false 
    * 2 - Query the API with the unique ID to get the result of processing the XML, if there are multiple SEGMENTs called "ZZ_JD_MATLIEFNRNUM": true, false 
    * 3 - Query the API with the unique ID to get the result of processing the XML, get the value of the segment "ZZ_JD_MATLIEFNRNUM": string 
    * 4 - Query the API with the unique ID to get the result of processing the XML, get the values (when multiple) of the segment "ZZ_JD_MATLIEFNRNUM": (array) strings 
    * 5 - Query the API with the unique ID to delete the XML from the stack completely
    
    */
    public $jobCodes = array(1,2,3,4,5);
    
    // login credentials for CMS
    public $username = 'admin';
    public $password = 'admin';
    
    public function __construct (){
        $this->showDebugInfo();
	}
    
    /**
    * Function showDebugInfo sets error reporting based on global variable $this->debugStatus
    */ 
    public function showDebugInfo(){
        if($this->debugStatus === true){
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }else{
            error_reporting(0);
        }
    }
    
     /**
    * Function debug global debug function that helps with formating
    * @param data data that need to be displayed
    * @param exit check if terminating is required
    * @param showBackTrace show backtrace info
    */ 
    public function debug($data=false,$exit = false,$showBackTrace=true){
        $this->debugStatus = true;
        $this->showDebugInfo();
        if($showBackTrace){
            $db = debug_backtrace();
            $caller = array_shift($db);
            echo $caller['file'] . ' - ' . $caller['line']. ': ';
        }
        echo '<pre>' . var_export($data, true) . '</pre>';
        if($exit){
            die();
        }
    }
    
}

?>
