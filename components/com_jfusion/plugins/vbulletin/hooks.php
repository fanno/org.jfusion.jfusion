<?php

/**
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage vBulletin
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

//force required variables into global scope
if (!isset($GLOBALS['vbulletin']) && !empty($vbulletin)) {
    $GLOBALS["vbulletin"] = & $vbulletin;
}
if (!isset($GLOBALS['db']) && !empty($db)) {
    $GLOBALS["db"] = & $db;
}

/**
 * Vbulletin hook class
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage vBulletin
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class executeJFusionHook
{
    var $vars;
    function executeJFusionHook($hook, &$vars, $key = '')
    {
        if ($hook != 'init_startup' && !defined('_VBJNAME') && empty($_POST['logintype'])) {
            die("JFusion plugins need to be updated.  Reinstall desired plugins in JFusion's config for vBulletin.");
        }

        if (!defined('_JFVB_PLUGIN_VERIFIED') && $hook != 'init_startup' && defined('_VBJNAME') && defined('_JEXEC') && empty($_POST['logintype'])) {
            define('_JFVB_PLUGIN_VERIFIED', 1);
            if (!JFusionFunction::validPlugin(_VBJNAME)) {
                die("JFusion plugin is invalid.  Reinstall desired plugins in JFusion's config for vBulletin.");
            }
        }

        //execute the hook
        $this->vars =& $vars;
        $this->key = $key;
        eval('$success = $this->' . $hook . '();');
        //if ($success) die('<pre>'.print_r($GLOBALS["vbulletin"]->pluginlist,true)."</pre>");

    }
    function init_startup()
    {
        global $vbulletin;
        if ($this->vars == "redirect" && !isset($_GET['noredirect']) && !defined('_JEXEC') && !isset($_GET['jfusion'])) {
            //only redirect if in the main forum
            if (!empty($_SERVER['PHP_SELF'])) {
                $s = $_SERVER['PHP_SELF'];
            } elseif (!empty($_SERVER['SCRIPT_NAME'])) {
                $s = $_SERVER['SCRIPT_NAME'];
            } else {
                //the current URL cannot be determined so abort redirect
                return;
            }
            $ignore = array($vbulletin->config['Misc']['admincpdir'], 'ajax.php', 'archive', 'attachment.php', 'cron.php', 'image.php', 'inlinemod', 'login.php', 'misc.php', 'mobiquo', $vbulletin->config['Misc']['modcpdir'], 'newattachment.php', 'picture.php', 'printthread.php', 'sendmessage.php');
            if (defined('REDIRECT_IGNORE')) {
                $custom_files = explode(',', REDIRECT_IGNORE);
                if (is_array($custom_files)) {
                    foreach ($custom_files as $file) {
                        if (!empty($file)) {
                            $ignore[] = trim($file);
                        }
                    }
                }
            }
            $redirect = true;
            foreach ($ignore as $i) {
                if (strpos($s, $i) !== false) {
                    //for sendmessage.php, only redirect if not sending an IM
                    if ($i == 'sendmessage.php') {
                        $do = $_GET['do'];
                        if ($do != 'im') {
                            continue;
                        }
                    }
                    $redirect = false;
                    break;
                }
            }

            if (isset($_POST['jfvbtask'])) {
                $redirect = false;
            }

            if ($redirect) {
                $filename = basename($s);
                $query = $_SERVER["QUERY_STRING"];
                if (SEFENABLED) {
                    if (SEFMODE == 1) {
                        $url = JOOMLABASEURL . "$filename/";
                        if (!empty($query)) {
                            $q = explode('&', $query);
                            foreach ($q as $k => $v) {
                                $url.= "$k,$v/";
                            }
                        }
                        if (!empty($query)) {
                            $queries = explode('&', $query);
                            foreach ($queries as $q) {
                                $part = explode('=', $q);
                                $url.= "$part[0],$part[1]/";
                            }
                        }
                    } else {
                        $url = JOOMLABASEURL . $filename;
                        $url.= (empty($query)) ? '' : "?$query";
                    }
                } else {
                    $url = JOOMLABASEURL . "&jfile={$filename}";
                    $url.= (empty($query)) ? '' : "&{$query}";
                }
                header("Location: $url");
                exit;
            }
        }
        //add our custom hooks into vbulletin's hook cache
        if (!empty($vbulletin->pluginlist) && is_array($vbulletin->pluginlist)) {
            $hooks = $this->getHooks($this->vars);
            if (is_array($hooks)) {
                foreach ($hooks as $name => $code) {
                    if ($name == 'global_setup_complete') {
                        $depracated =  (version_compare($vbulletin->options['templateversion'], '4.0.2') >= 0) ? 1 : 0;
                        if ($depracated) {
                            $name = 'global_bootstrap_complete';
                        }
                    }

                    if (isset($vbulletin->pluginlist[$name])) {
                        $vbulletin->pluginlist[$name].= "\n$code";
                    } else {
                        $vbulletin->pluginlist[$name] = $code;
                    }
                }
            }
        }

        return true;
    }
    function getHooks($plugin)
    {
        global $hookFile;

        if (empty($hookFile) && defined('JFUSION_VB_HOOK_FILE')) {
            //as of JFusion 1.6
            $hookFile = JFUSION_VB_HOOK_FILE;
        }

        //we need to set up the hooks
        if ($plugin == "frameless") {
            //retrieve the hooks that jFusion will use to make vB work framelessly
            $hookNames = array("album_picture_complete", "global_start", "global_complete", "global_setup_complete", "header_redirect", "logout_process", "member_profileblock_fetch_unwrapped", "redirect_generic", "xml_print_output");
        } elseif ($plugin == "duallogin") {
            //retrieve the hooks that vBulletin will use to login to Joomla
            $hookNames = array("global_setup_complete", "login_verify_success", "logout_process");
            define('DUALLOGIN', 1);
        } elseif ($plugin == 'jfvbtask') {
            $hookNames = array('global_setup_complete');
        } else {
            $hookNames = array();
        }
        $hooks = array();

        foreach ($hookNames as $h) {
            //certain hooks we want to call directly such as global variables
            if ($h == "profile_editoptions_start") {
                $hooks[$h] = 'global $stylecount;';
            } else {
                if ($h == "album_picture_complete") $toPass = '$vars =& $pictureinfo; ';
                elseif ($h == "global_complete") $toPass = '$vars =& $output; ';
                elseif ($h == "header_redirect") $toPass = '$vars =& $url;';
                elseif ($h == "member_profileblock_fetch_unwrapped") $toPass = '$vars =& $prepared;';
                elseif ($h == "redirect_generic") $toPass = '$vars = array(); $vars["url"] =& $url; $vars["js_url"] =& $js_url; $vars["formfile"] =& $formfile;';
                elseif ($h == "xml_print_output") $toPass = '$vars = & $this->doc;';
                else $toPass = '$vars = null;';
                $hooks[$h] = 'include_once \'' . $hookFile . '\'; ' . $toPass . ' $jFusionHook = new executeJFusionHook(\'' . $h . '\', $vars, \''. $this->key . '\');';
            }
        }
        return $hooks;
    }
    /**
     * HOOK FUNCTIONS
     */
    function album_picture_complete()
    {
        global $vbulletin;
        $start = strpos($this->vars['pictureurl'], '/picture.php');
        $tempURL = $vbulletin->options['bburl'] . substr($this->vars['pictureurl'], $start);
        $this->vars['pictureurl'] = $tempURL;
        return true;
    }
    function global_complete()
    {
        if (!defined('_JEXEC')) {
            return;
        }

        global $vbulletin;
        //create cookies to allow direct login into vb frameless
        /*
        if ($vbulletin->userinfo['userid'] != 0 && empty($vbulletin->GPC[COOKIE_PREFIX . 'userid'])) {
            if ($vbulletin->GPC['cookieuser']) {
                $expire = 60 * 60 * 24 * 365;
            } else {
                $expire = 0;
            }
            JFusionCurl::addCookie(COOKIE_PREFIX . 'userid', $vbulletin->userinfo['userid'], $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], false, true);
            JFusionCurl::addCookie(COOKIE_PREFIX . 'password', md5($vbulletin->userinfo['password'] . COOKIE_SALT), $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], false, true);
        }
        */
        //we need to update the session table
        $vdb = & JFusionFactory::getDatabase(_VBJNAME);
        if (!empty($vdb)) {
            $vars = & $vbulletin->session->vars;
            if ($vbulletin->session->created) {
                $bypass = ($vars[bypass]) ? 1 : 0;
                $query = "INSERT IGNORE INTO #__session
                            (sessionhash, userid, host, idhash, lastactivity, location, styleid, languageid, loggedin, inforum, inthread, incalendar, badlocation, useragent, bypass, profileupdate) VALUES
                            ({$vdb->Quote($vars[dbsessionhash]) },$vars[userid],{$vdb->Quote($vars[host]) },{$vdb->Quote($vars[idhash]) },$vars[lastactivity],{$vdb->Quote($vars[location]) },$vars[styleid],$vars[languageid],
                            $vars[loggedin],$vars[inforum],$vars[inthread],$vars[incalendar],$vars[badlocation],{$vdb->Quote($vars[useragent]) },$bypass,$vars[profileupdate])";
            } else {
                $query = "UPDATE #__session SET lastactivity = $vars[lastactivity], inforum = $vars[inforum], inthread = $vars[inthread], incalendar = $vars[incalendar], badlocation = $vars[badlocation]
                            WHERE sessionhash = {$vdb->Quote($vars[dbsessionhash]) }";
            }
            $vdb->setQuery($query);
            $vdb->query();
        }
        //we need to perform the shutdown queries that mark PMs read, etc
        if (is_array($vbulletin->db->shutdownqueries)) {
            foreach ($vbulletin->db->shutdownqueries AS $name => $query) {
                if (!empty($query) AND ($name !== 'pmpopup' OR !defined('NOPMPOPUP'))) {
                    $vdb->setQuery($query);
                    $vdb->query();
                }
            }
        }
        //echo the output and return an exception to allow Joomla to continue
        echo trim($this->vars, "\n\r\t.");
        Throw new Exception("vBulletin exited.");
    }
    function global_setup_complete()
    {
        if (!empty($_POST['jfvbtask'])) {
            //run the api task
            global $vbulletin;
            $jfaction = new JFvBulletinTask($vbulletin, $this->key);
            $response = $jfaction->performTask($_POST['jfvbtask']);
            $jfaction->outputResponse($response);
        } elseif (defined('_JEXEC')) {
            //If Joomla SEF is enabled, the dash in the logout hash gets converted to a colon which must be corrected
            global $vbulletin, $show, $vbsefenabled, $vbsefmode;
            $vbulletin->GPC['logouthash'] = str_replace(':', '-', $vbulletin->GPC['logouthash']);
            //if sef is enabled, we need to rewrite the nojs link
            if ($vbsefenabled == 1) {
                if ($vbsefmode == 1) {
                    $uri = & JURI::getInstance();
                    $url = $uri->toString();
                    $show['nojs_link'] = $url;
                    $show['nojs_link'].= (substr($url, -1) != '/') ? '/nojs,1/' : 'nojs,1/';
                } else {
                    $jfile = (JRequest::getVar('jfile', false)) ? JRequest::getVar('jfile') : 'index.php';
                    $show['nojs_link'] = "$jfile" . "?nojs=1";
                }
            }
        }
        return true;
    }
    function global_start()
    {
        //lets rewrite the img urls now while we can
        global $stylevar, $vbulletin;
        //check for trailing slash
        $DS = (substr($vbulletin->options['bburl'], -1) == '/') ? "" : "/";
        if(!empty($stylevar)) {
            foreach ($stylevar as $k => $v) {
                if (strstr($k, 'imgdir') && strstr($v, $vbulletin->options['bburl']) === false && strpos($v, 'http') === false) {
                    $stylevar[$k] = $vbulletin->options['bburl'] . $DS . $v;
                }
            }
        }
        return true;
    }
    function header_redirect()
    {
        global $vbsefenabled, $vbsefmode, $baseURL, $integratedURL, $foruminfo, $vbulletin;
        //reworks the URL for header redirects ie header('Location: $url');
        //if this is a forum link, return without parsing the URL
        if (!empty($foruminfo['link']) && (THIS_SCRIPT != 'subscription' || $_REQUEST['do'] != 'removesubscription')) {
            return;
        }
        if (defined('_JFUSION_DEBUG')) {
            $debug = array();
            $debug['url'] = $this->vars;
            $debug['function'] = 'header_redirect';
        }
        $admincp = & $vbulletin->config['Misc']['admincpdir'];
        $modcp = & $vbulletin->config['Misc']['modcp'];
        //create direct URL for admincp, modcp, and archive
        if (strpos($this->vars, $admincp) !== false || strpos($this->vars, $modcp) !== false || strpos($this->vars, 'archive') !== false) {
            if (defined('_JFUSION_DEBUG')) {
                $debug['parsed'] = $this->vars;
                $_SESSION["jfvbdebug"][] = $debug;
            }
            if (!empty($vbsefenabled)) {
                if ($vbsefmode == 1) {
                    if (strpos($this->vars, $admincp) !== false) {
                        $pos = $admincp;
                    } elseif (strpos($this->vars, $modcp) !== false) {
                        $pos = $modcp;
                    } elseif (strpos($this->vars, 'archive') !== false) {
                        $pos = 'archive';
                    }
                    $this->vars = $integratedURL . substr($this->vars, strpos($this->vars, $pos));
                } else {
                    $this->vars = str_replace($baseURL, $integratedURL, $this->vars);
                }
            } else {
                $this->vars = str_replace(JFusionFunction::getJoomlaURL(), $integratedURL, $this->vars);
            }
            //convert &amp; to & so the redirect is correct
            $this->vars = str_replace('&amp;', '&', $this->vars);
            return true;
        }
        //let's make sure the baseURL does not have a / at the end for comparison
        $testURL = (substr($baseURL, -1) == '/') ? substr($baseURL, 0, -1) : $baseURL;
        if (strpos(strtolower($this->vars["url"]), strtolower($testURL)) === false) {
            $url = basename($this->vars);
            $url = JFusionFunction::routeURL($url, JRequest::getInt('Itemid'));
            $this->vars = $url;
        }
        //convert &amp; to & so the redirect is correct
        $this->vars = str_replace('&amp;', '&', $this->vars);

        if (defined('_JFUSION_DEBUG')) {
            $debug['parsed'] = $this->vars;
            $_SESSION["jfvbdebug"][] = $debug;
        }
        return true;
    }
    function login_verify_success()
    {
        $this->backup_restore_globals('backup');
        global $vbulletin;
        //if JS is enabled, only a hashed form of the password is available
        $password = (!empty($vbulletin->GPC['vb_login_password'])) ? $vbulletin->GPC['vb_login_password'] : $vbulletin->GPC['vb_login_md5password'];
        if (!empty($password)) {
            if (!defined('_JEXEC')) {
                $mainframe = $this->startJoomla();
            } else {
                $mainframe = JFactory::getApplication('site');
                define('_VBULLETIN_JFUSION_HOOK', true);
            }
            // do the login
            global $JFusionActivePlugin;
            $JFusionActivePlugin =  _VBJNAME;
            $baseURL = (class_exists('JFusionFunction')) ? JFusionFunction::getJoomlaURL() : JURI::root();
            $loginURL = JRoute::_($baseURL . 'index.php?option=com_user&task=login', false);
            $credentials = array('username' => $vbulletin->userinfo['username'], 'password' => $password, 'password_salt' => $vbulletin->userinfo['salt']);
            $options = array('entry_url' => $loginURL);
            //set remember me option
            if(!empty($vbulletin->GPC['cookieuser'])) {
                $options['remember'] = 1;
            }
            //creating my own vb security string for check in the function
            define('_VB_SECURITY_CHECK', md5('jfusion' . md5($password . $vbulletin->userinfo['salt'])));
            $mainframe->login($credentials, $options);
            // clean up the joomla session object before continuing
            $session = JFactory::getSession();
            $session->close();
        }
        $this->backup_restore_globals('restore');
        return true;
    }
    function logout_process()
    {
        $this->backup_restore_globals('backup');
        if (defined('_JEXEC')) {
            //we are in frameless mode and need to kill the cookies to prevent getting stuck logged in
            global $vbulletin;
            JFusionCurl::addCookie(COOKIE_PREFIX . 'userid', 0, 0, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], false, true);
            JFusionCurl::addCookie(COOKIE_PREFIX . 'password', 0, 0, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], false, true);
            //prevent global_complete from recreating the cookies
            $vbulletin->userinfo['userid'] = 0;
            $vbulletin->userinfo['password'] = 0;
        }
        if (defined('DUALLOGIN')) {
            if (!defined('_JEXEC')) {
                $mainframe = $this->startJoomla();
            } else {
                $mainframe = JFactory::getApplication('site');
                define('_VBULLETIN_JFUSION_HOOK', true);
            }
            global $JFusionActivePlugin;
            $JFusionActivePlugin =  _VBJNAME;
            // logout any joomla users
            $mainframe->logout();
            // clean up session
            $session = JFactory::getSession();
            $session->close();
        }
        $this->backup_restore_globals('restore');
        return true;
    }
    function backup_restore_globals($action)
    {
        static $vb_globals;

        if (!is_array($vb_globals)) {
            $vb_globals = array();
        }

        if ($action == 'backup') {
            foreach ($GLOBALS as $n => $v) {
                $vb_globals[$n] = $v;
            }
        } else {
            foreach ($vb_globals as $n => $v) {
                $GLOBALS[$n] = $v;
            }
        }
    }
    function member_profileblock_fetch_unwrapped()
    {
        global $vbsefmode, $vbsefenabled, $baseURL;
        static $profileurlSet;
        if (!empty($this->vars[profileurl]) && $profileurlSet !== true) {
            $uid = JRequest::getVar('u');
            if ($vbsefenabled && $vbsefmode) {
                $this->vars[profileurl] = str_replace("member.php?u=$uid", '', $this->vars[profileurl]);
            } else {
                $this->vars[profileurl] = $baseURL . "&jfile=member.php&u=$uid";
            }
            $profileurlSet = true;
        }
    }
    function redirect_generic()
    {
        global $baseURL;
        //reworks the URL for generic redirects that use JS or html meta header
        if (defined('_JFUSION_DEBUG')) {
            $debug = array();
            $debug['url'] = $this->vars["url"];
            $debug['function'] = 'redirect_generic';
        }
        //let's make sure the baseURL does not have a / at the end for comparison
        $testURL = (substr($baseURL, -1) == '/') ? substr($baseURL, 0, -1) : $baseURL;
        if (strpos(strtolower($this->vars["url"]), strtolower($testURL)) === false) {
            $url = basename($this->vars["url"]);
            $url = JFusionFunction::routeURL($url, JRequest::getInt('Itemid'));

            //convert &amp; to & so the redirect is correct
            $url = str_replace('&amp;', '&', $url);
            $this->vars["url"] = $url;
            $this->vars["js_url"] = addslashes_js($this->vars["url"]);
            $this->vars["formfile"] = $this->vars["url"];
        }
        if (defined('_JFUSION_DEBUG')) {
            $debug['parsed'] = $this->vars['url'];
            $_SESSION["jfvbdebug"][] = $debug;
        }
        return true;
    }

    function xml_print_output()
    {
        if (!defined('_JEXEC')) {
            $mainframe = $this->startJoomla();
        }

        //parse AJAX output
        $public = & JFusionFactory::getPublic(_VBJNAME);
        $params = & JFusionFactory::getParams(_VBJNAME);

        $jdata = new stdClass();
        $jdata->body = & $this->vars;
        $jdata->Itemid = $params->get("plugin_itemid");
        //Get the base URL to the specific JFusion plugin
        $jdata->baseURL = JFusionFunction::getPluginURL($jdata->Itemid);
        //Get the integrated URL
        $jdata->integratedURL = $params->get('source_url');
        $public->parseBody($jdata);
    }

    function startJoomla()
    {
        define('_VBULLETIN_JFUSION_HOOK', true);
        define('_JEXEC', true);
        define('DS', DIRECTORY_SEPARATOR);
        // load joomla libraries
        require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
		define('_JREQUEST_NO_CLEAN', true); // we dont want to clean variables as it can "commupt" them for some applications, it also clear any globals used...
       	require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';
//		include_once JPATH_LIBRARIES . DS . 'import.php'; //include not require, so we only get it if its there ...
//      require_once JPATH_LIBRARIES . DS . 'loader.php';
        jimport('joomla.base.object');
        jimport('joomla.factory');
        jimport('joomla.filter.filterinput');
        jimport('joomla.error.error');
        jimport('joomla.event.dispatcher');
        jimport('joomla.event.plugin');
        jimport('joomla.plugin.helper');
        jimport('joomla.utilities.arrayhelper');
        jimport('joomla.environment.uri');
        jimport('joomla.environment.request');
        jimport('joomla.user.user');
        jimport('joomla.html.parameter');
        jimport('joomla.version');
        // JText cannot be loaded with jimport since it's not in a file called text.php but in methods
        JLoader::register('JText', JPATH_BASE . DS . 'libraries' . DS . 'joomla' . DS . 'methods.php');
        JLoader::register('JRoute', JPATH_BASE . DS . 'libraries' . DS . 'joomla' . DS . 'methods.php');

        //include the JFusion factory and function file
        $functionFile = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jfusion.php';
        $factoryFile = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.factory.php';
        $curlFile = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.curl.php';
        if (file_exists($functionFile) && file_exists($factoryFile) && file_exists($curlFile)) {
            require_once $factoryFile;
            require_once $functionFile;
            require_once $curlFile;
        }
        $mainframe = JFactory::getApplication('site');
        $GLOBALS['mainframe'] = & $mainframe;
        return $mainframe;
    }
}

