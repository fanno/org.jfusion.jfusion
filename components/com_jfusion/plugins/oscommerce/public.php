<?php

/**
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage osCommerce
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage osCommerce
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionPublic_oscommerce extends JFusionPublic
{
	/**
	 * returns the name of this JFusion plugin
	 * @return string name of current JFusion plugin
	 */
	function getJname()
	{
		return 'oscommerce';
	}
	function getRegistrationURL() {
		$params = JFusionFactory::getParams($this->getJname());
		$osCversion = $params->get('osCversion');
		switch ($osCversion) {
			case 'osc2':
			case 'osc3':
			case 'oscxt':
			case 'oscmax':
			case 'oscseo':
				return 'login';
			case 'osczen':
				return 'index.php?main_page=login';
		}
	}
	function getLostPasswordURL() {
		$params = JFusionFactory::getParams($this->getJname());
		$osCversion = $params->get('osCversion');
		switch ($osCversion) {
			case 'osc2':
			case 'oscmax':
				return 'password_forgotten';
			case 'osc3':
				return 'account.php?password_forgotten';
			case 'osczen':
				return 'index.php?main_page=password_forgotten';
			case 'oscxt':
			case 'oscseo':
				return 'cpassword_double_opt.php';
		}
	}
	function getLostUsernameURL() {
		$params = JFusionFactory::getParams($this->getJname());
		$osCversion = $params->get('osCversion');
		switch ($osCversion) {
			case 'osc2':
			case 'oscmax':
				return 'password_forgotten'; //  not supported
			case 'osc3':
				return 'account.php?password_forgotten';
			case 'osczen':
				return 'index.php?main_page=password_forgotten';
			case 'oscxt':
			case 'oscseo':
				return 'cpassword_double_opt.php';
		}
	}
}
?>