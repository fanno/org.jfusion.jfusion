<?php
/**
 * 
 *
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage MyBB
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * JFusion Authentication Class for MyBB
 * For detailed descriptions on these functions please check the model.abstractauth.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage MyBB
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionAuth_mybb extends JFusionAuth {
    function generateEncryptedPassword($userinfo) {
        //Apply myBB encryption
        $testcrypt = md5(md5($userinfo->password_salt) . md5($userinfo->password_clear));
        return $testcrypt;
    }
}