class JFvBulletinTask {
    private $key;
    private $data;
    protected $vbulletin;
    protected $xml;

    function __construct(&$vbulletin, $key) {
        if (empty($key)) {
            $this->outputResponse(array("error" => "Missing key!"));
        }
        $this->key = $key;
        $this->vbulletin =& $vbulletin;
    }

    function performTask($task) {
        if (isset($_POST['jfvbdata'])) {
            $this->data = $this->decryptApiData($_POST['jfvbdata']);
        } else {
            $this->outputResponse(array("error" => "Missing data!"));
        }

        if (method_exists($this, "_{$task}")) {
            //perform the task
            $this->{"_{$task}"}();
        } else {
            //respond with error
            $this->outputResponse(array("error" => "Task does not exist!"));
        }
    }

    function decryptApiData($data) {
    	if (function_exists('mcrypt_decrypt')) {
	        $decrypted_data = @unserialize(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($data), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	        if (!is_array($decrypted_data)) {
	            $this->outputResponse(array("error" => "Data corrupted!"));
	        } elseif ($decrypted_data['jfvbkey'] != $this->key) {
	            //key doesn't match
	            $this->outputResponse(array("error" => "Bad key!"));
	        }
    	} else {
    		$this->outputResponse(array("error" => "mcrypt_decrypt Missing"));
    	}
        return $decrypted_data;
    }

    function outputResponse($data = array("error" => "Access Denied!")) {
        if (!empty($data['errors'])) {
            $data['success'] = 0;
        } elseif (!empty($data['error'])) {
            $data['success'] = 0;
            $data['errors'] = array($data['error']);
            unset($data['error']);
        }

        $this->xml = new SimpleXMLElement('<root/>');
        array_walk($data, array ($this, 'addDataToXML'));
        $xml_string = $this->xml->asXML();
        die($xml_string);
    }

    function addDataToXML ($val, $key) {
        if ($key == 'errors') {
            if (count($val) === 1) {
                //must make sure errors is an array
                $this->xml->addChild('errors', 'API Errors');
            }
            foreach ($val as $err) {
                $this->xml->addChild("errors", $err);
            }
        } else {
            $this->xml->addChild($key, $val);
        }
    }

    function convertUserData($existinguser)
    {
        $userinfo = array('userid' => $existinguser->userid, 'username' => $existinguser->username, 'email' => $existinguser->email, 'password' => $existinguser->password);
        return $userinfo;
    }

    function _createUser() {
        $userinfo =& $this->data['userinfo'];
        $defaultgroup =& $this->data['defaultgroup'];
        $usergroups =& $this->data['usergroups'];

        //create the new user
        $userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
        $userdm->set('username', $userinfo->username);
        $userdm->set('email', $userinfo->email);
        $userdm->set('password', $userinfo->password_clear);

        if (is_array($usergroups)) {
            $userdm->set('usergroupid', $defaultgroup);
            $userdm->set('displaygroupid', $usergroups[$userinfo->group_id]['displaygroup']);
            $userdm->set('membergroupids', $usergroups[$userinfo->group_id]['membergroups']);
        } else {
            $userdm->set('usergroupid', $defaultgroup);
            $userdm->set('displaygroupid', 0);
        }

        $userdm->set('usertitle', $userinfo->usertitle);

        //set the timezone
        if (isset($userinfo->timezone)) {
            $timezone = $userinfo->timezone;
            $userdm->set('timezoneoffset', $timezone);
        }

        //performs some final VB checks before saving
        $userdm->pre_save();
        if (empty($userdm->errors)) {
            $userdmid = $userdm->save();
            $response = array(
            	"new_id" => $userdmid,
                "success" => 1
            );
            $this->outputResponse($response);
        } else {
            $this->outputResponse(array("errors" => $userdm->errors));
        }
    }

    function _deleteUser() {
        $userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
        $existinguser = $this->convertUserData($this->data['userinfo']);
        $userdm->set_existing($existinguser);
        $userdm->delete();
	    if(!empty($userdm->errors)){
            $this->outputResponse(array("errors" => $userdm->errors));
	    } else {
            $response = array("success" => 1);
            $this->outputResponse($response);
	    }
    }

    function _updateUsergroup() {
        $existinguser =& $this->data['existinguser'];
        $userinfo =& $this->data['userinfo'];

        $userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
        $vbuserinfo = $this->convertUserData($existinguser);
        $userdm->set_existing($vbuserinfo);

        if (empty($this->data['aec'])) {
            $usergroups =& $this->data['usergroups'];
            $defaultgroup =& $usergroups[$userinfo->group_id]['defaultgroup'];
            $displaygroup =& $usergroups[$userinfo->group_id]['displaygroup'];
            $membergroups =& $usergroups[$userinfo->group_id]['membergroups'];
        } else {
            $defaultgroup = $membergroups = $displaygroup = $this->data['aecgroupid'];
        }

        $userdm->set('usergroupid', $defaultgroup);
        $userdm->set('membergroupids', $membergroups);
        $userdm->set('usertitle',$this->data['usertitle']);

        //performs some final VB checks before saving
        $userdm->pre_save();
        if (empty($userdm->errors)) {
            $userdm->save();

            //now save the displaygroup
            if (!empty($displaygroup)) {
                unset($userdm);
                $userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
                $userdm->set_existing($vbuserinfo);
                $userdm->set('displaygroupid', $displaygroup);
                $userdm->pre_save();
                if (empty($userdm->errors)) {
                    $userdm->save();
                    $response = array("success" => 1);
                    $this->outputResponse($response);
                } else {
                    $this->outputResponse(array("errors" => $userdm->errors));
                }
            }
        } else {
            $this->outputResponse(array("errors" => $userdm->errors));
        }
    }

    function _updateEmail() {
		$userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
		$userdm->set_existing($this->convertUserData($this->data['existinguser']));
		$userdm->set('email', $this->data['userinfo']->email);
		//performs some final VB checks before saving
		$userdm->pre_save();
	    if(empty($userdm->errors)){
			$userdm->save();
            $response = array("success" => 1);
            $this->outputResponse($response);
	    } else {
            $this->outputResponse(array("errors" => $userdm->errors));
	    }
    }

    function _unblockUser() {
        $userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
        $existinguser =& $this->data['existinguser'];
        $bannedgroup &= $this->data['bannedgroup'];
        $defaultgroup =& $this->data['defaultgroup'];
        $displaygroup =& $this->data['displaygroup'];

        $userinfo = $this->convertUserData($existinguser);
        $userdm->set_existing($userinfo);

        if (!empty($this->data['result'])) {
            $result =& $this->data['result'];
            //set the user title
            if ($result->customtitle && $result->usertitle!=$result->bantitle) {
                $usertitle = $result->usertitle;
            } else if (!empty($result->usertitle)) {
                $usertitle = $result->usertitle;
            } else {
                $usertitle = $this->data['defaulttitle'];
            }
            $userdm->set('usertitle', $usertitle);
            $userdm->set('posts', $existinguser->posts);
            // This will activate the rank update

            //keep user from getting stuck as banned
            if ($result->usergroupid==$bannedgroup) {
                $usergroupid = $defaultgroup;
            } else {
                $usergroupid = $result->group_id;
            }
            if ($result->displaygroupid==$bannedgroup) {
                $displaygroupid = $displaygroup;
            } else {
                $displaygroupid = $result->displaygroupid;
            }

            $userdm->set('usergroupid', $usergroupid);
            $userdm->set('displaygroupid', $displaygroupid);
            $userdm->set('customtitle', $result->customtitle);
        } else {
            $userdm->set('usergroupid', $defaultgroup);
            $userdm->set('displaygroupid', $displaygroup);
        }

        //performs some final VB checks before saving
        $userdm->pre_save();
	    if(empty($userdm->errors)){
			$userdm->save();
            $response = array("success" => 1);
            $this->outputResponse($response);
	    } else {
            $this->outputResponse(array("errors" => $userdm->errors));
	    }
    }

    function _inactivateUser() {
        $userdm =& datamanager_init('User', $this->vbulletin, ERRTYPE_SILENT);
        $existinguser =& $this->data['existinguser'];
        $vbuser = $this->convertUserData($existinguser);
        $userdm->set_existing($vbuser);
        $userdm->set_bitfield('options', 'noactivationmails', 0);
        //performs some final VB checks before saving
        $userdm->pre_save();
	    if(empty($userdm->errors)){
			$userdm->save();
			$response = array("success" => 1);
            $this->outputResponse($response);
	    } else {
            $this->outputResponse(array("errors" => $userdm->errors));
	    }
    }

    function _createThread() {
        $threaddm = & datamanager_init('Thread_FirstPost', $this->vbulletin, ERRTYPE_SILENT, 'threadpost');

        $foruminfo = fetch_foruminfo($this->data['forumid'], false);
        $threaddm->set_info('forum', $foruminfo);
        $threaddm->set('forumid', $foruminfo['forumid']);
        $threaddm->set('userid', $this->data['userid']);
        $threaddm->set('title', $this->data['title']);
        $threaddm->set('pagetext', trim($this->data['text']));
        $threaddm->set('allowsmilie', $foruminfo['allowsmilies']);
        $threaddm->set('showsignature', 1);
        $threaddm->set('ipaddress', $this->data['ipaddress']);
        $threaddm->set('visible', 1);
        $threaddm->set_info('parseurl', 1);
        $timestamp = ($this->data['timestamp'] == 'timestamp') ? TIMENOW : $this->data['timestamp'];
        $threaddm->set('dateline', $timestamp);
        $threaddm->pre_save();
        if (!empty($threaddm->errors)) {
            $this->outputResponse(array("errors" => $threaddm->errors));
        } else {
            $threadid = $threaddm->save();
            $postid = $threaddm->fetch_field('firstpostid');
            $response = array(
            	"new_id" => $threadid,
                "firstpostid" => $postid,
                "success" => 1
            );
            $this->outputResponse($response);
        }
    }

    function _createPost() {
        $threadinfo = fetch_threadinfo($this->data['ids']->threadid);
		$foruminfo = fetch_foruminfo($this->data['ids']->forumid, false);

        $postdm = & datamanager_init('Post', $this->vbulletin, ERRTYPE_SILENT, 'threadpost');
        $postdm->set_info('forum', $foruminfo);
        $postdm->set_info('thread', $threadinfo);
        $userinfo =& $this->data['userinfo'];
        $postdm->set_info('user', $userinfo);
        $postdm->set('userid', $userinfo['userid']);
        if ($guest) {
            $postdm->set('username', $userinfo['username']);
			if($this->data['post_approved']) {
                $postdm->set('visible', 0);
            } else {
                $postdm->set('visible', 1);
            }
        } else {
            $postdm->set('visible', 1);
        }
        $postdm->setr('parentid', $this->data['ids']->postid);
        $postdm->setr('threadid', $this->data['ids']->threadid);
        $postdm->setr('ipaddress', $this->data['ipaddress']);
        $postdm->set('dateline', TIMENOW);
        $postdm->setr('pagetext', $this->data['text']);
        $postdm->set('title', $this->data['title']);
        $postdm->set('allowsmilie', $foruminfo['allowsmilies']);
        $postdm->set('showsignature', 1);
        $postdm->pre_save();

        if (!empty($postdm->errors)) {
            $this->outputResponse(array("errors" => $postdm->errors));
        } else {
            $id = $postdm->save();
            $response = array(
            	"new_id" => $id,
                "success" => 1
            );
            $this->outputResponse($response);
        }
    }

    function _updateThread() {
        $ids =& $this->data['existingthread'];

        $postinfo = array();
        $postinfo['postid'] = $ids->postid;
        $postinfo['threadid'] = $ids->threadid;
        $postinfo['ipaddress'] = $this->data['ipaddress'];
        $postinfo['dateline'] = TIMENOW;

        $threadinfo = fetch_threadinfo($ids->threadid);
		$foruminfo = fetch_foruminfo($ids->forumid, false);

        $postdm = & datamanager_init('Post', $this->vbulletin, ERRTYPE_SILENT, 'threadpost');
        $postdm->set_existing($postinfo);
        $postdm->set_info('forum', $foruminfo);
        $postdm->set_info('thread', $threadinfo);
        $postdm->setr('pagetext', $this->data['text']);
        $postdm->setr('title', $this->data['title']);
        $parseurl = (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) AND $foruminfo['allowbbcode']);
        $postdm->set_info('parseurl', $parseurl);
        $postdm->pre_save();
        if (!empty($postdm->errors)) {
            $this->outputResponse(array("errors" => $postdm->errors));
        } else {
            $postdm->save();
            //update the thread's title
            $threaddm = & datamanager_init('Thread', $this->vbulletin, ERRTYPE_SILENT, 'threadpost');
            $threaddm->set_existing($threadinfo);
            $threaddm->set('title', $this->data['title']);
            $threaddm->save();

            $response = array("success" => 1);
            $this->outputResponse($response);
        }
    }
}
?>