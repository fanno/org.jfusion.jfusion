<?php
/**
 * @package JFusion
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined ( '_JEXEC' ) or die ( 'Restricted access' );

//require the constants
require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'defines.php');
require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');

jimport ( 'joomla.filesystem.file' );

//force a jfusion view if the plugin submitted form to index.php without a view input
$view = JRequest::getVar('view','plugin');

//this is needed as a safe guard in the case a plugin submits a form to index.php with an input named view that is used by the integrated software
$jfusion_views = JFolder::folders(JPATH_SITE.DS.'components'.DS.'com_jfusion'.DS.'views');
if(!in_array($view,$jfusion_views)) $view = 'plugin';

// Load the appropriate controller
$controller = JRequest::getCmd('controller', $view ); // Black magic: Get controller based on the selected view
$path = JPATH_COMPONENT . DS . 'controllers' . DS . $controller . '.php';

if (JFile::exists ( $path )) {
	// The requested controller exists and there you load it...
	require_once ($path);
}
else {
	// Hmm... an invalid controller was passed
	JError::raiseError ( '500', JText::_ ( 'Unknown controller' ) );
}

// Instanciate and execute the controller
// Note we cannot use JString here as it causes function name conflicts with phpBB
$classname = 'JFusionController' . ucfirst ( $controller );
$controller = new $classname ( );

$controller->execute ( JRequest::getCmd ( 'task' ) );

// Redirect
$controller->redirect ();