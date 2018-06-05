<?php

class RatePAY_ProduktRatenrechner_Block_Product_Ratenrechner extends Mage_Core_Block_Template {

    private $amount = 0;

    private $allowedRuntimes = [];

    public function __construct(array $args = [])
    {
        parent::__construct($args);

        $productId = Mage::registry('current_product')->getId();
        $collection = Mage::getModel('catalog/product')->load($productId);

        $this->amount = (is_null($collection->getSpecialPrice())) ? $collection->getPrice() : $collection->getSpecialPrice();
    }

    public function getProductPrice() {
        return $this->amount;
    }

    public function getRuntimes() {
        if (count($this->allowedRuntimes) > 0) {
            return $this->allowedRuntimes;
        }

        $libraryConnector = Mage::getSingleton('produktratenrechner/libraryconnector');

        $allRuntimes = explode(',', $this->getConfig("runtimes"));
        $amount = $this->amount;
        $interestrate = $this->getConfig("interestrate");
        $serviceCharge = $this->getConfig("service_charge");
        $paymentFirstday = $this->getConfig("payment_firstday");
        $minimumRate = $this->getConfig("rate_min");

        $content = [
            'InstallmentCalculation' => [
                'Amount' => $amount,
                'ServiceCharge' => $serviceCharge,
                'InterestRate' => $interestrate,
                'PaymentFirstday' => (strpos($paymentFirstday, ',') !== false) ? strstr($paymentFirstday, ',', true) : $paymentFirstday
            ]
        ];

        foreach ($allRuntimes as $runtime) {
            $content['InstallmentCalculation']['CalculationTime']['Month'] = $runtime;

            $rateAmount = $libraryConnector->callCalculationRequestOffline($content, "calculation-by-time");

            if($rateAmount >= $minimumRate){
                $allowedRuntimes[$runtime] = $this->getText($runtime, $rateAmount);
            }
        }

        $this->allowedRuntimes = $allowedRuntimes;

        return $allowedRuntimes;
    }

    public function getDefaultRuntime() {
        $defaultRuntime = $this->getConfig("default_runtime");

        $runtimes = $this->getRuntimes();
        if (key_exists($defaultRuntime, $runtimes)) {
            return [$defaultRuntime => $runtimes[$defaultRuntime]];
        } else {
            $value = end($runtimes);
            return [key($runtimes) => $value];
        }
    }

    public function isDefaultRuntimeOnly() {
        return (bool) $this->getConfig("default_runtime_only");
    }

    public function isAvailable() {
        return
            (bool) $this->getConfig("status") &&
            (bool) $this->getConfig("active") &&
            $this->getProductPrice() >= $this->getConfig("min_price") &&
            $this->getProductPrice() <= $this->getConfig("max_price") &&
            count($this->getRuntimes()) > 0;
    }

    // @ToDo: Move method to helper
    private function getConfig($field, $suffix = null) {
        if (is_null($suffix)) {
            $suffix = strstr(Mage::app()->getStore()->getLocaleCode(), "_", true); // If no suffix is set, store country is taken
        }

        $storeId = Mage::app()->getStore()->getStoreId();
        $path = 'catalog/ratepay_prr_' . $suffix . '/' . $field;
        return Mage::getStoreConfig($path, $storeId);
    }

    private function getText($runtime, $amount) {
        if ($this->getConfig("round", "general")) {
            $amount = floor($amount);
        } else {
            $amount = number_format($amount, 2, ",", "");
        }

        $responseText = $this->getConfig("text");
        $responseText = str_replace("{runtime}", $runtime, $responseText);
        $responseText = str_replace("{price}", $amount, $responseText);

        return $responseText;
    }

}