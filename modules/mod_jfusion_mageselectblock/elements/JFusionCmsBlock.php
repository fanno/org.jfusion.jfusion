<?php
/**
 * @package JFusion
 * @subpackage Elements
 * @author JFusion development team
 * @copyright Copyright (C) 2009 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
// Check to ensure this file is included in Joomla!
defined ( '_JEXEC' ) or die ();

require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.factory.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jfusion.php';

/**
 * Get the block list of the cms of Magento
 * @package mod_jfusion_magecustomblock
 */
class JElementJFusionCmsBlock extends JElement {
	
	function fetchElement($name, $value, &$node, $control_name) {
		
		$output = "";
		
		//Query current selected Module Id
		$id = JRequest::getVar ( 'id', 0, 'method', 'int' );
		$cid = JRequest::getVar ( 'cid', array ($id ), 'method', 'array' );
		JArrayHelper::toInteger ( $cid, array (0 ) );
		
		$db = &JFactory::getDBO ();
		$query = 'SELECT params FROM #__modules  WHERE module = \'mod_jfusion_mageselectblock\' and id = ' . $db->Quote ( $cid [0] );
		
		$db->setQuery ( $query );
		$params = $db->loadResult ();
		$parametersInstance = new JParameter ( $params, '' );
		
		$jname = $parametersInstance->get ( 'magento_plugin', '' );
		if (! empty ( $jname )) {
			if (JFusionFunction::validPlugin ( $jname )) {
				$dbplugin = & JFusionFactory::getDatabase ( $jname );
				
				//@todo - take in charge the different stores
				$query = "SELECT block_id as value, title as name FROM #__cms_block WHERE is_active = '1' ORDER BY block_id";
				$dbplugin->setQuery ( $query );
				$rows = $dbplugin->loadObjectList ();
				if (! empty ( $rows )) {
					$output .= JHTML::_ ( 'select.genericlist', $rows, $control_name . '[' . $name . ']', 'size="1" class="inputbox"', 'value', 'name', $value );
				} else {
					$output .= $jname . ': ' . JText::_('No list');
				}
			
			} else {
				$output .= $jname . ": " . JText::_ ( 'No valid plugin' ) . "<br />";
			}
		} else {
			$output .= JText::_ ( 'No plugin selected' );
		}
		
		return $output;
	}
}