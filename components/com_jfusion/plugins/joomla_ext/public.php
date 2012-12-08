<?php

/**
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage JoomlaExt 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
/**
 * load the common Joomla JFusion plugin functions
 */
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jplugin.php';

/**
 * JFusion Public Class for an external Joomla database
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage JoomlaExt 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionPublic_joomla_ext extends JFusionPublic {
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */    
    function getJname() {
        return 'joomla_ext';
    }
    function getRegistrationURL() {
        return JFusionJplugin::getRegistrationURL($this->getJname());
    }
    function getLostPasswordURL() {
        return JFusionJplugin::getLostPasswordURL($this->getJname());
    }
    function getLostUsernameURL() {
        return JFusionJplugin::getLostUsernameURL($this->getJname());
    }
    /************************************************
    * Functions For JFusion Who's Online Module
    ***********************************************/
    function getOnlineUserQuery($limit) {
        return JFusionJplugin::getOnlineUserQuery($limit);
    }
    function getNumberOnlineGuests() {
        return JFusionJplugin::getNumberOnlineGuests();
    }
    function getNumberOnlineMembers() {
        return JFusionJplugin::getNumberOnlineMembers();
    }
    /**
     * Update the language front end param in the account of the user if this one changes it
     * NORMALLY THE LANGUAGE SELECTION AND CHANGEMENT FOR JOOMLA IS PROVIDED BY THIRD PARTY LIKE JOOMFISH
     *
     * @todo - to implement after the release 1.1.2
     *
     * @param object $userinfo
     * @return array status
     */
    function setLanguageFrontEnd($userinfo) {
        return JFusionJplugin::setLanguageFrontEnd($userinfo);
    }
}
