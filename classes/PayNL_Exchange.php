<?php
require_once('Transactions.php');

final class PayNL_Exchange extends PayNL_Transactions
{
    protected $paymentSessionId;
    
    protected $exchangeEmail = array();
    public $getArray = array();
    public $paymentStatus = array();
    /**
     * Constructor for PayNL_Exchange handler
     * 
     * See documentation for options
     * 
     * @param array $_GET like array
     * @param integer $companyId
     * @param string $username
     * @param string $password
     * @param boolean $ipCheck
     * @param array $exchangeEmail
     * @param boolean $directMail
     * @return boolean
     */
    public function __construct(array $getArray,$companyId,$username,$password, $ipCheck = true, array $exchangeEmail = array(), $directMail = true)
    {
        $this->exchangeEmail = $exchangeEmail;
        
        try
        {
            $this->doIpCheck($_SERVER['REMOTE_ADDR']);
        }
        catch(Exception $ex)
        {
            if ($ipCheck)
            {
                echo "false|IP not approved for communication.";
                $this->doError('Exchange request received from unapproved IP, following data was provided: '.print_r($getArray,true));
                exit;
            }
        }

        parent::__construct($getArray['program_id'],$getArray['website_id'],$getArray['website_location_id'],$companyId,$username,$password);
        
        $this->getArray = $getArray;
        $this->object = $getArray['object'];
        
        
        if ($directMail)
        {
            // Verify payment
            $paymentStatus = $this->getPaymentStatus($getArray['payment_session_id']);
            $this->paymentStatus = $paymentStatus;
            $refund = $this->isCancelDelete($this->getArray['action']);
            
            if ($refund)
            {
                $this->doDebug($getArray['payment_session_id']." has received a refund");
                $this->doRefundMail();
                
                echo "true|refund OK";
                return true;
            }
            else
            {
                if ($paymentStatus['status'] == 'PAID' || $paymentStatus['status'] == 'PAID_CHECKAMOUNT')
                {
                    $this->doDebug($getArray['payment_session_id']." has paid for order: '".$getArray['object']."'");
                    $this->doExchangeMail();
                    
                    echo "true|Processed";
                    return true;
                }
                
                echo "true|Nothing done.";
                return true;
            }
            
            echo 'false|I do not understand this request';
            return true;
        }
        else
        {
            // Verify payment
            $paymentStatus = $this->getPaymentStatus($getArray['payment_session_id']);
            $this->paymentStatus = $paymentStatus;
            $refund = $this->isCancelDelete($this->getArray['action']);
            
            return array_merge(array('refund'=>$refund),$paymentStatus);
        }
    }
    /**
     * Returns shop order id (if set when creating transaction)
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->object;
    }
    /**
     * Send e-mail about refund to site owner.
     * Please note the e-mail address has to be set first.
     * 
     * @param String Custom message $message
     * @param String Custom subject $subject
     * @param String Custom single e-mailadress $extraTarget
     * @return boolean
     */
    public function doRefundMail($message="",$subject="New Pay.nl refund",$extraTarget="")
    {
        $default = "Dear sir/madam,\nA refund has been completed for #paymentSessionId#.\nThe order number for this was: #object#.\nAmount paid: #amount# EUR.\n\nKind regards,\nExchange script";
                
        if (strlen($message)>1)
        {
            $default = $message;
        }
        
        // Replace vars
        $this->replaceVars($message);
        
        if (strlen($extraTarget) > 0)
        {
            mail($extraTarget,$subject,$message);
        }
        else
        {
            foreach($this->exchangeEmail as $email)
            {
                mail($email,$subject,$message);
            }
        }
        return true;
    }
    /**
     * Send e-mail about exchange request to site owner.
     * Please note the e-mail address has to be set first.
     * 
     * @param String Custom message $message
     * @param String Custom subject $subject
     * @param String Custom single e-mailadress $extraTarget
     * @return boolean
     */
    public function doExchangeMail($message="",$subject="New Pay.nl action",$extraTarget="")
    {
        $default = "Dear sir/madam,\nA payment has been completed for #paymentSessionId#.\nThe order number for this was: #object#.\nAmount paid: #amount# EUR.\n\nKind regards,\nExchange script";
                
        if (strlen($message)>1)
        {
            $default = $message;
        }
        
        // Replace vars
        $message = $this->replaceVars($default);
        
        if (strlen($extraTarget) > 0)
        {
            mail($extraTarget,$subject,$message);
        }
        else
        {
            foreach($this->exchangeEmail as $email)
            {
                mail($email,$subject,$message);
            }
        }
        return true;
        
    }
    /**
     * Replace payment vars for e-mail
     * Allows you to simpily implement something to send e-mail messages.
     * 
     * @param String $message
     * @return String
     */
    private function replaceVars($message)
    {
        $arrData = array();
        $arrData = $this->forceCamelCase($this->paymentStatus);
        $arrData = array_merge($this->forceCamelCase($this->getArray),$arrData);
        $arrData['amount'] = number_format(($this->paymentStatus['amount']/100),2,',','.'); // Convert cents into whole amount
        
        foreach ($arrData as $key=>$val)
        {
            $arrData["#".$key."#"] = $val; // Append matching value
            unset($arrData[$key]);
        }
        
        $message = str_ireplace(array_keys($arrData),array_values($arrData),$message);
        
        return $message;
    }
    

