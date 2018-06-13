<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category RatePAY
 * @package RatePAY_produktratenrechner
 * @copyright Copyright (c) 2015 RatePAY GmbH (https://www.ratepay.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class RatePAY_ProduktRatenrechner_Adminhtml_ProduktRatenrechner_ProfilerequestController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return profile request result
     *
     * @return void
     */
    public function callProfileRequestAction()
    {
        $credentials = array();
        $credentials['profile_id'] = $this->getRequest()->getParam('profile_id');
        $credentials['security_code'] = $this->getRequest()->getParam('security_code');
        $credentials['sandbox'] = $this->getRequest()->getParam('sandbox');
        $credentials['method'] = $this->_getRpMethod($this->getRequest()->getParam('method'));

        Mage::app()->getResponse()->setBody($this->_callProfileRequest($credentials));
    }

    private function _callProfileRequest($credentials) {
        $method = $credentials['method'];
        $country = $this->_getRpCountry($method);

        $request = Mage::getModel('produktratenrechner/libraryConnector', $credentials['sandbox'] == "1" ? true : false);

        // @ToDo: Move this to mapping helper
        $headInfo = [
            'Credential' => [
                'ProfileId' => $credentials['profile_id'],
                'Securitycode' => $credentials['security_code']
            ]
        ];

        $response = $request->callProfileRequest($headInfo);

        $coreConfig = Mage::getModel('core/config');

        if (!$response->isSuccessful()) {
            $coreConfig->saveConfig('catalog/' . $method . '/status', 0);
            return Mage::helper('produktratenrechner')->__('Request Failed') . " (" . Mage::helper('produktratenrechner')->__('Reason Message') . ": " . $response->getReasonMessage() . ")";
        }

        $result = $response->getResult();

        $merchantConfig = $result['merchantConfig'];
        $installmentConfig = $result['installmentConfig'];

        $coreConfig->saveConfig('catalog/' . $method . '/status', (
            ($merchantConfig['merchant-status'] == 2) &&
            ($merchantConfig['activation-status-installment'] == 2) &&
            ($merchantConfig['eligibility-ratepay-installment'] == 'yes')) ? 1 : 0
        );

        $coreConfig->saveConfig('catalog/' . $method . '/runtimes', $installmentConfig['month-allowed']);
        $coreConfig->saveConfig('catalog/' . $method . '/min_price', $merchantConfig['tx-limit-installment-min']);
        $coreConfig->saveConfig('catalog/' . $method . '/max_price', $merchantConfig['tx-limit-installment-max']);
        $coreConfig->saveConfig('catalog/' . $method . '/rate_min', $installmentConfig['rate-min-normal']);

        $coreConfig->saveConfig('catalog/' . $method . '/interestrate', $installmentConfig['interestrate-default']);
        $coreConfig->saveConfig('catalog/' . $method . '/service_charge', $installmentConfig['service-charge']);
        $coreConfig->saveConfig('catalog/' . $method . '/payment_firstday', $installmentConfig['valid-payment-firstdays']);

        return 1;
    }

    private function _getRpMethod($id) {
        return str_replace('catalog_', '', $id);
    }

    private function _getRpMethodWithoutCountry($id) {
        $id = str_replace('_de', '', $id);
        $id = str_replace('_at', '', $id);
        $id = str_replace('_ch', '', $id);
        $id = str_replace('_nl', '', $id);
        $id = str_replace('_be', '', $id);
        $id = str_replace('0', '', $id);

        return $id;
    }

    private function _getRpCountry($id) {
        if(strstr($id, '_at')) {
            return 'at';
        }
        if(strstr($id, '_ch')) {
            return 'ch';
        }
        if(strstr($id, '_nl')) {
            return 'nl';
        }
        if(strstr($id, '_be')) {
            return 'be';
        }
        return 'de';
    }
}
