<?php

/**
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage Magento 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.curl.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.factory.php';
/**
 * JFusion Public Class for Magento 1.1
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage Magento 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionPublic_magento extends JFusionPublic {
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname() 
    {
        return 'magento';
    }
    function getRegistrationURL() {
        return 'index.php/customer/account/create/';
    }
    function getLostPasswordURL() {
        return 'index.php/customer/account/forgotpassword/';
    }
    function getLostUsernameURL() {
        return 'index.php/customer/account/forgotpassword/'; // not available in Magento, map to lostpassword
	}
        
    function setLanguageFrontEnd($userinfo = null) {
        $status = array('error' => '', 'debug' => '');
        // The language is selected by the library magelib when the magento framework is started
        /*if (JPluginHelper::isEnabled('system', 'magelib')) {
            $status['debug'] = JText::_('STEP_SKIPPED_MAGELIB_INSTALLED');
            return $status;
        }*/
        $JLang = JFactory::getLanguage();
        $cookies_to_set = array();
        $params = JFusionFactory::getParams($this->getJname());
        /**
         * To store the good information in the cookie concerning the language
         * We need to get the code of the store view which should correspond to the language selected from Joomla
         * Example: the store view code for english is 'en' but it could be 'english' or 'eng' (we can't give code
         * as fr-FR or en-EN, punctuation is not allowed in magento)
         * so we need to get this information from the admin plugin because from magento it's not
         * possible to know which store view is for which language
         * Then in joomla the english code is en-GB but in the store view code of magento it's 'en'
		 **/

        $language_store_view = $params->get('language_store_view', '');
        if (strlen($language_store_view) <= 0) {
            $status['debug'] = JText::_('NO_STORE_LANGUAGE_LIST');
            return $status;
        }
        // we define and set the cookie 'store'
        $langs = explode(";", $language_store_view);
        foreach ($langs as $lang) {
            $codes = explode("=", $lang);
            $joomla_code = $codes[0];
            $store_code = $codes[1];
            if ($joomla_code == $JLang->getTag()) {
                $cookies_to_set[0] = array("store=$store_code");
                break;
            }
        }
        $curl_options['cookiedomain'] = $params->get('cookie_domain');
        $curl_options['cookiepath'] = $params->get('cookie_path');
        $curl_options['expires'] = $params->get('cookie_expires');
        $curl_options['secure'] = $params->get('secure');
        $curl_options['httponly'] = $params->get('httponly');
        $status = JFusionCurl::setmycookies($status, $cookies_to_set, $curl_options['cookiedomain'], $curl_options['cookiepath'], $curl_options['expires'], $curl_options['secure'], $curl_options['httponly']);
        return $status;
    }
}