    /**
     * Add e-mail address for exchange.
     * Only works when not calling directMail in constructor
     *
     * @param string $email
     */
    public function addEmail($email)
    {
        
        $new = true;
        foreach($this->exchangeEmail as $var)
        {
            if (strtoupper($var) == strtoupper($email))
            {
                $new = false;
            }
        }
        if ($new)
            $this->exchangeEmail[] = $email;
        
    }
    /**
     * Determines if exchange is a refund
     *
     * @param  $paymentStatus
     * @return unknown
     */
    private function isCancelDelete($action)
    {
        $arrActionDel = array();
        $arrActionDel[] = "delete";
        $arrActionDel[] = "incassodelete";
        $arrActionDel[] = "incassopredeclined";
        $arrActionDel[] = "incassostorno";
        if (in_array(strtolower($action),$arrActionDel))
        {
            return true;
        }
        return false;
    }
    /**
     * Checks if source IP of request is a valid Pay.nl IP.
     *
     * @param string $ip
     * @return boolean
     */
    private function doIpCheck($ip)
    {
        $allowedList = array();
        $allowedList['85.158.206.17'] = 1;
        $allowedList['85.158.206.18'] = 1;
        $allowedList['85.158.206.19'] = 1;
        $allowedList['85.158.206.20'] = 1;
        $allowedList['85.158.206.21'] = 1;
        if (!isset($allowedList[$ip]))
        {
            return false;
        }
        return true;
    }
    /**
     * Transformer for exchange to camel case.
     *
     * @param string $strString
     * @return string
     */
    private function makeCamelCase($strString)
    {
        $strCamelCase = "";
        $arrString = explode('_', $strString);
        $iElements = count($arrString);
        
        for($i = 0; $i < $iElements; $i++)
        {
            $strCamelCase.= $i == 0 ? $arrString[$i] : ucfirst($arrString[$i]);
        }
        
        return $strCamelCase;
    }
    /**
     * Force camelCase system
     *
     * @param array $arrInput
     * @param array $arrReplace
     * @return array
     */
    private function forceCamelCase(array $arrInput, array $arrReplace = array())
    {
        $arrCamelCase = array();
        
        foreach($arrInput as $strKey => $value)
        {
            if(is_array($value))
            {
                $value = $this->forceCamelCase($value, $arrReplace);
            }
            
            if(array_key_exists($strKey, $arrReplace))
            {
                $strKey = $arrReplace[$strKey];
            }
            else
            {
                $strKey = $this->makeCamelCase($strKey);
            }
            
            $arrCamelCase[$strKey] = $value;
        }
        
        return $arrCamelCase;
    }    
    
}
