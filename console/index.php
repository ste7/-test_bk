<?php 
require_once(__DIR__ . '/../core/core.php');
/**
 * XMLConsole - class for CLI purpos
 *
 * any function tht is needed for CLI  side goes here
 *
 */ 
class XMLConsole extends Core {
    
    private $jobInfo;
    private $jobName;
    private $xmlReader;
    
    function __construct (){
        // uncomend line below if debug is required
        //$this->debugStatus = true;

        parent::__construct();
        $this->doJob();
    }
    
    /**
    * Function doJob call all necesary function in order to process job
    *
    */ 
    function doJob () {
        
        // first step take available job
        $this->selectFirstAvailableJobAndLock();
        
        //second step validate xml for job
        $this->validateXML();
        
        //third step process job
        $this->processJob();
    }
    
    /**
    * Function selectFirstAvailableJobAndLock takes first available job with status "pending" and process it
    *
    */ 
    function selectFirstAvailableJobAndLock() {
        // scan for all jobs in input
        $scanned_directory = array_diff(scandir($this->baseProtectedPath . $this->jobsPathInput), array('..', '.'));
        if(empty($scanned_directory)){
            $this->jsEncode(array(),false,'No jobs');
            die;
        }
        
        // take first job that has status pending nad update it
        foreach ($scanned_directory as $job_name){
            
            $jobContent = json_decode(file_get_contents($this->baseProtectedPath . $this->jobsPathInput . $job_name));
            
            if($jobContent->status ==='pending')
            {
                // update status of job
                $jobContent->status = 'working';
                $this->jobName = $job_name;
                $updated = json_encode($jobContent);
                $currentFile = fopen($this->baseProtectedPath . $this->jobsPathInput . $job_name, "w");
                fwrite($currentFile, $updated);
                fclose($currentFile);
                
                $this->logData('Job started: ' . $job_name);
                
                //remember job info globali
                $this->jobInfo = $jobContent;
                return;
            }
        }
        
        $this->jsEncode(array(),false,'No available jobs');
        die;
    }
    
    /**
    * Function validateXML takes xml and validates format of it
    *
    */ 
    function validateXML() {
        $xmlPath = $this->baseProtectedPath . $this->storagePath. $this->jobInfo->xmlIdentifier . '.xml';
        $xml = XMLReader::open($xmlPath);
        if($xml === false){
            $this->logData('Job: '. $this->jobName . ' status : XML file missing: ' . $this->jobInfo->xmlIdentifier . '.xml');
            $this->writeJobStatus('error','Missing data to process');
            die;
        }
        // The validate parser option must be enabled for 
        // this method to work properly
        $xml->setParserProperty(XMLReader::VALIDATE, true);
        if(!$xml->isValid()){
            $xml->close();
            $this->logData('Job: '. $this->jobName . ' status : XML invalid format: ' . $this->jobInfo->xmlIdentifier . '.xml');
            $this->writeJobStatus('error','invalid format');
        }
        $xml->close();
    }
    
    /**
    * Function processJob process curent job and do steps based on "jobCode"
    *
    */
    function processJob() {
        // prepair XML path of file that will be used
        $xmlPath = $this->baseProtectedPath . $this->storagePath. $this->jobInfo->xmlIdentifier . '.xml';
        
        //do jobs based on code
        switch ($this->jobInfo->jobCode) {
            case 1:
                $xml = XMLReader::open($xmlPath);
                $result=false;
                while ($xml->read())
                {
                    if($xml->nodeType == XMLReader::ELEMENT && $xml->name === $this->elementCode){
                       $result=true;
                       break;
                    }
                }
                $xml->close();
                $this->writeJobStatus('sucess',$result);
                break;
            case 2:
                $xml = XMLReader::open($xmlPath);
                $result = 0;
                
                while ($xml->read() && $result < 2)
                {

                    if($xml->nodeType == XMLReader::ELEMENT && $xml->name ===$this->elementCode){
                       $result++;
                    }
                }
                if($result > 1){
                    $result = true;
                }else{
                    $result = false;
                }
                $xml->close();
                $this->writeJobStatus('sucess',$result);
                break;
             case 3:
                $xml = XMLReader::open($xmlPath);
                $result=false;
                while ($xml->read())
                {
                    if($xml->nodeType == XMLReader::ELEMENT && $xml->name === $this->elementCode){
                       $result=$xml->readInnerXml();
                       break;
                    }
                }
                
                $xml->close();
                $this->writeJobStatus('sucess',$result);
                break;
             case 4:
                $xml = XMLReader::open($xmlPath);
                $result = array();
                
                while ($xml->read())
                {

                    if($xml->nodeType == XMLReader::ELEMENT && $xml->name ===$this->elementCode){
                       $result[] = $xml->readInnerXml();
                    }
                }
                $xml->close();
                $this->writeJobStatus('sucess',$result);
                break;
             case 5:
                if(unlink($xmlPath)){
                    $this->writeJobStatus('sucess',true);
                }else{
                    $this->writeJobStatus('error',false);
                }
                break;
            default:
               $this->writeJobStatus('error',false);
        }

        return;
    }
    
    /**
    * Function writeJobStatus moves job from input to output "pile" and updates status of job
    *
    */
    function writeJobStatus($status='error',$data=null) {
        
        $this->jobInfo->status = $status;
        
        if(!is_null($data)){
            $this->jobInfo->data = $data;
        }
        $updated = json_encode($this->jobInfo , JSON_UNESCAPED_UNICODE );
        $currentFile = fopen($this->baseProtectedPath . $this->jobsPathOutput . $this->jobName, "w");
        fwrite($currentFile, $updated);
        fclose($currentFile);
        unlink($this->baseProtectedPath . $this->jobsPathInput . $this->jobName);
        $this->logData('Job: '. $this->jobName . ' status : ' . $status);
        die;

    }


}

new XMLConsole;