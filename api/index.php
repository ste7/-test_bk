<?php

require_once('../core/core.php');

/**
 * XMLApi - class for api purpos
 *
 * any function tht is needed for API or communication with client side goes here
 *
 */ 

class XMLApi extends Core {
    
    function __construct (){
        // uncomend line below if debug is required
        //$this->debugStatus = true;
        
       parent::__construct();
       // call function based on method and function name
       $method = strtolower($_SERVER["REQUEST_METHOD"]);
       $direction = explode("/", ($_SERVER["REQUEST_URI"]))[2];
       $function = $method . '_' . $direction;
       $this->$function();
    }
    
    
    /**
    * Function post_ recived raw xml string data or file with xml code
    *
    * @param $_POST['XMLContent'] raw xml string data
    * @param $_FILES['XMLFile'] file with xml code
    * 
    * @return JSON encoded format if success or error
    * future developemnt may include validation of file format, file size, if users i permited to send file, limit number of files being send  for given time, ip, account...
    */ 
    function post_reciveXML () {
        // validate if input exist and it is not empty
        if((!isset($_POST['XMLContent']) || empty($_POST['XMLContent'])) && (!isset($_FILES['XMLFile']) || empty($_FILES['XMLFile'])) ) {
            $this->jsEncode(array(),false,'XML content missing');
            return;
        }
        
        $name = 'xml_' .microtime(true) * 10000;
        $path = $this->baseProtectedPath . $this->storagePath . $name . ".xml";
        
        // if by any chance file all ready exist look for new name
        while(file_exists($path)){
            $name = 'xml_' .microtime(true) * 10000;
            $path = $this->baseProtectedPath . $this->storagePath . $name . ".xml";
        }
        
        if(isset($_FILES['XMLFile']) && !empty($_FILES['XMLFile'])){
            // file uploaded option
            if (!move_uploaded_file($_FILES['XMLFile']['tmp_name'], $path)) {
                //log and return status
                $this->logData('XML file NOT saved: ' . $name);
                $this->jsEncode(array(),false,'Error while processing file');
                 return;
            }
            
        }else{
            // raw xml data option
            $input = $_POST['XMLContent'];
            $currentFile = fopen($path, "w");
            fwrite($currentFile, $input);
            fclose($currentFile);
            
        } 
        
        //log and return status
        $this->logData('XML file saved: ' . $name);
        $this->jsEncode('XML identifier: ' . $name,true,'',true);
    }
    /**
    * Function post_query_api recived unique XMLIdentifier for xml file that will be put in processing stack and specific jobCode
    *
    * @param $_POST['XMLIdentifier'] unique XMLIdentifier that was returned by "post_reciveXML" function
    * @param $_FILES['jobCode'] specific jobCode that need to match jobs from config
    * 
    * @return JSON encoded format if success or error
    * future developemnt may include validation if users i permited to que jobe, limit number of jobs
    */ 
    function post_query_api () {
        // validate if input exist and it is not empty
        if(!isset($_POST['XMLIdentifier']) || empty($_POST['XMLIdentifier']) || !isset($_POST['jobCode']) || empty($_POST['jobCode']) || !in_array($_POST['jobCode'], $this->jobCodes)) {
            $this->jsEncode(array(),false,'Wrong data');
            return;
        }
        
        //prepare data for job
        $input = json_encode(array(
                                                'xmlIdentifier'=> $_POST['XMLIdentifier'],
                                                'jobCode'=> $_POST['jobCode'],
                                                'status'=>'pending'
                                                ), JSON_UNESCAPED_UNICODE);
        
        $jobIdentifier = 'job_'.microtime(true) * 10000;
        $path = $this->baseProtectedPath . $this->jobsPathInput . $jobIdentifier . ".json";
        
        // if by any chance file all ready exist look for new name
         while(file_exists($path)){
            $jobIdentifier = 'job_'.microtime(true) * 10000;
            $path = $this->baseProtectedPath . $this->jobsPathInput . $jobIdentifier . ".json";
        }
        
        $currentFile = fopen($path, "w");
        fwrite($currentFile, $input);
        fclose($currentFile);
        $this->jsEncode('Query identifier: ' . $jobIdentifier,true,'',true);
    }
    
