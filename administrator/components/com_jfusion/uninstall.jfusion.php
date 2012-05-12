<?php

/**
 * Uninstaller file
 *
 * PHP version 5
 *
 * @category  JFusion
 * @package   Install
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Get the extension id
 * Grabbed this from the JPackageMan installer class with modification
 *
 * @param string $type        type
 * @param int    $id          id
 * @param string $group       group
 * @param string $description description
 *
 * @return unknown_type
 */

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');

function _uninstallPlugin($type, $id, $group, $description)
{
    $db = JFactory::getDBO();
    $result = $id;
	$jversion = new JVersion;
    $version = $jversion->getShortVersion();
    if(version_compare($version, '1.6') >= 0) {
        switch ($type) {
        case 'plugin':
            $db->setQuery("SELECT extension_id FROM #__extensions WHERE folder = '$group' AND element = '$id'");
            $result = $db->loadResult();
            break;
        case 'module':
            $db->setQuery("SELECT extension_id FROM #__extensions WHERE element = '$id'");
            $result = $db->loadResult();
            break;
        }
    } else {
        switch ($type) {
        case 'plugin':
            $db->setQuery("SELECT id FROM #__plugins WHERE folder = '$group' AND element = '$id'");
            $result = $db->loadResult();
            break;
        case 'module':
            $db->setQuery("SELECT id FROM #__modules WHERE module = '$id'");
            $result = $db->loadResult();
            break;
        }
    }
    if ($result) {
        $tmpinstaller = new JInstaller();
        $installer_result = $tmpinstaller->uninstall($type, $result, 0);
        if (!$result) {
            ?>
            <table style="background-color:#f9ded9;width:100%;"><tr style="height:30px">
            <td><font size="2"><b><?php echo JText::_('UNINSTALL') . ' ' . $description . ' ' . JText::_('FAILED'); ?></b></font></td></tr></table>
            <?php
        } else {
            ?>
            <table style="background-color:#d9f9e2;width:100%;"><tr style="height:30px">
            <td><font size="2"><b><?php echo JText::_('UNINSTALL') . ' ' . $description . ' ' . JText::_('SUCCESS'); ?></b></font></td></tr></table>
            <?php
        }
    }
}

function com_uninstall() {
    $return = true;
    echo '<h2>JFusion ' . JText::_('UNINSTALL') . '</h2><br/>';

    //restore the normal login behaviour
    $db = JFactory::getDBO();

	$jversion = new JVersion;
    $version = $jversion->getShortVersion();
    if(version_compare($version, '1.6') >= 0){
        $db->setQuery('UPDATE #__extensions SET enabled = 1 WHERE element =\'joomla\' and folder = \'authentication\'');
        $db->Query();
        $db->setQuery('UPDATE #__extensions SET enabled = 1 WHERE element =\'joomla\' and folder = \'user\'');
        $db->Query();
    } else {
        $db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'joomla\' and folder = \'authentication\'');
        $db->Query();
        $db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'joomla\' and folder = \'user\'');
        $db->Query();
    }

    echo '<table style="background-color:#d9f9e2;" width ="100%"><tr style="height:30px">';
    echo '<td><font size="2"><b>' . JText::_('NORMAL_JOOMLA_BEHAVIOR_RESTORED') . '</b></font></td></tr></table>';

    //uninstall the JFusion plugins
    _uninstallPlugin('plugin', 'jfusion', 'user', 'JFusion User Plugin');
    _uninstallPlugin('plugin', 'jfusion', 'authentication', 'JFusion Authentication Plugin');
    _uninstallPlugin('plugin', 'jfusion', 'search', 'JFusion Search Plugin');
    _uninstallPlugin('plugin', 'jfusion', 'content', 'JFusion Discussion Bot Plugin');
    _uninstallPlugin('plugin', 'jfusion', 'system', 'JFusion System Plugin');

    //uninstall the JFusion Modules
    _uninstallPlugin('module', 'mod_jfusion_login', '', 'JFusion Login Module');
    _uninstallPlugin('module', 'mod_jfusion_activity', '', 'JFusion Activity Module');
    _uninstallPlugin('module', 'mod_jfusion_user_activity', '', 'JFusion User Activity Module');
    _uninstallPlugin('module', 'mod_jfusion_whosonline', '', 'JFusion Whos Online Module');

    //see if any mods from jfusion plugins need to be removed
    $plugins = JFusionFactory::getPlugins();
    foreach($plugins as $plugin) {
    	$JFusionPlugin =& JFusionFactory::getAdmin($plugin->name);
    	$result = $JFusionPlugin->uninstall();
    	if (is_array($result)) {
    	    $success = $result[0];
    	    $reasons = $result[1];
    	} else {
    	    $success = $result;
    	}
    	if (!$success) {
            echo '<table style="background-color:#f9ded9;" width ="100%"><tr style="height:30px">' . "\n";
            echo '<td><font size="2"><b>'.JText::_('UNINSTALL') . ' ' . $plugin->name . ' ' . JText::_('FAILED') . ': </b></font></td></tr>' . "\n";
            if (is_array($reasons)) {
                foreach ($reasons as $r) {
                    echo '<td style="padding-left: 15px;">'.$r.'</td></tr>' . "\n";
                }
            } elseif (!empty($reasons)) {
                    echo '<td style="padding-left: 15px;">'.$reasons.'</td></tr>' . "\n";
            }
            echo "</table>\n";
    	    $return = false;
    	}
    }

    //remove the jfusion tables.
    $db = JFactory::getDBO();
    $query = "DROP TABLE #__jfusion";
    $db->setQuery($query);
    if (!$db->Query()){
        echo $db->stderr() . "<br />";
        $return = false;
    }

    $query = "DROP TABLE #__jfusion_sync";
    $db->setQuery($query);
    if (!$db->Query()){
        echo $db->stderr() . "<br />";
        $return = false;
    }

    $query = "DROP TABLE #__jfusion_sync_details";
    $db->setQuery($query);
    if (!$db->Query()){
        echo $db->stderr() . "<br />";
        $return = false;
    }

    $query = "DROP TABLE #__jfusion_users";
    $db->setQuery($query);
    if (!$db->Query()){
        echo $db->stderr() . "<br />";
        $return = false;
    }

    $query = "DROP TABLE #__jfusion_users_plugin";
    $db->setQuery($query);
    if (!$db->Query()){
        echo $db->stderr() . "<br />";
        $return = false;
    }

    $query = "DROP TABLE #__jfusion_discussion_bot";
    $db->setQuery($query);
    if (!$db->queryBatch()){
    	echo $db->stderr() . "<br />";
    	$return = false;
    }

    return $return;
}