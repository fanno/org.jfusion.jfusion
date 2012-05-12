<?php

/**
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage Gallery2 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
/**
 * load the Abstract Auth Class
 */
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.abstractauth.php';
/**
 * @category  Gallery2
 * @package   JFusionPlugins
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org 
 */
class JFusionAuth_gallery2 extends JFusionAuth {
    function getJname() 
    {
        return 'gallery2';
    }	
    function generateEncryptedPassword($userinfo) {
        require JFUSION_PLUGIN_PATH . DS . $this->getJname() . DS . 'gallery2.php';
        jFusion_g2BridgeCore::loadGallery2Api($this->getJname(),false);
        $testcrypt = GalleryUtilities::md5Salt($userinfo->password_clear, $userinfo->password_salt);
        return $testcrypt;
    }
}
