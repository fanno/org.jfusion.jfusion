<?php

/**
 * This is the jfusion user plugin file
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    Plugins
 * @subpackage System
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
/**
 * Load the JFusion framework if installed
 */
jimport('joomla.plugin.plugin');
$model_file = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.factory.php';
$factory_file = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jfusion.php';
if (file_exists($model_file) && file_exists($factory_file)) {
    /**
     * require the JFusion libraries
     */
    include_once $model_file;
    include_once $factory_file;
}
/**
 * JFusion System Plugin class
 *
 * @category   JFusion
 * @package    Plugins
 * @subpackage System
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class plgSystemJfusion extends JPlugin
{
    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @param object &$subject The object to observe
     * @param array  $config   An array that holds the plugin configuration
     *
     * @access protected
     * @since  1.0
     */
    function plgSystemJfusion(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage('plg_system_jfusion', JPATH_ADMINISTRATOR);
    }
    /**
     * onAfterInitialise
     *
     * This function is called by joomla framework
     *
     * @since 1.0
     * @return void
     */
    function onAfterInitialise()
    {
        //JError::raiseNotice('500','system plugin called');
        $session = JFactory::getSession();
        //initialise some vars
        ob_start();
        $refresh = false;
        $status = array();
        $task = JRequest::getVar('task');
        $status['debug'] = array();
        $status['error'] = array();
        $debug = $this->params->get('debug', 0);
        if ($debug) {
            define('DEBUG_SYSTEM_PLUGIN', 1);
        }

        //prevent endless loops
        $time = JRequest::getVar('time');
        if (!empty($time)) {
            //restore $_POST, $_FILES, and $_REQUEST data if this was a refresh
            $backup = $session->get('JFusionVarBackup', array());
            if (!empty($backup)) {
                $_POST = $_POST + $backup['post'];
                $_FILES = $_FILES + $backup['files'];
                $_REQUEST = $_REQUEST + $backup['request'];
                $session->clear('JFusionVarBackup');
                if ($debug) {
                    JError::raiseNotice('500', 'Form variables restored.');
                }
            }
            return;
        }

        //only call keepAlive if in the frontend
        $syncsessions = $this->params->get('syncsessions');
        $keepalive = $this->params->get('keepalive');
        $mainframe = JFactory::getApplication();
        if ($mainframe->isSite() && !empty($syncsessions) && $task != 'logout') {
            //for master if not joomla_int
            $master = JFusionFunction::getMaster();
            if (!empty($master) && $master->name != 'joomla_int') {
                $JFusionUser = & JFusionFactory::getUser($master->name);
                $changed = $JFusionUser->syncSessions($keepalive);
                if (!empty($changed)) {
                    if ($debug) {
                        JError::raiseNotice('500',"$master->name session changed");
                    }
                    $refresh = true;
                }
            }
            //slave plugins
            $plugins = JFusionFunction::getPlugins();
            foreach ($plugins as $plugin) {
                //only call keepAlive if the plugin is activated for dual login
                if ($plugin->dual_login) {
                    $JFusionUser = & JFusionFactory::getUser($plugin->name);
                    $changed = $JFusionUser->syncSessions($keepalive);
                    if (!empty($changed)) {
                        if ($debug) {
                            JError::raiseNotice('500',"$plugin->name session changed");
                        }
                        $refresh = true;
                    }
                }
            }
        }
        /**
         * Joomla Object language with the current information about the language loaded
         * In the purpose to reduce the load charge of Joomla and the communication with the others
         * integrated software the script is realized once the language is changed
         *
         */
        $synclanguage = $this->params->get('synclanguage');
        if (!empty($synclanguage)) {
			$this->setLanguagePluginsFrontend();
        }

        //stop output buffer
        ob_end_clean();

        //check if page refresh is needed
        if ($refresh == true) {
            $backup = array();
            $backup['post'] = $_POST;
            $backup['request'] = $_REQUEST;
            $backup['files'] = $_FILES;
            $session->set('JFusionVarBackup',$backup);
            if ($debug) {
                JError::raiseNotice('500','Refresh is true');
            }
            $uri = & JURI::getInstance();
            //add a variable to ensure refresh
            $uri->setVar('time', time());
            $link = $uri->toString();
            $mainframe = JFactory::getApplication();
            $mainframe->redirect($link);
        }
    }
    
    /**
     * Can be invoked from components, modules or else
     */
    function setLanguagePluginsFrontend() {
		$JLang = JFactory::getLanguage ();
		$session = JFactory::getSession ();
		$oldlang = $session->get ( 'oldlang' );
		if (! isset ( $oldlang ) || $oldlang != $JLang->_lang) {
			$session->set ( 'oldlang', $JLang->_lang );
			// The instance of the user is not obligatory. Without to be logged, the user can change the language of the integrated softwares
			// if those implement it.
			$userinfo = JFactory::getUser ();
			$master = JFusionFunction::getMaster ();
			$JFusionMasterPublic = & JFusionFactory::getPublic ( $master->name );
			if (method_exists ( $JFusionMasterPublic, 'setLanguageFrontEnd' )) {
				$status = $JFusionMasterPublic->setLanguageFrontEnd ( $userinfo );
				if (! empty ( $status ['error'] )) {
					//could not set the language
					JFusionFunction::raiseWarning ( $master->name . ' ' . JText::_ ( 'SET_LANGUAGEFRONTEND_ERROR' ), $status ['error'], 1 );
				}
			} else {
				$status ['debug'] [] = JText::_ ( 'METHOD_NOT_IMPLEMENTED' ) . ": " . $master->name;
			}
			$slaves = JFusionFunction::getSlaves ();
			foreach ( $slaves as $slave ) {
				$JFusionSlavePublic = & JFusionFactory::getPublic ( $slave->name );
				if (method_exists ( $JFusionSlavePublic, 'setLanguageFrontEnd' )) {
					$status = $JFusionSlavePublic->setLanguageFrontEnd ( $userinfo );
					if (! empty ( $status ['error'] )) {
						//could not set the language
						JFusionFunction::raiseWarning ( $slave->name . ' ' . JText::_ ( 'SET_LANGUAGEFRONTEND_ERROR' ), $status ['error'], 1 );
					}
				} else {
					$status ['debug'] [] = JText::_ ( 'METHOD_NOT_IMPLEMENTED' ) . ": " . $slave->name;
				}
			}
		}
	}
}