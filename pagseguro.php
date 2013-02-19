<?php

defined('_JEXEC') or die('Restricted access');

/**
 *
 * a special type of 'pagseguro ':
 *
 * @author Wellington Camargo
 * @version $Id: pagseguro.php 5177 2013-01-23$
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2013 S2IT Solutions Consultoria LTDA - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.org
 */
if (!class_exists('vmPSPlugin')) {
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVmPaymentPagseguro extends vmPSPlugin {

    // instance of class
    public static $_this = FALSE;
    private static $_moduleVersion = '1.0';
    
    
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
    

    public function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment PagSeguro Table');
    }

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
            'tax_id'                        => 'smallint(11) DEFAULT NULL'
        );
    }

    private function _getPagSeguroVarsToPush() {
        return array(
            'pagseguro_email'           => array('', 'string'),
            'pagseguro_token'           => array('', 'string'),
            'pagseguro_charset'         => array('', 'string'),
            'pagseguro_url_redirect'    => array('', 'string'),
            'pagseguro_log'             => array('', 'int'),
            'pagseguro_log_file_name'   => array('', 'string'),
            'status_waiting_payment'    => array('', 'char'),
            'status_in_analisys'        => array('', 'char'),
            'status_paid'               => array('', 'char'),
            'status_available'          => array('', 'char'),
            'status_in_dispute'         => array('', 'char'),
            'status_refunded'           => array('', 'char'),
            'status_cancelled'          => array('', 'char')
        );
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
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
        return $this->displayListFE($cart, $selected, $htmlIn);
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
            require(JPATH_VM_ADMINISTRATOR . DS . 'table' . DS . 'vendors.php');
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
        $currency_code_3 = shopFunctions::getCurrencyByID($method->payment_currency, 'currency_code_3');
        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, FALSE), 2);

        // creating an entry for payment into PagSeguro payment table
        $this->_virtuemart_paymentmethod_id = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['payment_name'] = $this->renderPluginName($method);
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = (!empty($method->cost_per_transaction) ? $method->cost_per_transaction : 0);
        $dbValues['cost_percent_total'] = (!empty($method->cost_percent_total) ? $method->cost_percent_total : 0);
        $dbValues['payment_currency'] = $currency_code_3;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $this->storePSPluginInternalData($dbValues);
        
        // performing PagSeguro transaction
        $pagSeguroPaymentRequest = $this->_generatePagSeguroRequestData($cart, $order, $method);
        $url = $this->_performPagSeguroRequest($pagSeguroPaymentRequest, $method);
        
        // if not created a valid url, order isn't finalized (0), else order is finalized (1)
        $returnValue = (!$url) ? 0 : 1;

        $html .= '<a href="'.$url.'" target="_blank" >Ir para o PagSeguro</a>';
        
        // setting new order status
        $newStatus = $method->status_waiting_payment;
        
        return $this->processConfirmedOrderPaymentResponse($returnValue, $cart, $order, $html, $dbValues['payment_name'], $newStatus);
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
       //echo '<pre>';print_r($cart_prices);exit();
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
    * Perform currency conversion to float
    * @param $method
    */
    function convert ($method) {
        $method->min_amount = (float)$method->min_amount;
        $method->max_amount = (float)$method->max_amount;
    }
    
    /**
     * Get order currency iso code 3
     * @param type $currencyID
     * @return type
     */
    private function _getCurrencyIsoCode3($currencyID){
        $db = JFactory::getDbo();
        $sql = "select currency_code_3 from #__virtuemart_currencies where virtuemart_currency_id=".$currencyID;
        $db->setQuery($sql);
        return $db->loadResult();
    }

    /**
     * Generates PagSeguro request data
     * @param VirtueMartCart $cart
     * @param array $order
     * @param TablePaymentmethods $method
     * @return \PagSeguroPaymentRequest
     */
    private function _generatePagSeguroRequestData(VirtueMartCart $cart, Array $order, TablePaymentmethods $method, $Cart_Prices){
        
        $paymentRequest = new PagSeguroPaymentRequest();
        $paymentRequest->setCurrency($this->_getCurrencyIsoCode3($method->payment_currency)); // currency
        $paymentRequest->setReference($order['details']['BT']->order_number); // reference
        $paymentRequest->setRedirectURL($method->pagseguro_url_redirect); // redirect url
        $paymentRequest->setItems($this->_generateProductsData($cart)); // products

        $sender = (isset($order['details']['ST']) && (count($order['details']['ST'] > 0)) ? $order['details']['ST'] : $order['details']['BT']); 
        $paymentRequest->setSender($this->_generateSenderData($sender)); // sender
        $paymentRequest->setShipping($this->_generateShippingData($sender)); // shipping
        
        return $paymentRequest;
    }
    
    /**
     *  Generates products data to PagSeguro transaction
     *  @return Array PagSeguroItem
     */
    private function _generateProductsData(VirtueMartCart $cart){
        
        $pagSeguroItems = array();
        
        $cont = 1;

        foreach ($cart->products as $key => $product) {
            
            $pagSeguroItem = new PagSeguroItem();
            $pagSeguroItem->setId($cont++);
            $pagSeguroItem->setDescription($this->_truncateValue($product->product_name, 255)); 
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
            $pagSeguroSender->setName($this->_truncateValue(trim($sender->first_name). ' ' . trim($sender->last_name), 50, ''));
        }
        
        return $pagSeguroSender;
    }
    
    /**
     *  Generates shipping data to PagSeguro transaction
     *  @return PagSeguroShipping
     */
    private function _generateShippingData($deliveryAddress){
        
        $shipping = new PagSeguroShipping();
        $shipping->setAddress($this->_generateShippingAddressData($deliveryAddress));
        $shipping->setType($this->_generateShippingType());
        
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
            $address->setCountry($this->_getColumValue('virtuemart_countries', 'country_3_code', 'virtuemart_country_id', $deliveryAddress->virtuemart_country_id));
            $address->setState($this->_getColumValue('virtuemart_states', 'state_2_code', 'virtuemart_state_id', $deliveryAddress->virtuemart_state_id));
        }
        
        return $address;
    }

    /**
     *  Perform PagSeguro request and return url from PagSeguro
     *  @return mixed - if is a valid url, return url string, else return false
     */
    private function _performPagSeguroRequest(PagSeguroPaymentRequest $pagSeguroPaymentRequest, TablePaymentmethods $method){
        
        try {
            // setting PagSeguro configurations
            $this->_setPagSeguroConfiguration($method);
            
            // setting PagSeguro Prestashop module version
            $this->_setPagSeguroModuleVersion();
            
            // getting credentials
            $credentials = new PagSeguroAccountCredentials($method->pagseguro_email, $method->pagseguro_token);

            // get PagSeguro request return and verify if is a valid url
            return filter_var($pagSeguroPaymentRequest->register($credentials), FILTER_VALIDATE_URL);
            
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
            $filename = JPATH_BASE.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.$method->pagseguro_log_file_name;
            $this->_verifyFile($filename);
            PagSeguroConfig::activeLog($filename);
        }

    }
    
    /**
     * Retrieve PagSeguro PrestaShop module version
     */
    private function _setPagSeguroModuleVersion(){
        PagSeguroLibrary::setModuleVersion('virtuemart-v'.self::$_moduleVersion);
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
    private function _getColumValue($table, $select, $where, $value){
        $db = JFactory::getDbo();
        $sql = "select $select from #__$table where $where=".$value;
        $db->setQuery($sql);
        return $db->loadResult();
    }
    
    /**
     * Perform truncate of string value
     * @param string $string
     * @param type $limit
     * @param type $endchars
     * @return string
     */
    private function _truncateValue($string, $limit, $endchars = '...'){
        
        if (!is_array($string) || !is_object($string)){
            
            $stringLength = strlen($string);
            $endcharsLength  = strlen($endchars);
            
            if ($stringLength > (int)$limit){
                $cut = (int)($limit - $endcharsLength);
                $string = substr($string, 0, $cut).$endchars;
            }
        }
        return $string;
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
