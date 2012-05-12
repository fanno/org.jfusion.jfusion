<?php
/**
 * @package JFusion
 * @author JFusion development team
 * @copyright Copyright (C) 2009 JFusion. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Jfusion_Joomla_Helper_Data extends Mage_Core_Helper_Data {

	protected $_data;

	function __construct() {
		$this->_data = Mage::getStoreConfig ( 'joomla/joomlaconfig' );
	}

	function getBaseUrl(){
		return self::getJSecureBaseUrl();
	}
	
	function getJSecureBaseUrl() {
	    if(isset($this->_data["baseurl"])){
    		$parsedUrl = parse_url($this->_data["baseurl"]);
    		if(Mage::app()->getStore()->isCurrentlySecure() && $parsedUrl["scheme"] == 'http'){
    			$this->_data ['baseurl'] = 'https'. ltrim($this->_data ['baseurl'], 'http');
    		}
    		return rtrim($this->_data ['baseurl'], '/') . '/';
	    }else{
	        Mage::throwException(Mage::helper('joomla')->__('The Joomla configuration has not been correctly defined.'));
	    }
	}
	
	function getSecretKey(){
	    if(isset($this->_data['secret_key'])){
	        return $this->_data['secret_key'];
	    }else{
	        Mage::throwException(Mage::helper('joomla')->__('The Joomla secret Key has not been defined in the configuration.'));
	    }
	}
	
	function isCacheActivated(){
		return ($this->_data['cache'])?$this->_data['cache']:'';
	}
	
	function getData($key = null){
		if($key != null && isset($this->_data[$key])){
			return $this->_data[$key];
		}else{
			return null;
		}
	}
}