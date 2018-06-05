<?php

class RatePAY_ProduktRatenrechner_Model_Request extends Mage_Core_Model_Abstract
{

    /**
     * Xml response instance
     * 
     * @var SimpleXMLElement
     */
    private $response = null;
    
    /**
     * Xml request instance
     * 
     * @var SimpleXMLElement
     */
    private $request = null;
    
    /**
     * Error string
     * 
     * @var string
     */
    private $error = '';

    /**
     * Returns the Request
     *
     * @return SimpleXML
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Validates the Response
     *
     * @param string $requestType
     * @return boolean|array
     */
    public function validateResponse($requestType = '')
    {
        $statusCode = '';
        $resultCode = '';
        if($this->response != null) {
            $statusCode = (string) $this->response->head->processing->status->attributes()->code;
            $resultCode = (string) $this->response->head->processing->result->attributes()->code;
            $reasonCode = (string) $this->response->head->processing->reason->attributes()->code;
        }
        switch($requestType) {
            case 'CONFIGURATION_REQUEST':
                if($statusCode == "OK" && $resultCode == "500") {
                    $result = array();
                    $result['interestrateMin'] = (string) $this->response->content->{'installment-configuration-result'}->{'interestrate-min'};
                    $result['interestrateDefault'] = (string) $this->response->content->{'installment-configuration-result'}->{'interestrate-default'};
                    $result['interestrateMax'] = (string) $this->response->content->{'installment-configuration-result'}->{'interestrate-max'};
                    $result['monthNumberMin'] = (string) $this->response->content->{'installment-configuration-result'}->{'month-number-min'};
                    $result['monthNumberMax'] = (string) $this->response->content->{'installment-configuration-result'}->{'month-number-max'};
                    $result['monthLongrun'] = (string) $this->response->content->{'installment-configuration-result'}->{'month-longrun'};
                    $result['monthAllowed'] = (string) $this->response->content->{'installment-configuration-result'}->{'month-allowed'};
                    $result['paymentFirstday'] = (string) $this->response->content->{'installment-configuration-result'}->{'payment-firstday'};
                    $result['paymentAmount'] = (string) $this->response->content->{'installment-configuration-result'}->{'payment-amount'};
                    $result['paymentLastrate'] = (string) $this->response->content->{'installment-configuration-result'}->{'payment-lastrate'};
                    $result['rateMinNormal'] = (string) $this->response->content->{'installment-configuration-result'}->{'rate-min-normal'};
                    $result['rateMinLongrun'] = (string) $this->response->content->{'installment-configuration-result'}->{'rate-min-longrun'};
                    $result['serviceCharge'] = (string) $this->response->content->{'installment-configuration-result'}->{'service-charge'};
                    $this->error = '';
                    return $result;
                } else {
                    $this->error = 'FAIL';
                    return false;
                }
                break;
            case 'PROFILE_REQUEST':
                if($statusCode == "OK" && $resultCode == "500") {
                    $resultMasterData = (array) $this->response->content->{'master-data'};
                    $resultInstallmentConfiguration = (array) $this->response->content->{'installment-configuration-result'};
                    $result['merchant_config'] = $resultMasterData;
                    $result['installment_config'] = $resultInstallmentConfiguration;
                    $this->error = '';
                    return $result;
                } else {
                    $this->error = 'FAIL';
                    return false;
                }
                break;
            case 'CALCULATION_REQUEST':
                $successCodes = array('603', '671', '688', '689', '695', '696', '697', '698', '699');
                if($statusCode == "OK" && in_array($reasonCode, $successCodes) && $resultCode == "502") {
                    $result = array();
                    $result['totalAmount'] = (string) $this->response->content->{'installment-calculation-result'}->{'total-amount'};
                    $result['amount'] = (string) $this->response->content->{'installment-calculation-result'}->{'amount'};
                    $result['interestRate'] = (string) $this->response->content->{'installment-calculation-result'}->{'interest-rate'};
                    $result['interestAmount'] = (string) $this->response->content->{'installment-calculation-result'}->{'interest-amount'};
                    $result['serviceCharge'] = (string) $this->response->content->{'installment-calculation-result'}->{'service-charge'};
                    $result['annualPercentageRate'] = (string) $this->response->content->{'installment-calculation-result'}->{'annual-percentage-rate'};
                    $result['monthlyDebitInterest'] = (string) $this->response->content->{'installment-calculation-result'}->{'monthly-debit-interest'};
                    $result['numberOfRatesFull'] = (string) $this->response->content->{'installment-calculation-result'}->{'number-of-rates'};
                    $result['numberOfRates'] = $result['numberOfRatesFull']-1;
                    $result['rate'] = (string) $this->response->content->{'installment-calculation-result'}->{'rate'};
                    $result['lastRate'] = (string) $this->response->content->{'installment-calculation-result'}->{'last-rate'};
                    $result['debitSelect'] = (string) $this->response->content->{'installment-calculation-result'}->{'payment-firstday'};
                    $result['code'] = $reasonCode;
                    return $result;
                } else {
                    $this->error = 'FAIL';
                    return false;
                }
            default:
                $this->error = 'FAIL';
                return false;
        }
    }