    /**
    * Function post_return_query_result retrives job status based on unique identifier for given job QueryJobIdentifier
    *
    * @param $_POST['QueryJobIdentifier'] unique job identifier that was returned by "post_query_api" function
    * 
    * @return JSON encoded format if success or error
    * future developemnt may include validation if users i permited to que jobe, limit number of checkes in given time frame
    */ 
    function post_return_query_result () {
        // validate if input exist and it is not empty
        if(!isset($_POST['QueryJobIdentifier']) || empty($_POST['QueryJobIdentifier'])) {
            $this->jsEncode(array(),false,'Wrong data');
            return;
        }
        
        $queryJobIdentifier = $_POST['QueryJobIdentifier'];
        $pathInput = $this->baseProtectedPath . $this->jobsPathInput . $queryJobIdentifier . ".json";
        $pathOutput = $this->baseProtectedPath . $this->jobsPathOutput . $queryJobIdentifier . ".json";
        // check in oputput or input que
        if(!file_exists($pathInput) && !file_exists($pathOutput)){
            $this->jsEncode('Error job lost',true);
        }else if(file_exists($pathOutput) ){
            $output = json_decode(file_get_contents($pathOutput));
            $this->jsEncode($output->data,true,$output->status);
        }else{
            $output = json_decode(file_get_contents($pathInput));
            $this->jsEncode($output->data,true,$output->status);
        }
        die;
    }
    
    /**
    * Function post_login simulate loging in for users based on input parameters and saved parameters in config
    *
    * @param $_POST['username'] parameter for users username
    * @param $_POST['password'] parameter for users password
    * 
    * @return JSON encoded format if success or error
    */ 
    function post_login () {
        if((!isset($_POST['username']) || empty($_POST['username'])) && (!isset($_POST['password']) || empty($_POST['password'])) ) {
            $this->jsEncode(array(),false,'login data missing');
            return;
        }
        if($_POST['username'] === $this->username && $_POST['password'] === $this->password){
            $this->jsEncode(array('status'=>'User found','member'=>sha1(md5(openssl_random_pseudo_bytes(8)))),true,'User found',true);
        }else{
            $this->jsEncode(array(),false,'Invalid login credentials',true);
        }
        
    }
    
    /**
    * Function get_history retrives job status and info about jobs in order to be shown in CMS
    * 
    * @return JSON encoded format if success or error
    * future developemnt may include validation if users is loged in, if permited to call this api, filter, pagination, grouping (input, output), and returning only parameters fields
    */
    function get_history () {
        //list input and output jobs
        $scanned_directory_input = array_diff(scandir($this->baseProtectedPath . $this->jobsPathInput), array('..', '.'));
        $scanned_directory_output = array_diff(scandir($this->baseProtectedPath . $this->jobsPathOutput), array('..', '.'));
        
        // check if no data
        if(empty($scanned_directory_input) && empty($scanned_directory_output)){
            $this->jsEncode(array(),false,'No jobs');
            die;
        }
        // format jobs to required output
        $history = array();
        foreach ($scanned_directory_input as $jobIdentifier){
            $jobPath = $this->baseProtectedPath . $this->jobsPathInput . $jobIdentifier;
            $jobContent = json_decode(file_get_contents($jobPath));
            $jobContent->jobIdentifier = $jobIdentifier;
            $jobContent->jobDate = date("F d Y H:i:s.",filemtime($jobPath));
            $jobContent->jobPath = $jobPath;
            $jobContent->jobGroup = 'input';
            $history[] = $jobContent;
        }
        
        foreach ($scanned_directory_output as $jobIdentifier){
            $jobPath = $this->baseProtectedPath . $this->jobsPathOutput . $jobIdentifier;
            $jobContent = json_decode(file_get_contents($jobPath));
            $jobContent->jobIdentifier = $jobIdentifier;
            $jobContent->jobDate = date("F d Y H:i:s.",filemtime($jobPath));
            $jobContent->jobPath = $jobPath;
            $jobContent->jobGroup = 'output';
            $history[] = $jobContent;
        }
        
        $this->jsEncode($history,true,'',true);
    }

}

new XMLApi;

?>
