<?php
class PayNL_Transactions
{
    private $handshake = '';
    
    protected $programId;
    protected $websiteId;
    protected $websiteLocationId;
    
    private $classVersion = '1.0.0';
    private $apiBaseCommMethod = 'https://';
    private $apiBaseURL = 'rest-api.pay.nl/';
    
    
    protected $useCurl = false;
    private $curl = null; // cURL handle (if used)
    
    protected $debug = false;
    private $emailErrors=array();
    
    
    // Payment vars
    private $bankId;
    private $object;
    private $returnUrl;
    private $exchangeUrl = "";
    private $testmode = false;
    
    
    /**
     * Create payment class instance
     * Will throw an exception on error.
     *
     * @param Integer $programId
     * @param Integer $websiteId
     * @param Integer $websiteLocationId
     * @param Integer $companyId
     * @param String $username
     * @param String $password
     * @return Boolean / Exception
     */
    public function __construct($programId,$websiteId,$websiteLocationId,$companyId=0,$username="",$password="",$debug=false)
    {
        // Save the input (make sure integers are integers)
        $this->programId = intval($programId);
        $this->websiteId = intval($websiteId);
        $this->websiteLocationId = intval($websiteLocationId);
        $this->debug = $debug;
        
        // Check for libcURL
        if(function_exists('curl_init'))
        {
            $this->doDebug("Using CURL for requests");
            $this->useCurl = true;
        }

        // Login to pay.nl if requested
        if ($companyId > 0 || strlen($username) > 0 || strlen($password) > 0)
        {
            $this->login($companyId,$username,$password);
        }
        
        
        return true;
    }
    
    /**
     * Do cleanup work.
     * (And logoff from Pay.nl)
     */
    public function __destruct()
    {
        if (strlen($this->handshake) > 0)
        {
            $this->_logoff();
        }
        
        if (!is_null($this->ch))
        {
            curl_close($this->ch);
        }
    }
    /**
     * Login to Pay.nl
     * Needed for API calls
     *
     * @param integer $companyId
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function login($companyId,$username,$password)
    {
        $arrArguments = array();
        $arrArguments['companyId'] = $companyId;
        $arrArguments['username'] = $username;
        $arrArguments['password'] = $password;
        
        $result = $this->_doRequest('Authentication/login',$arrArguments,5,'v2');
        $result = @unserialize($result);
        if (!is_array($result) || !isset($result['result']))
        {
            $this->doError('Unexpected response from Pay.nl @ Authentication/login');
            throw new Exception('Unexpected response from Pay.nl @ Authentication/login');
        }
        
        $this->doDebug('Retrieved handshake: '.$result['result']);
        $this->handshake = $result['result'];
        
        return true;
    }
    /**
     * Gets current list of iDeal banks
     *
     * @return array
     */
    public function getIdealBanks()
    {
        $result = $this->_doRequest('Transaction/getBanks');
        $result = @unserialize($result);
        
        if (!is_array($result))
        {
            throw new Exception('Unexpected response from Pay.nl @ Authentication/login');
        }
        
        $this->doDebug('Retrieved banklist: '.print_r($result,true));
        
        return $result;
    }
    