    /**
     * Calls the CONFIGURATION_REQUEST
     *
     * @param array $headInfo
     * @param array $loggingInfo
     * @return boolean|array
     */
    public function callConfigurationRequest($headInfo,$loggingInfo)
    {
        $this->constructXml();
        $requestType = "CONFIGURATION_REQUEST";
        $this->setRequestHead($requestType, $headInfo);
        $loggingInfo['requestType'] = $requestType;
        $this->sendXmlRequest($loggingInfo);
        return $this->validateResponse($requestType);
    }

    /**
     * Calls the PROFILE_REQUEST
     *
     * @param array $headInfo
     * @param array $loggingInfo
     * @return boolean|array
     */
    public function callProfileRequest($headInfo,$loggingInfo)
    {
        $this->constructXml();
        $requestType = "PROFILE_REQUEST";
        $this->setRequestHead($requestType, $headInfo);
        $loggingInfo['requestType'] = $requestType;
        $this->sendXmlRequest($loggingInfo);
        return $this->validateResponse($requestType);
    }

    /**
     * Calls the CALCULATION_REQUEST
     *
     * @param array $headInfo
     * @param array $calculationInfo
     * @return boolean|array
     */
    public function callCalculationRequest($headInfo, $calculationInfo)
    {
        $this->constructXml();
        $requestType = "CALCULATION_REQUEST";
        $this->setRequestHead($requestType, $headInfo);
        $this->setRatepayContentCalculation($calculationInfo);
        $this->sendXmlRequest();
        return $this->validateResponse($requestType);
    }

    /**
     * Sets the Head Tag with all Informations based on, of which type the Request will be
     *
     * @param string $operationInfo
     * @param array $headInfo
     */
    private function setRequestHead($operationInfo, $headInfo)
    {
        $head = $this->request->addChild('head');
        $head->addChild('system-id', Mage::helper('core/http')->getServerAddr(false));

        $operation = $head->addChild('operation', $operationInfo);
        if (isset($headInfo['subtype']) && $headInfo['subtype'] != '') {
            $operation->addAttribute('subtype', $headInfo['subtype']);
        }

        $credential = $head->addChild('credential');
        $credential->addChild('profile-id', $headInfo['profileId']);
        $credential->addChild('securitycode', $headInfo['securityCode']);

        $this->_setRequestVersions($head);
    }

    /**
     * Sets the Content Tag with all Informations to the Request
     *
     * @param array $customerInfo
     * @param array $itemInfo
     * @param array $paymentInfo
     * @param string $requestInfo
     */
    private function setRequestContent($customerInfo, $itemInfo, $paymentInfo = '', $requestInfo = '')
    {
        $content = $this->request->addChild('content');
        if ($requestInfo == 'PAYMENT_REQUEST' || $requestInfo == 'PAYMENT_QUERY') {
            $this->setRatepayContentCustomer($content, $customerInfo);
        }

        $this->setRatepayContentBasket($content, $itemInfo);
        
        if ($requestInfo != 'CONFIRMATION_DELIVER' && $requestInfo != 'PAYMENT_QUERY') {
            $this->setRatepayContentPayment($content,$paymentInfo);
        }
    }
    
    /**
     * Set the shop version, the shop edition and the module version for the request
     * 
     * @param SimpleXMLElement $head 
     */
    private function _setRequestVersions($head)
    {
        $meta = $head->addChild('meta');
        $systems = $meta->addChild('systems');
        $system = $systems->addChild('system');
        $system->addAttribute('name', 'Magento_' . Mage::helper('produktratenrechner')->getEdition());
        $system->addAttribute(
            'version',  
            Mage::getVersion() . '_' . (string) Mage::getConfig()->getNode()->modules->RatePAY_ProduktRatenrechner->version
        );
    }

