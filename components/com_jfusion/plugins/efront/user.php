<?php

/**
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * load the jplugin model
 */
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jplugin.php';
if (!class_exists('JFusionEfrontHelper')) {
   require_once 'efronthelper.php';
}
/**
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionUser_efront extends JFusionUser
{
	
	function &getUser($userinfo) {
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        //get the identifier
        list($identifier_type, $identifier) = $this->getUserIdentifier($userinfo, 'login', 'email');
        if ($identifier_type == 'login') {
            $identifier = $this->filterUsername($identifier);
        }
        
        //initialise some params
        $update_block = $params->get('update_block');
        $query = 'SELECT * FROM #__users WHERE ' . $identifier_type . ' = ' . $db->Quote($identifier);
        $db->setQuery($query);
        $result = $db->loadObject();
        if ($result)
        {      
            // change/add fields used by jFusion
            $result->userid = $result->id;
            $result->username = $result->login;
            $result->group_id = JFusionEfrontHelper::groupNameToID($result->user_type,$result->user_types_ID);
            $result->group_name = JFusionEfrontHelper::groupIdToName($result->group_id);
            $result->name = trim($result->name . ' ' . $result->surname);
            $result->registerDate = date('d-m-Y H:i:s', $result->timestamp);
            $result->activation = ($result->pending == 1) ? "1" : "";
            $result->block = !$result->active;
        }    
        return $result;
    }
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return 'efront';
    }
    function destroySession($userinfo, $options) {
        $status = array();
        $status['error'] = array();
        $status['debug'] = array();
        if (isset($options['remember'])) {
            if ($options['remember']) {
                 $status['error'] = false;
                 return $status;
            }
        }

        $params = JFusionFactory::getParams($this->getJname());
        $cookiedomain = $params->get('cookie_domain');
        $cookiepath = $params->get('cookie_path', '/');
        $httponly = $params->get('httponly',0);
        $secure = $params->get('secure',0);
        //Set cookie values
        $expires = mktime(12, 0, 0, 1, 1, 1990);
        if (!$cookiepath) {
            $cookiepath = "/";
        }
        // Clearing eFront Cookies
        $remove_cookies = array('cookie_login', 'cookie_password');
        if ($cookiedomain) {
            foreach ($remove_cookies as $name) {
                @setcookie($name, '', $expires, $cookiepath, $cookiedomain);
                $status['debug'][] = JText::_('DELETED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $name . ', ' .  JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain. ', '.JText::_('COOKIE_SECURE') . '=' .$secure. ', '.JText::_('COOKIE_HTTPONLY') . '=' .$httponly;
           }
        } else {
            foreach ($remove_cookies as $name) {
                @setcookie($name, '', $expires, $cookiepath);
                $status['debug'][] = JText::_('DELETED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $name . ', ' .  JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain. ', '.JText::_('COOKIE_SECURE') . '=' .$secure. ', '.JText::_('COOKIE_HTTPONLY') . '=' .$httponly;
            }
        }

        // do some eFront housekeeping
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "DELETE FROM #__users_to_chatrooms WHERE users_LOGIN = " . $db->Quote($userinfo->username);
        $db->setQuery($query);
        if (!$db->query()) {
            $status["debug"][] = "Error Could not delete users_to_chatroom for user $userinfo->username: {$db->stderr() }";
        } else {
            $status["debug"][] = "Deleted users_to_chatroom for user $userinfo->username.";
        }
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "DELETE FROM #__chatrooms WHERE users_LOGIN = " . $db->Quote($userinfo->username). " AND type = ".$db->Quote("one_to_one");
        $db->setQuery($query);
        if (!$db->query()) {
            $status["debug"][] = "Error Could not delete chatrooms for user $userinfo->username: {$db->stderr() }";
        } else {
            $status["debug"][] = "Deleted chatrooms for user $userinfo->username";
        }
        $query = "DELETE FROM #__users_online WHERE users_LOGIN = " . $db->Quote($userinfo->username);
        $db->setQuery($query);
        if (!$db->query()) {
            $status["debug"][] = "Error Could not delete users_on_line for user $userinfo->username: {$db->stderr()}.";
        } else {
            $status["debug"][] = "Deleted users_on_line for user $userinfo->username.";
        }
        $query = "SELECT action FROM #__logs WHERE users_LOGIN = " . $db->Quote($userinfo->username)." timestamp desc limit 1";
        $db->setQuery($query);
        $action = $db->loadResult;
        if ($action != 'logout') {
            $log = new stdClass;
            $log->id = null;
        	$log->users_LOGIN = $userinfo->username;
        	$log->timestamp = time(); 
        	$log->action = 'logout';
        	$log->comments = 'logged out by jFusion';
        	$log->lessons_ID =0;
        	$ip = explode('.',$_SERVER['REMOTE_ADDR']);
        	$log->session_ip = sprintf('%02x%02x%02x%02x',  $ip[0],  $ip[1],  $ip[2],  $ip[3]);
            $ok = $db->insertObject('#__logs', $log, 'id');
        if (!$ok) {
            $status["debug"][] = "Error Could not log the logout action for user $userinfo->username: {$db->stderr() }";
        } else {
            $status["debug"][] = "Logged the logout action for user $userinfo->username";
        }
    }
        $status['error'] = false;
        return $status;
    }
    function createSession($userinfo, $options) {
        $status = array();
        $status['error'] = array();
        $status['debug'] = array();
        //do not create sessions for blocked users
        if (!empty($userinfo->block) || !empty($userinfo->activation)) {
            $status['error'][] = JText::_('FUSION_BLOCKED_USER');
            return $status;
        }
        //get cookiedomain, cookiepath
        $params = JFusionFactory::getParams($this->getJname());
        $cookiedomain = $params->get('cookie_domain', '');
        $cookiepath = $params->get('cookie_path', '/');
        $httponly = $params->get('httponly',0);
        $secure = $params->get('secure',0);
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT password FROM #__users WHERE login=' . $db->Quote($userinfo->username);
        $db->setQuery($query);
        $user = $db->loadObject();
        // Set cookie values
        $query = "SELECT value FROM #__configuration WHERE name = 'autologout_time'";
        $db->setQuery($query);
        $autologout_time = $db->loadResult(); // this is in minutes
        $expires = 60 * $autologout_times; // converted to seconds
        // correct for remember me option
        if (isset($options['remember'])) {
            if ($options['remember']) {
                // Make the cookie expire in a years time
                $expires = 60 * 60 * 24 * 365;
            }
        }
        $name = 'cookie_login';
        $value = $userinfo->username;
        JFusionFunction::addCookie($name, $value, $expires, $cookiepath, $cookiedomain, false, $httponly);
        if ( ($expires) == 0) {$expires_time='Session_cookie';}
        else {$expires_time=date('d-m-Y H:i:s',time()+$expires);}
        $status['debug'][] = JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $name . ', ' . JText::_('VALUE') . '=' . urldecode($value) .', ' .JText::_('EXPIRES') . '=' .$expires_time .', ' . JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain. ', '.JText::_('COOKIE_SECURE') . '=' .$secure. ', '.JText::_('COOKIE_HTTPONLY') . '=' .$httponly;
        $name = 'cookie_password';
        $value = $user->password;
        JFusionFunction::addCookie($name, $value, $expires, $cookiepath, $cookiedomain, false, $httponly);
        $status['debug'][] = JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $name . ', ' . JText::_('VALUE') . '=' . urldecode($value) .', ' .JText::_('EXPIRES') . '=' .$expires_time .', ' . JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain. ', '.JText::_('COOKIE_SECURE') . '=' .$secure. ', '.JText::_('COOKIE_HTTPONLY') . '=' .$httponly;
        return $status;
    } 
    function filterUsername($username) {
        // as the username also is used as a directory we probably must strip unwanted characters.
        $bad           = array("\\", "/", ":", ";", "~", "|", "(", ")", "\"", "#", "*", "$", "@", "%", "[", "]", "{", "}", "<", ">", "`", "'", ",", " ", "ÄŸ", "Äž", "Ã¼", "Ãœ", "ÅŸ", "Åž", "Ä±", "Ä°", "Ã¶", "Ã–", "Ã§", "Ã‡");
        $replacement    = array("_", "_", "_", "_", "_", "_", "", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "", "_", "_", "g", "G", "u", "U", "s", "S", "i", "I", "o", "O", "c", "C");
        $username = str_replace($bad, $replacement, $username);
    	return $username;
    }
    function updatePassword($userinfo, $existinguser, &$status) {
        $params = JFusionFactory::getParams($this->getJname());
        $md5_key = $params->get('md5_key');
        $existinguser->password = md5($userinfo->password_clear.$md5_key);
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET password =' . $db->Quote($existinguser->password). 'WHERE id =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password, 0, 6) . '********';
        }
    }
    function updateUsername($userinfo, &$existinguser, &$status) {
        // not implemented in jFusion 1.x
    }
    function updateEmail($userinfo, &$existinguser, &$status) {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET email =' . $db->Quote($userinfo->email) . ' WHERE id =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('EMAIL_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }
    function blockUser($userinfo, &$existinguser, &$status) {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET active = 0 WHERE id =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('BLOCK_UPDATE') . ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        }
    }
    function unblockUser($userinfo, &$existinguser, &$status) {
        //unblock the user
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET active = 1 WHERE id =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('BLOCK_UPDATE') . ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        }
    }
    function activateUser($userinfo, &$existinguser, &$status) {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET pending = 0 WHERE id =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('ACTIVATION_UPDATE') . ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }
    function inactivateUser($userinfo, &$existinguser, &$status) {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET pending = 1 WHERE id =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('ACTIVATION_UPDATE') . ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }
    function createUser($userinfo, &$status) {
       /**
        * NOTE: eFront does a charactercheck on the user credentials. I think we are ok (HW): if (preg_match("/^.*[$\/\'\"]+.*$/", $parameter))
        */	
        $status = array();
        $status['debug'] = array();
        $status['error'] = array();
    	$params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());
        //prepare the variables
        $user = new stdClass;
        $user->id = null;
        $user->login = $this->filterUsername($userinfo->username);
        $parts = explode(' ', $userinfo->name);
        $user->name = trim($parts[0]);
        if (count($parts) > 1) {
        	// construct the lastname
        	$lastname = '';
            for ($i = 1;$i < (count($parts));$i++) {
                $lastname = $lastname . ' ' . $parts[$i];
            }
            $user->surname = trim($lastname);
        } else {
            // eFront needs Firstname AND Lastname, so add a dot when lastname is empty
            $user->surname = '.';
        }
        $user->email = $userinfo->email;
        //get the default user group and determine if we are using simple or advanced
        $usergroups = (substr($params->get('usergroup'), 0, 2) == 'a:') ? unserialize($params->get('usergroup')) : $params->get('usergroup', 18);
        //check to make sure that if using the advanced group mode, $userinfo->group_id exists
        if (is_array($usergroups) && !isset($userinfo->group_id)) {
            $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . ": " . JText::_('ADVANCED_GROUPMODE_MASTER_NOT_HAVE_GROUPID');
            return null;
        }
        $default_group_id = (is_array($usergroups)) ? $usergroups[$userinfo->group_id] : $usergroups;
        $user_type = "";
        $user_types_ID = 0;
        switch ($default_group_id){
            case 0: 
                $user_type = 'student';
           	    break;
            case 1: 
            	$user_type = 'professor';
            	break;
            case 2: 
            	$user_type = 'administrator';
            	break;
            default: 
                // correct id
                $user_types_ID = $default_group_id - 2;
                $query = 'SELECT basic_user_type from #__user_types WHERE id = '.$user_types_ID;
                $db->setQuery($query);
                $user_type = $db->loadResult();
        }
        $user->user_type = $user_type;
        $user->user_types_ID = $user_types_ID;
        if (isset($userinfo->password_clear) && strlen($userinfo->password_clear) != 32) {
            $md5_key = $params->get('md5_key');
         	$user->password = md5($userinfo->password_clear.$md5_key);
        } else {
        	$user->password = $userinfo->password;
        }
        // note that we will plan to propagate the language setting for a user from version 2.0
        // for now we just use the default defined in eFront	
        $query = "SELECT value from #__configuration WHERE name = 'default_language'";
        $db->setQuery($query);
        $default_language = $db->loadResult();
        $user->languages_NAME = $default_language;
        $user->active = 1;
        $user->comments = null;
        $user->timestamp = time();
        $user->pending = 0;
        $user->avatar = null;
        $user->additional_accounts = null;
        $user->viewed_license =0;
        $user->need_mod_init =1;
        if (!$db->insertObject('#__users', $user, 'id')) {
            //return the error
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
            return;
        }
        // we need to create the user directories. Can't use Joomla's API because it uses the Joomla Root Path
        $uploadpath = $params->get('uploadpath');        
        $user_dir = $uploadpath.$user->login.'/';
        if (is_dir($user_dir)) {                                                
            JFusionEfrontHelper::delete_directory($user_dir); //If the folder already exists, delete it first, including files
        }	
        // we are not interested in the result of the deletion, just continue
        if (mkdir($user_dir, 0755) || is_dir($user_dir)) 
        { 
            //Now, the directory either gets created, or already exists (in case errors happened above). In both cases, we continue
            //Create personal messages attachments folders
            mkdir($user_dir.'message_attachments/', 0755);
            mkdir($user_dir.'message_attachments/Incoming/', 0755);
            mkdir($user_dir.'message_attachments/Sent/', 0755);
            mkdir($user_dir.'message_attachments/Drafts/', 0755);
            mkdir($user_dir.'avatars/', 0755);

            //Create database representations for personal messages folders (it has nothing to do with filesystem database representation)
            $f_folder = new stdClass;
            $f_folder->id = null;
            $f_folder->name = 'Incoming';
            $f_folder->users_LOGIN = $user->login;           
            $errors = $db->insertObject('#__f_folders', $f_folder, 'id');
            $f_folder->id = null;
            $f_folder->name = 'Sent';
            $f_folder->users_LOGIN = $user->login;           
            $errors = $db->insertObject('#__f_folders', $f_folder, 'id');
            $f_folder->id = null;
            $f_folder->name = 'Drafts';
            $f_folder->users_LOGIN = $user->login;           
            $errors = $db->insertObject('#__f_folders', $f_folder, 'id');

            // for eFront Educational and enterprise versions we now should assign skillgap tests
            // not sure I should implemented it, anyway I have only the community version to work on             
        }
        //return the good news
        $status['debug'][] = JText::_('USER_CREATION');
        $status['userinfo'] = $this->getUser($userinfo);
        return;
    }
    function deleteUser($userinfo){
        // we are using the api function remove_user here. 
        // User deletion is not a time critical function and deleting a user is
        // more often than not a complicated task in this type of software.
        // In eFront, it is impossible to trigger the 'ondeleteuser' signal for the
        // modules without loading the complete website. 
        
    	// check apiuser existance
        $status = array();
        $status['debug'] = array();
        $status['error'] = array();
        $status['debug'] = null;
        if (!is_object($userinfo)) {
            $status['error'][] = JText::_('NO_USER_DATA_FOUND');
            return $status;
        }
        $existinguser = $this->getUser($userinfo);
        if (!empty($existinguser)) {
            $params = JFusionFactory::getParams($this->getJname());
        	$apiuser = $params->get('apiuser');
            $apikey = $params->get('apikey');
            $login = $existinguser->username;
            $jname = $this->getJname();
            if (!$apiuser || !$apikey) {
                JError::raiseWarning(0, $jname . '-plugin: ' . JText::_('EFRONT_WRONG_APIUSER_APIKEY_COMBINATION'));
                $status['error'][] = '';
                return $status;
            }
            // get token
            $curl_options['action'] ='token';
            $status = JFusionEfrontHelper::send_to_api($curl_options,$status);
            if ($status['error']){
                return $status;
            }    
            $result = $status['result'][0];
            $token = $result->token;
    	    // login
            $curl_options['action']='login';
            $curl_options['parms'] = "&token=$token&username=$apiuser&password=$apikey";
            $status = JFusionEfrontHelper::send_to_api($curl_options,$status);
            if ($status['error']){
                return $status;
            }    
            $result = $status['result'][0];
            if($result->status == 'ok'){
                // logged in (must logout later)
                // delete user
                $curl_options['action']='remove_user';
                $curl_options['parms'] = "&token=$token&login=$login";
                $status = JFusionEfrontHelper::send_to_api($curl_options,$status);
                $errorstatus = $status;
                if ($status['error']){
                    $status['debug'][] = $status['error'][0];
                    $status['error']=array();
                }
                $result = $status['result'][0];
                if($result->status != 'ok'){
                    $errorstatus['debug'][]=$jname.' eFront API--'.$result->message;
                }
                // logout
                $curl_options['action']='logout';
                $curl_options['parms'] = "&token=$token";
                $status = JFusionEfrontHelper::send_to_api($curl_options,$status);
                $result = $status['result'][0];
                if($result->status != 'ok'){
                    $errorstatus['error'][]=$jname.' eFront API--'.$result->message;
                    return $errorstatus;
                }
            }  
            $status['error']= null;
            $status['debug'][] = JText::_('DELETED').JTEXT::_(' USER: ' ).$login;
            return $status;
        }
    }
    function updateUsergroup($userinfo, &$existinguser, &$status) {
        $params = & JFusionFactory::getParams($this->getJname());
    	//get the usergroup and determine if working in advanced or simple mode
        if (substr($params->get('usergroup'), 0, 2) == 'a:'){
            //check to see if we have a group_id in the $userinfo, if not return
            if (!isset($userinfo->group_id)) {
                $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . ": " . JText::_('ADVANCED_GROUPMODE_MASTER_NOT_HAVE_GROUPID');
                return null;
            }
            $usergroups = unserialize($params->get('usergroup'));
            if (isset($usergroups[$userinfo->group_id])) {
                $db = JFusionFactory::getDataBase($this->getJname());
                if ($usergroups[$userinfo->group_id]< 3){
                	$user_type = $this->groupIDToName($usergroups[$userinfo->group_id]);
                	$user_types_ID = 0;
                } else {
                	$user_types_ID = $usergroups[$userinfo->group_id]-2;
                    $query = 'SELECT basic_user_type from #__user_types WHERE id = '.$user_types_ID;
                    $db->setQuery($query);
                    $user_type = $db->loadResult();
                }
                $query = "UPDATE #__users SET user_type = ".$db->Quote($user_type).", user_types_ID = $user_types_ID WHERE id =" . $existinguser->userid;
                $db->setQuery($query);
                if (!$db->query()) {
                    $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . $db->stderr();
                } else {
                    $status['debug'][] = JText::_('GROUP_UPDATE') . ': ' . $existinguser->group_id . ' -> ' . $usergroups[$userinfo->group_id];
                }
           }
        } else {
            $status['error'][] = JText::_('GROUP_UPDATE_ERROR');
        }
    }
    
}
?>