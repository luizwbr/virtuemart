<?php

/*
************************************************************************
Copyright [2013] [PagSeguro Internet Ltda.]

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
************************************************************************
*/

defined('_JEXEC') or die('Restricted access');

if (!class_exists('vmPSPlugin')) {
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVmPaymentPagseguro extends vmPSPlugin {

    /**
     * The instance of class
     * @var object 
     */
    public static $_this = FALSE;

    /**
     * The plugin version
     * @var string 
     */
    private static $_pluginVersion = '1.1';
    
    /**
     * The constructor
     * @param type $subject
     * @param type $config
     */
    public function __construct(&$subject, $config) {
        
        parent::__construct($subject, $config);

        $this->_loggable = TRUE;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';
        $this->setConfigParameterable($this->_configTableFieldName, $this->_getPagSeguroVarsToPush());
        
        // adding PagSeguro API
        $this->_addPagSeguroLibrary();

    }
   
    /**
     * Adding PagSeguro API for module
     */
    private function _addPagSeguroLibrary(){
        require_once 'PagSeguroLibrary/PagSeguroLibrary.php';
    }
    
    /**
     * Create table sql for PagSeguro Payment Plugin
     * @return String
     */
    public function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment PagSeguro Table');
    }
    
    /**
     * Table fields for PagSeguro Payment Plugin
     * @return Array
     */
    public function getTableSQLFields() {
        return array(
            'id'                            => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id'           => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number'                  => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id'   => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name'                  => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total'           => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency'              => 'char(3) ',
            'cost_per_transaction'          => 'decimal(10,2) DEFAULT NULL',
            'cost_percent_total'            => 'decimal(10,2) DEFAULT NULL',
            'tax_id'                        => 'smallint(11) DEFAULT NULL',
            'reference'                     => 'char(32) DEFAULT NULL'
        );
    }

    /**
     * Get util variables for PagSeguro Payment Plugin
     * @return Array
     */
    private function _getPagSeguroVarsToPush() {
        return array(
            'pagseguro_email'               => array('', 'string'),
            'pagseguro_token'               => array('', 'string'),
            'pagseguro_charset'             => array('', 'string'),
            'pagseguro_url_redirect'        => array('', 'string'),
            'pagseguro_url_notification'    => array('', 'string'),
            'pagseguro_log'                 => array('', 'int'),
            'pagseguro_log_file_name'       => array('', 'string'),
            'payment_logos'                 => array('', 'char'),
            'status_waiting_payment'        => array('', 'char'),
            'status_in_analysis'            => array('', 'char'),
            'status_paid'                   => array('', 'char'),
            'status_available'              => array('', 'char'),
            'status_in_dispute'             => array('', 'char'),
            'status_refunded'               => array('', 'char'),
            'status_cancelled'              => array('', 'char')
        );
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for example
     *
     * @param object  $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
        
        if (PagSeguroCurrencies::checkCurrencyAvailabilityByIsoCode(shopFunctions::getCurrencyByID($cart->pricesCurrency, 'currency_code_3'))){
            return $this->displayListFE($cart, $selected, $htmlIn);
        }
        return false;
    }

    /**
     * displays the logos of a VirtueMart plugin
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     * @param array $logo_list
     * @return html with logos
     */
    protected function displayLogos($logo_list) {

        $img = "";

        if (!(empty($logo_list))) {
            
            $url = JURI::root () . '/media/images/stories/virtuemart/' . $this->_psType . '/';
            if (!is_array ($logo_list)) { $logo_list = (array)$logo_list; }
            foreach ($logo_list as $logo) {
                    $alt_text = substr ($logo, 0, strpos ($logo, '.'));
                    $img .= '<span class="vmCartPaymentLogo" ><img align="middle" src="' . $url . $logo . '"  alt="' . $alt_text . '" /></span> ';
            }
        }
        return $img;
    }    
    
    /**
     * Add required classes to order payment
     */
    private function _addRequiredClasses() {
        if (!class_exists('VirtueMartModelOrders')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
        }

        if (!class_exists('VirtueMartModelCurrency')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
        }

        if (!class_exists('TableVendors')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . 'vendors.php');
        }
        
        if (!class_exists ('VirtueMartCart')) {
            require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        }
        
    }

    /**
     * Fired when user click confirm order
     * @param $cart
     * @param $order
     * @return bool|null
     */
    function plgVmConfirmedOrder($cart, $order) {
        
        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        
        if (!$this->selectedThisElement($method->payment_element)) {
            return FALSE;
        }
        
        $this->_debug = $method->debug;
        $this->logInfo('PagSeguro plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

        // adding required classes
        $this->_addRequiredClasses();

        // vendor data
        $vendorModel = VmModel::getModel('Vendor');
        $vendorModel->setId(1);
        $vendor = $vendorModel->getVendor();
        $vendorModel->addImages($vendor, 1);

        // getting order data
        $this->getPaymentCurrency($method);
        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, FALSE), 2);

        // creating an entry for payment into PagSeguro payment table
        $this->_virtuemart_paymentmethod_id = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['payment_name'] = $this->renderPluginName($method);
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = (!empty($method->cost_per_transaction) ? $method->cost_per_transaction : 0);
        $dbValues['cost_percent_total'] = (!empty($method->cost_percent_total) ? $method->cost_percent_total : 0);
        $dbValues['payment_currency'] = PagSeguroCurrencies::getIsoCodeByName('REAL');
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $dbValues['reference'] = $order['details']['BT']->virtuemart_order_id;
        
        // storing data order into plugin data table
        $this->storePSPluginInternalData($dbValues);
        
        // performing PagSeguro transaction
        $pagSeguroPaymentRequest = $this->_generatePagSeguroRequestData($cart, $order, $method);
        $url = $this->_performPagSeguroRequest($pagSeguroPaymentRequest, $method);

        // setting new order status
        $newStatus = $method->status_waiting_payment;
        
        // processing PagSeguro response
        $this->_processPagSeguroPaymentResponse($url, $cart, $order, $dbValues['payment_name'], $newStatus);
        
    }

    /**
     * Proccess PagSeguro response
     * If a valid url , redirect to PagSeguro
     * else, show and error page
     * @param String $url
     * @param VirtueMartCart $cart
     * @param Array $order
     * @param String $payment_name
     * @param String $newStatus
     */
    private function _processPagSeguroPaymentResponse($url, VirtueMartCart $cart, Array $order, $payment_name, $newStatus){
        
         $application = JFactory::getApplication();
        
        if (filter_var($url, FILTER_VALIDATE_URL)){
            // We delete the old stuff
            // send the email only if payment has been accepted
            // update status

            $modelOrder = VmModel::getModel ('orders');
            $order['order_status'] = $newStatus;
            $order['customer_notified'] = 1;
            $order['comments'] = '';
            $order['paymentName'] = $payment_name;
            $modelOrder->updateStatusForOneOrder ($order['details']['BT']->virtuemart_order_id, $order, TRUE);
            
            // Redirecting to PagSeguro
            $application = JFactory::getApplication();
            $application->redirect($url);
        }
        else {
            // error while processing the payment
            $application->redirect (JRoute::_ ('index.php?option=com_virtuemart&view=cart'), JText::_ ('COM_VIRTUEMART_CART_ORDERDONE_DATA_NOT_VALID'));
        }
        
    }
    
    /**
     * Get payment currency
     * @param type $virtuemart_paymentmethod_id
     * @param type $paymentCurrencyId
     * @return null|boolean
     */
    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return FALSE;
        }

        $this->getPaymentCurrency($method);
        $paymentCurrencyId = $method->payment_currency;
    }

    /**
    * Display stored payment data for an order
    * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
    */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
        
        if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
            return NULL; // Another method was selected, do nothing
        }

        // getting order data from PagSeguro payment table
        $paymentTable = $this->_getPagSeguroPaymentData($virtuemart_order_id);
        $this->getPaymentCurrency($paymentTable);

        $html = '<table class="adminlist">' . "\n";
        $html .=$this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_NAME', $paymentTable->payment_name);
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
        $html .= '</table>' . "\n";
        
        return $html;
    }

    /**
     * Calcule final price to product
     * 
     * @param VirtueMartCart $cart
     * @param $key
     * @return float
     */
    private function calculePrice(VirtueMartCart $cart ,$key){
       
        if (!class_exists ('calculationHelper')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
        }
 
       $calculator = calculationHelper::getInstance();
       $cart_prices = $calculator->getCheckoutPrices($cart);       
       
       $sales_price = $cart->products[$key]->product_price;
       
       foreach($cart_prices as $prices_key => $prices){
           if($key === $prices_key){
               $sales_price = $prices["salesPrice"];
           }
       }
       
        return $sales_price;
    }
    
    /**
    * Check if the payment conditions are fulfilled for this payment method
    * @author: Valerie Isaksen
    * @param $cart_prices: cart prices
    * @param $payment
    * @return true: if the conditions are fulfilled, false otherwise
    */
    protected function checkConditions($cart, $method, $cart_prices) {

        $this->convert ($method);

        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

        $amount = $cart_prices['salesPrice'];
        $amount_cond =  ($amount >= $method->min_amount AND $amount <= $method->max_amount
                        OR
                        ($method->min_amount <= $amount AND ($method->max_amount == 0)));

        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array ($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }
        // probably did not gave his BT:ST address
        if (!is_array ($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (!isset($address['virtuemart_country_id'])) {
            $address['virtuemart_country_id'] = 0;
        }

        if (in_array ($address['virtuemart_country_id'], $countries) || count ($countries) == 0) {
            if ($amount_cond) {
                return TRUE;
            }
        }
        
        return FALSE;
    }

    /**
     * We must reimplement this triggers for joomla 1.7
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     *
     * @author Valérie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     *
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {
        return $this->OnSelectCheck($cart);
    }

    /*
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price (value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
     * @author Valerie Isaksen
     * @cart: VirtueMartCart the current cart
     * @cart_prices: array the new cart prices
     * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
     *
     */

    /**
     * @param VirtueMartCart $cart
     * @param array          $cart_prices
     * @param                $cart_prices_name
     * @return bool|null
     */
    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
	return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     *
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    /**
     * Fired in payment method when click save into
     * payment method info view
     * @param String $name
     * @param Integer $id
     * @param String $table
     * @return bool
     */
    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }
    
    /**
     * Process PagSeguro response URL
     * @param String $html
     * @return boolean
     */
    function plgVmOnPaymentResponseReceived (&$html) {
        
        // adding required classes
        $this->_addRequiredClasses();
        
        //We delete the old stuff
        // get the correct cart / session
        $cart = VirtueMartCart::getCart ();
        $cart->emptyCart ();
        
        return TRUE;
    }
    
    /*
    * plgVmOnPaymentNotification() - This event is fired by Offline Payment. It can be used to validate the payment data as entered by the user.
    * Return:
    * Parameters:
    * None
    * @author Valerie Isaksen
    */
    function plgVmOnPaymentNotification () {

		$post = $_POST;

        if (!PagSeguroHelper::isNotificationEmpty($post)){
            
            $notificationType = new PagSeguroNotificationType($post['notificationType']);

            $strType = $notificationType->getTypeFromValue();

            switch ($strType) {

                case 'TRANSACTION':
                    $this->_doUpdateByNotification($post['notificationCode']);
                    break;

                default:
                    LogPagSeguro::error("Unknown notification type [" . $notificationType->getValue() . "]");
            }

        } else {

            LogPagSeguro::error("Invalid notification parameters.");
        }
        
    }
   
    /**
     * Gets the redirect url
     * @param type $redirectUrl
     * @return string
     */
    private function _getRedirectUrl($redirectUrl){
        if (PagSeguroHelper::isEmpty($redirectUrl)){
            $redirectUrl = JROUTE::_ (JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived');
        }
        
        return $redirectUrl;
    }
    
    /**
     * Gets PagSeguro plugin notification url
     * if empty the url configured in plugin configuration area, return default plugin url notification
     * @return string
     */
    private function _getNotificationUrl($notificationUrl){
        if (PagSeguroHelper::isEmpty($notificationUrl)){
            $notificationUrl = JROUTE::_ (JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component');
        }
        
        return $notificationUrl;
    }
    
    /**
     * Perform update by received PagSeguro notification
     * @param string $notificationCode
     */
    private function _doUpdateByNotification($notificationCode) {
        // getting configuration params data 
        $paramsData = $this->_getParamsData();
        
    	try {
            // getting credentials data
            $credentials = new PagSeguroAccountCredentials($paramsData['pagseguro_email'], $paramsData['pagseguro_token']);
            // getting transaction data object
            $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);
            // getting PagSeguro status number
            $statusPagSeguro = $transaction->getStatus()->getValue();
            // getting new order state
            $newStatus = $this->_getAssociatedStatus($paramsData, $statusPagSeguro);
            // getting status translation
            $statusTranslation = $this->_getStatusTranslation($statusPagSeguro);
            // performing update status
            if (!PagSeguroHelper::isEmpty($newStatus)){
                $this->_updateOrderStatus($transaction->getReference(), $newStatus, $statusTranslation);
            }
                
    	} catch (PagSeguroServiceException $e) {
            LogPagSeguro::error("Error trying get transaction [" . $e->getMessage() . "]");
    	}

    }
    
    /**
     * Gets PagSeguro translation for status
     * @param int $status
     * @param string $language
     * @return string The translated status.
     */
    private function _getStatusTranslation($statusPagSeguro, $language = 'br'){
        // including translation class
        include_once 'pagseguroorderstatustranslation.php';
        return PagSeguroOrderStatusTranslation::getStatusTranslation($this->_getStatusString($statusPagSeguro), $language);
    }
    
    /**
     * Gets the status string description for $statusPagSeguro
     * @param int $statusPagSeguro
     * @return string
     */
    private function _getStatusString($statusPagSeguro){
        $transactionStatus = new PagSeguroTransactionStatus($statusPagSeguro);
        return $transactionStatus->getTypeFromValue();
    }
    
    /**
     * Check if haystack starts with needle
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    private function _startswith($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
    
    /**
     * Gets the associated VirtueMart PagSeguro plugin configuration status with PagSeguro received status
     * if find, return the status code, else, return NULL
     * 
     * @param array $paramsData
     * @param string $statusPagSeguro
     * @return mixed
     */
    private function _getAssociatedStatus(Array $paramsData, $statusPagSeguro){
        
        $statusList = array();
        
        foreach ($paramsData as $key => $value) {
            
            if ($this->_startswith($key, 'status_')){
                $statusList[strtoupper(str_replace('status_', '', $key))] = $value;
            }
        }
        
        $statusString = $this->_getStatusString($statusPagSeguro);

        return (isset($statusList[$statusString])) ? $statusList[$statusString] : NULL;
    }
    
    /**
     * Gets the PagSeguro plugin configured values
     * @return array
     */
    private function _getParamsData(){
        
        $paramsData = array();
        
        $db = JFactory::getDBO();
        $db->setQuery('SELECT `payment_params` FROM `#__virtuemart_paymentmethods` WHERE `payment_element`="pagseguro" ');
        $data = explode('|', $db->loadResult());
        
        foreach ($data as $param) {
            if (!PagSeguroHelper::isEmpty($param)){
                $array_temp = explode('=', $param);
                $paramsData[$array_temp[0]] = str_replace('"', '', $array_temp[1]);
            }
        }
        
        return $paramsData;
    }
    
    /**
     * Do the update order status in the system
     * @param int $reference
     * @param char $newStatus
     */
    private function _updateOrderStatus($reference, $newStatus, $statusTranslation){
        
        $model = VmModel::getModel('orders');
        
        $inputOrder = array('order_status' => $newStatus,
                            'customer_notified' => TRUE,
                            'comments' => '['.$statusTranslation.']');

        return $model->updateStatusForOneOrder($reference, $inputOrder);
    }
    
    /**
    * Perform currency conversion to float
    * @param $method
    */
    function convert ($method) {
        $method->min_amount = (float)$method->min_amount;
        $method->max_amount = (float)$method->max_amount;
    }

    /**
     * Generates PagSeguro request data
     * @param VirtueMartCart $cart
     * @param array $order
     * @param TablePaymentmethods $method
     * @return PagSeguroPaymentRequest
     */
    private function _generatePagSeguroRequestData(VirtueMartCart $cart, Array $order, TablePaymentmethods $method){
        
        $paymentRequest = new PagSeguroPaymentRequest();
        $paymentRequest->setCurrency(PagSeguroCurrencies::getIsoCodeByName('REAL')); // currency
        $paymentRequest->setReference($order['details']['BT']->virtuemart_order_id); // reference
        $paymentRequest->setRedirectURL($this->_getRedirectUrl($method->pagseguro_url_redirect)); // redirect url
        $paymentRequest->setNotificationURL($this->_getNotificationUrl($method->pagseguro_url_notification)); // notification url
        $paymentRequest->setItems($this->_generateProductsData($cart)); // products
        $paymentRequest->setExtraAmount($this->_getExtraAmountValues($cart)); // extra values
        
        $sender = (isset($order['details']['ST']) && (count($order['details']['ST'] > 0)) ? $order['details']['ST'] : $order['details']['BT']); 
        $paymentRequest->setSender($this->_generateSenderData($sender)); // sender

        $paymentRequest->setShipping($this->_generateShippingData($sender, $cart->pricesUnformatted['salesPriceShipment'])); // shipping
        
        return $paymentRequest;
    }

    /**
     * Gets extra amount cart values (coupon and shipping)
     * @param VirtueMartCart $cart
     * @return float
     */
    private function _getExtraAmountValues(VirtueMartCart $cart){
        $coupon = (float)$cart->pricesUnformatted['salesPriceCoupon'];
        
        return PagSeguroHelper::decimalFormat($coupon * (-1));
    }
    
    /**
     * Generates products data to PagSeguro transaction
     * @param VirtueMartCart $cart
     * @return array
     */
    private function _generateProductsData(VirtueMartCart $cart){
        
        $pagSeguroItems = array();
        
        $cont = 1;

        foreach ($cart->products as $key => $product) {
            
            $pagSeguroItem = new PagSeguroItem();
            $pagSeguroItem->setId($cont++);
            $pagSeguroItem->setDescription($product->product_name);
            $pagSeguroItem->setQuantity($product->quantity);           
            $pagSeguroItem->setAmount(number_format( $this->calculePrice($cart, $key) , 2));
            $pagSeguroItem->setWeight((int)ShopFunctions::convertWeigthUnit($product->product_weight, $product->product_weight_uom, 'G')); // defines weight in gramas
            
            array_push($pagSeguroItems, $pagSeguroItem);
        }
        
        return $pagSeguroItems;
    }
    
    /**
     *  Generates sender data to PagSeguro transaction
     *  @return PagSeguroSender
     */
    private function _generateSenderData($sender){
        $pagSeguroSender = new PagSeguroSender();
        
        if (isset($sender) && !is_null($sender)){
            $pagSeguroSender->setEmail($sender->email);
            $pagSeguroSender->setName($sender->first_name. ' ' . $sender->last_name);
        }
        
        return $pagSeguroSender;
    }

    /**
     * Generates shipping data to PagSeguro transaction
     * @param stdClass $deliveryAddress
     * @param float $shippingCost
     * @return \PagSeguroShipping
     */
    private function _generateShippingData($deliveryAddress, $shippingCost){
        
        $shipping = new PagSeguroShipping();
        $shipping->setAddress($this->_generateShippingAddressData($deliveryAddress));
        $shipping->setType($this->_generateShippingType());
        $shipping->setCost(PagSeguroHelper::decimalFormat((float)$shippingCost));

        return $shipping;
    }
    
    /**
     *  Generate shipping type data to PagSeguro transaction
     *  @return PagSeguroShippingType
     */
    private function _generateShippingType(){
        $shippingType = new PagSeguroShippingType();
        $shippingType->setByType('NOT_SPECIFIED');
        
        return $shippingType;
    }
    
    /**
     *  Generates shipping address data to PagSeguro transaction
     *  @return PagSeguroAddress
     */
    private function _generateShippingAddressData($deliveryAddress){

        $address = new PagSeguroAddress();
        
        if (!is_null($deliveryAddress)){
            $address->setCity($deliveryAddress->city);
            $address->setPostalCode($deliveryAddress->zip);
            $address->setStreet($deliveryAddress->address_1);
            $address->setDistrict($deliveryAddress->address_2);
            $address->setCountry($this->_getColumnValue('virtuemart_countries', 'country_3_code', 'virtuemart_country_id', $deliveryAddress->virtuemart_country_id));
            $address->setState($this->_getColumnValue('virtuemart_states', 'state_2_code', 'virtuemart_state_id', $deliveryAddress->virtuemart_state_id));
        }
        
        return $address;
    }

    /**
     *  Perform PagSeguro request and return url from PagSeguro
     *  @return string
     */
    private function _performPagSeguroRequest(PagSeguroPaymentRequest $pagSeguroPaymentRequest, TablePaymentmethods $method){
        
        
        try {
            // setting PagSeguro configurations
            $this->_setPagSeguroConfiguration($method);
            
            // setting PagSeguro plugin version
            $this->_setPagSeguroModuleVersion();
            
            // setting VirtueMart version
            $this->_setPagSeguroCMSVersion();
            
            // getting credentials
            $credentials = new PagSeguroAccountCredentials($method->pagseguro_email, $method->pagseguro_token);

            // return performed PagSeguro request values
            return $pagSeguroPaymentRequest->register($credentials);
            
        }
        catch(PagSeguroServiceException $e){
            die($e->getMessage());
        }
        
    }
    
    /**
     * Retrieve PagSeguro data configuration from database
     */
    private function _setPagSeguroConfiguration(TablePaymentmethods $method){

        // retrieving configurated default charset
        PagSeguroConfig::setApplicationCharset($method->pagseguro_charset);
        
        // retrieving configurated default log info
        if ($method->pagseguro_log){
            $filename = JPATH_BASE.$method->pagseguro_log_file_name;
            $this->_verifyFile($filename);
            PagSeguroConfig::activeLog($filename);
        }

    }
    
    /**
     * Sets PagSeguro plugin version
     */
    private function _setPagSeguroModuleVersion(){
        PagSeguroLibrary::setModuleVersion('virtuemart-v.'.self::$_pluginVersion);
    }
    
    /**
     * Sets VirtueMart version
     */
    private function _setPagSeguroCMSVersion(){
        PagSeguroLibrary::setCMSVersion('virtuemart-v.'.vmVersion::$RELEASE);
    }
    
    /**
     * Get PagSeguro payment data from database
     * @param int $virtuemart_order_id
     * @return mixed $paymentTable
     */
    private function _getPagSeguroPaymentData($virtuemart_order_id){
        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` '
                . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);
        if (!($paymentTable = $db->loadObject())) {
            vmWarn(500, $q . " " . $db->getErrorMsg());
            return '';
        }
        
        return $paymentTable;
    }
    
    /**
     * Get order currency iso code 3
     * @param type $currencyID
     * @return type
     */
    private function _getColumnValue($table, $select, $where, $value){
        $db = JFactory::getDbo();
        $sql = "select $select from #__$table where $where=".$value;
        $db->setQuery($sql);
        return $db->loadResult();
    }
    
    /**
     * Try create file if not exists
     * @param string $filename
     */
    private function _verifyFile($filename){
        
        try {
            $f = fopen($filename, 'a');
            fclose($f);
        }
        catch(Exception $e){
            die($e->getMessage());
        }
    }
    
}

?>