    /**
     * Sets the shopping-basket tag to the content tag with all Informations to the Request
     *
     * @param SimpleXMLElement $content
     * @param array $basketInfo
     */
    private function setRatepayContentBasket($content, $basketInfo)
    {
        $shoppingBasket = $content->addChild('shopping-basket');
        $shoppingBasket->addAttribute('amount', number_format($basketInfo['amount'], 2, ".", ""));
        $shoppingBasket->addAttribute('currency', $basketInfo['currency']);

        $items = $shoppingBasket->addChild('items');

        foreach ($basketInfo['items'] as $itemInfo) {
            $item = $items->addCDataChild('item', $this->removeSpecialChars($itemInfo['articleName']));
            $item->addAttribute('article-number', $this->removeSpecialChars($itemInfo['articleNumber']));
            $item->addAttribute('quantity', number_format($itemInfo['quantity'], 0, '.', ''));
            $item->addAttribute('unit-price-gross', number_format(round($itemInfo['unitPriceGross'],2), 2, ".", ""));
            $item->addAttribute('tax-rate', number_format($itemInfo['taxPercent'], 0, ".", ""));
        }
    }

    /**
     * Sets the payment tag to the content tag with all Informations to the Request
     *
     * @param SimpleXMLElement $content
     * @param array $paymentInfo
     */
    private function setRatepayContentPayment($content, $paymentInfo)
    {
        $payment = $content->addChild('payment');
        $payment->addAttribute('method', $paymentInfo['method']);
        $payment->addAttribute('currency', $paymentInfo['currency']);
        if ($this->getRequest()->head->operation == 'PAYMENT_REQUEST') {
            $payment->addChild('amount', number_format($paymentInfo['amount'], 2, ".", ""));
            if(isset($paymentInfo['debitType'])) {
                $payment->addChild('debit-pay-type', $paymentInfo['debitType']);
                $installment = $payment->addChild('installment-details');
                if(isset($paymentInfo['installmentNumber'])) {
                    $installment->addChild('installment-number', $paymentInfo['installmentNumber']);
                    $installment->addChild('installment-amount', $paymentInfo['installmentAmount']);
                    $installment->addChild('last-installment-amount', $paymentInfo['lastInstallmentAmount']);
                    $installment->addChild('interest-rate', $paymentInfo['interestRate']);
                    $installment->addChild('payment-firstday', $paymentInfo['paymentFirstDay']);
                }
            }
        }
    }

    /**
     * This method set's the installment-calculation element of the request xml
     */
    private function setRatepayContentCalculation($calculation)
    {
        $content = $this->request->addChild('content');
        $installment = $content->addChild('installment-calculation');

        $installment->addChild('amount', $calculation['amount']);

        if ($calculation['method'] == 'calculation-by-rate') {
            $calc_rate = $installment->addChild('calculation-rate');
            $calc_rate->addChild('rate', $calculation['value']);
        } else if ($calculation['method'] == 'calculation-by-time') {
            $calc_time = $installment->addChild('calculation-time');
            $calc_time->addChild('month', $calculation['value']);
        }
    }

    /**
     * Sending request to the RatePAY Server and returning the response.
     *
     * @param array $loggingInfo
     * @return SimpleXML
     */
    private function sendXmlRequest()
    {
        $client = Mage::getSingleton('produktratenrechner/request_communication');
        $client->resetParameters();
        $client->setRawData(trim($this->request->asXML(), "\xef\xbb\xbf"), "text/xml; charset=UTF-8");
        $rawdata = trim($this->request->asXML(), "\xef\xbb\xbf");
        $response = $client->request('POST');
        $this->response = new SimpleXMLElement($response->getBody());
        return $this->response;
    }

    /**
     * Creates new empty XML Object for Requests
     */
    public function constructXml()
    {
        $xmlString = '<request version="1.0" xmlns="urn://www.ratepay.com/payment/1_0"></request>';
        $this->request = null;
        $this->request = new RatePAY_ProduktRatenrechner_Model_Request_Xml($xmlString);
    }

    /**
     * This method replaced all zoot signs
     *
     * @param string $str
     * @return string
     */
    private function removeSpecialChars($str)
    {
        $search = array("–", "´", "‹", "›", "‘", "’", "‚", "“", "”", "„", "‟", "•", "‒", "―", "—", "™", "¼", "½", "¾");
        $replace = array("-", "'", "<", ">", "'", "'", ",", '"', '"', '"', '"', "-", "-", "-", "-", "TM", "1/4", "1/2", "3/4");
        return $this->removeSpecialChar($search, $replace, $str);
    }

    /**
     * This method replaced one zoot sing from a string
     *
     * @param array $search
     * @param array $replace
     * @param string $subject
     * @return string
     */
    private function removeSpecialChar($search, $replace, $subject)
    {
        $str = str_replace($search, $replace, $subject);
        return $str;
    }

}
