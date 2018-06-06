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
 * @package RatePAY_ProduktRatenrechner
 * @copyright Copyright (c) 2015 RatePAY GmbH (https://www.ratepay.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class RatePAY_ProduktRatenrechner_Model_LibraryConnector extends RatePAY_ProduktRatenrechner_Model_LibraryConnectorAbstract
{

    private $headModel;

    private $contentModel;

    private $onlineRequest;

    private $offlineRequest;

    public function __construct($sandbox = true)
    {
        // Set library as autoloader (and remove mage autoloader) while instancing library classes
        $this->setLibAutoloader();

        $this->headModel = new RatePAY\ModelBuilder('Head');
        $this->contentModel = new RatePAY\ModelBuilder('Content');
        $this->onlineRequest = new RatePAY\RequestBuilder((bool) $sandbox);
        $this->offlineRequest = new RatePAY\Service\OfflineInstallmentCalculation();

        // Switch back to mage autoloader
        $this->removeLibAutoloader();
    }

    public function callCalculationRequestOffline($content, $subtype = null)
    {
        // Set library as autoloader (and remove mage autoloader) while instancing library classes
        $this->setLibAutoloader();

        try {
            if (!is_null($content)) {
                $this->contentModel->setArray($content);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        try {
            if (is_null($subtype)) {
                $subtype = "calculation-by-time";
            }

            $response = $this->offlineRequest->callOfflineCalculation($this->contentModel)->subtype($subtype);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        // Switch back to mage autoloader
        $this->removeLibAutoloader();

        return $response;
    }

    public function callProfileRequest($head)
    {
        $head = $this->extendHeadData($head);

        // Set library as autoloader (and remove mage autoloader) while instancing library classes
        $this->setLibAutoloader();
        try {
            $this->headModel->setArray($head);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        try {
            $response = $this->onlineRequest->callProfileRequest($this->headModel);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        // Switch back to mage autoloader
        $this->removeLibAutoloader();

        return $response;
    }

    private function extendHeadData($head)
    {
        $edition = 'CE';
        if (file_exists(Mage::getBaseDir() . '/LICENSE_EE.txt')) {
            $edition = 'EE';
        } else if (file_exists(Mage::getBaseDir() . '/LICENSE_PRO.html')) {
            $edition = 'PE';
        }

        $head['SystemId'] = Mage::helper('core/http')->getServerAddr(false);
        $head['Meta'] = [
            'Systems' => [
                'System' => [
                    'Name' => "Magento_" . $edition,
                    'Version' => Mage::getVersion() . '_' . (string) Mage::getConfig()->getNode()->modules->RatePAY_ProduktRatenrechner->version // @ToDo: Move this to helper
                ]
            ]
        ];

        return $head;
    }
}