    /**
     * Set the bankId (required for iDEAL / Giropay)
     *
     * @param integer $id
     */
    public function setBankId($id)
    {
        $this->bankId = $id;
    }
    /**
     * Set the (webshop) order code
     *
     * @param string $orderCode
     */
    public function setExternalOrder($orderCode)
    {
        if (strlen($orderCode) > 25)
        {
            throw new Exception('orderCode too large');
        }
        $this->object = strip_tags($orderCode);
    }
    /**
     * Set the Return URL where to send the
     * user when getting back to this site.
     *
     * @param string $url
     */
    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;
    }
    /**
     * Set to add an additional Exchange URL
     * besides the one already listed.
     *
     * @param string $url
     */
    public function setExchangeUrl($url)
    {
        $this->exchangeUrl = $url;
    }
    /**
     * Enable testmode (only needed if the site is already live)
     */
    public function enableTestMode()
    {
        $this->testmode = true;
    }
    /**
     * Retrieve list of allowed/enabled payment profiles for
     * this program/website/location.
     *
     * @return array
     */
    public function getActivePaymentProfiles()
    {
        $arrSettings = array();
        $arrSettings['programId'] = $this->programId;
        $arrSettings['websiteId'] = $this->websiteId;
        $arrSettings['websiteLocationId'] = $this->websiteLocationId;
        $arrSettings['paymentMethodId'] = 4; // Only retrieve Pay per transaction payment profiles
        
        $result = $this->_doRequest('WebsiteLocation/getActivePaymentProfiles',$arrSettings);
        $result = @unserialize($result);
        
        return $result;
    }
    /**
     * Create new transaction
     *
     * @param integer $amount (in cents)
     * @param integer $paymentProfileId
     * @param array $settings (optional settings)
     * @return array
     */
    public function createTransaction($amount, $paymentProfileId, array $settings = array())
    {
        if (strlen($this->returnUrl) <= 5)
        {
            throw new Exception('Return URL not set');
        }
        
        if ($amount < 0)
        {
            throw new Exception('Unable to create transaction for amount smaller than 0 cents');
        }
        
        // determine ipAddress
        $ipAddress = '127.0.0.1';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 0)
        {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        
        $arrSettings = $settings;
        $arrSettings['amount'] = $amount;
        $arrSettings['programId'] = $this->programId;
        $arrSettings['websiteId'] = $this->websiteId;
        $arrSettings['websiteLocationId'] = $this->websiteLocationId;
        $arrSettings['paymentProfileId'] = intval($paymentProfileId);
        $arrSettings['ipAddress'] = $ipAddress;
        $arrSettings['orderReturnUrl'] = $this->returnUrl;
        if ($this->testmode == true)
        {
            $arrSettings['testMode'] = 1;
        }
        
        if (!is_null($this->bankId))
        {
            $arrSettings['bankId'] = $this->bankId;
        }
        
        if (!is_null($this->object))
        {
            $arrSettings['object'] = $this->object;
            if (!isset($arrSettings['orderDesc']))
            {
                $arrSettings['orderDesc'] = 'Order '.$this->object;
            }
        }
        
        if (!is_null($this->exchangeUrl) && strlen($this->exchangeUrl) > 5)
        {
            $arrSettings['orderExchangeUrl'] = $this->exchangeUrl;
        }
        
        $result = $this->_doRequest('Transaction/create',$arrSettings);
        
        if($this->debug == true)
        {
        	var_dump($arrSettings);
        	var_dump($result);
        	
        }
        
        $result = @unserialize($result);
       // mail('luuk@sebsoft.nl','RESULT',print_r($result,true));
        if (!is_array($result) || !isset($result['result']))
        {
            $this->doError('Unexpected response from Pay.nl @ Transaction/create');
            throw new Exception('Unexpected response from Pay.nl @ Transaction/create');
        }
        
        if ($result['result'] == 'FALSE')
        {
            throw new Exception('Unable to create new session due to error');
        }
        
        unset($result['entranceCode']);
        unset($result['result']);

        return $result;
    }
    /**
     * Get status of payment with paymentSessionId xxxxx.
     *
     * @param integer $paymentSessionId
     * @return array
     */
    public function getPaymentStatus($paymentSessionId)
    {
        $arrArguments = array();
        $arrArguments['paymentSessionId'] = $paymentSessionId;
        
        $result = $this->_doRequest('Transaction/getStatusByPaymentSessionId',$arrArguments);
        $result = @unserialize($result);
        if (!is_array($result) || !isset($result['result']))
        {
            $this->doError('Unexpected response from Pay.nl @ Transaction/create');
            throw new Exception('Unexpected response from Pay.nl @ Transaction/create');
        }
        
        $arrResult = array();
        $arrResult['status'] = $result['statusAction'];
        $arrResult['amount'] = $result['amount'];
        $arrResult['statsAdded'] = $result['statsAdded'];
        if (isset($result['customer']))
        {
            $arrResult = array_merge($arrResult,$result['customer']);
        }
        
        return $arrResult;
    }
    
    /**
     * Method for doing requests by eighter cURL or file_get_contents
     *
     * @param string $functionName
     * @param array $arguments
     * @param integer $retryCount
     * @param string $version
     * @return string
     */
    final private function _doRequest($functionName,array $arguments = array(),$retryCount=5,$version='v1')
    {
        if ($retryCount < 0)
        {
            return false;
        }
        $retryCount--; // Every request can be tried for 5 times (unless specific errors)
        
        
        if ($this->useCurl)
        {
            // Construct URL
            $strUrl = $this->apiBaseCommMethod.$this->apiBaseURL.$version.'/'.$functionName.'/array_serialize/';
            $strUrl = $this->prepareHttpGet($strUrl, $arguments);
            //var_dump($strUrl); // Enable this to see what api calls are done by this system
            $this->doDebug($strUrl);

            if ($this->curl == null)
            {
                $this->curl = curl_init();
                
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->curl, CURLOPT_USERAGENT, 'PPT Class version '.$this->classVersion. ' (for program '.$this->programId.')');
                curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            }
            
            // Set the handshake for all requests, at least... when it's known.
            if (strlen($this->handshake) > 0)
                curl_setopt($this->curl, CURLOPT_USERPWD,'pptCHandshake'.":".$this->handshake);
            
            curl_setopt($this->curl, CURLOPT_URL,$strUrl);
            
            $data = curl_exec($this->curl);
            $getinfo = curl_getinfo($this->curl);
            $error_nr = curl_errno($this->curl);
            if ($error_nr > 0)
            {
                // Error occured.
                return $this->_doRequest($functionName,$arguments,$retryCount,$version);
            }
            if ($getinfo['http_code'] > 400)
            {
                $error = 'Unknown communications error';
                $uData = @unserialize($data);
                if (is_array($uData) && count($uData) > 0)
                {
                    if (isset($uData['error']))
                    {
                        $error = $uData['error'];
                    }
                    else
                    {
                        $error.= ' - Unable to retrieve error';
                    }
                }
                
                $this->doError($error);
                throw new Exception('Error: '.$error,$getinfo['http_code']);
            }
            
            return $data;
        }
        else // If we don't do cURL, just try file_get_contents
        {
            // Construct URL
            if (strlen($this->handshake) > 0)
            {
                $strUrl = $this->apiBaseCommMethod."pptHandshake:".$this->handshake."@".$this->apiBaseURL.$version.'/'.$functionName.'/array_serialize/';
            }
            else
            {
                $strUrl = $this->apiBaseCommMethod.$this->apiBaseURL.$version.'/'.$functionName.'/array_serialize/';
            }
            
            $strUrl = $this->prepareHttpGet($strUrl, $arguments);
            //var_dump($strUrl); // Enable this to see what api calls are done by this system
            $this->doDebug($strUrl);

            $context = stream_context_create(
                array(
                    'http' => array(
                        'timeout' => 10      // Timeout in seconds
                    )
                ));
            $data = file_get_contents($strUrl,0,$context);
            if ($data === false)
            {
                return $this->_doRequest($functionName,$arguments,$retryCount,$version);
            }
            
            return $data;
        }
        
        
        return false; // This should not be reacheable
    }
    /**
     * Function to modify array of params into REST API parameters.
     *
     * @param string $strUrl
     * @param array $arrParams
     * @return string
     */
    final private function prepareHttpGet($strUrl, array $arrParams)
    {
        $first = 1;
        
        // Prepare query string
        foreach ($arrParams as $key => $value)
        {
            if ($first != 1)
            {
                $strUrl = $strUrl . "&";
            }
            else
            {
                $strUrl = $strUrl . "?";
                $first = 0;
            }
            
            if(is_array($value))
            {
                $count = count($value);
                foreach ($value as $k => $v)
                {
                    $count--;
                    $strUrl = $strUrl . $key."[".$k ."]=" . urlencode($v);
                    if ($count > 0) $strUrl.="&";
                }
                continue;
            }
        
            // Add item to string
            $strUrl = $strUrl . $key ."=" . urlencode($value);
        }
        
        return $strUrl;
    }
    /**
     * Logoff from Pay.nl API
     *
     */
    private function _logoff()
    {
        // We don't care about the output
        try
        {
            $this->_doRequest('Authentication/logout',array(),5,'v2');
        }
        catch (Exception $ex)
        {
        }
    }
    /**
     * Add e-mail address for error handler
     *
     * @param string $emailAddress
     */
    public function addMailErrors($emailAddress)
    {
        $new = true;
        foreach($this->emailErrors as $val)
        {
            if (strtoupper($val) == strtoupper($emailAddress))
            {
                $new = false;
                break;
            }
        }
        
        if ($new)
        {
            $this->emailErrors[] = $emailAddress;
        }
    }
    /**
     * Critical error handler
     *
     * @param string $string
     */
    public function doError($string)
    {
        error_log("Pay.nl [critical error]: ".$string);
        foreach($this->emailErrors as $mail)
        {
            mail($mail,'Pay.nl Critical error',$string."\n\nOccured at:\n".$this->_generateBacktrace());
        }
    }
    /**
     * Debug message
     *
     * @param string $string
     * @param boolean $severe
     */
    public function doDebug($string,$severe = false)
    {
        if ($this->debug)
        {
            error_log("Pay.nl: ".$string);
            if ($severe == true)
            {
                echo "Pay.nl: <pre>".$string."</pre>";
            }
        }
    }
    /**
     * Generate backtrace while skipping this function in the backtrace.
     *
     * @return string
     */
    final private function _generateBacktrace()
    {
        $rawtrace = debug_backtrace();
        array_shift($rawtrace);
        
        $output="";
        foreach($rawtrace as $entry)
        {
            if (isset($entry['file']))
            {
                $output.="\nFile: ".$entry['file']." (Line: ".$entry['line'].")\n";
            }
            else
            {
                $output.="\nClass: ".$entry['class']."\n";
            }
            $output.="Function: ".$entry['function']."\n";
        }
        return $output;
    }
   
}
