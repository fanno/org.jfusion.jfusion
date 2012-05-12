<?php

/**
* @package JFusion_SMF
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * Load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

/**
 * JFusion User Class for SMF 1.1.x
 * For detailed descriptions on these functions please check the model.abstractuser.php
 * @package JFusion_SMF
 */
class JFusionUser_smf2 extends JFusionUser {

    function &getUser($userinfo)
    {
		//get the identifier
		list($identifier_type,$identifier) = $this->getUserIdentifier($userinfo,'a.member_name','a.email_address');

        // initialise some objects
        $params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT a.id_member as userid, a.member_name as username, a.real_name as name, a.email_address as email, a.passwd as password, a.password_salt as password_salt, a.validation_code as activation, a.is_activated, NULL as reason, a.last_login as lastvisit, a.id_group as group_id '.
        		'FROM #__members as a '.
        		'WHERE '.$identifier_type.'=' . $db->Quote($identifier);

        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
        	if ($result->group_id==0) {
        		$result->group_name = "Default Usergroup";
        	} else {
        		$query = 'SELECT group_name FROM #__membergroups WHERE id_group = ' . $result->group_id;
        		$db->setQuery($query );
        		$result->group_name = $db->loadResult();
        	}

            //Check to see if they are banned
            $query = 'SELECT id_ban_group, expire_time FROM #__ban_groups WHERE name= ' . $db->quote($result->username);
            $db->setQuery($query);
            $expire_time = $db->loadObject();
            if ($expire_time) {
            	if ($expire_time->expire_time == '' || $expire_time->expire_time > time() ){
                	$result->block = 1;
            	} else {
                	$result->block = 0;
            	}
            } else {
                $result->block = 0;
            }

            if ($result->is_activated == 1){
				$result->activation = '';
            }
        }
        return $result;
    }

    function getJname()
    {
        return 'smf2';
    }

    function deleteUser($userinfo)
    {
    	//setup status array to hold debug info and errors
        $status = array();
        $status['debug'] = array();
        $status['error'] = array();

        $db = JFusionFactory::getDatabase($this->getJname());

		$query = 'DELETE FROM #__members WHERE member_name = '.$db->quote($userinfo->username);
		$db->setQuery($query);
        if (!$db->query()) {
       		$status['error'][] = JText::_('USER_DELETION_ERROR') . ' ' .  $db->stderr();
        } else {
	        //update the stats
        	$query = 'UPDATE #__settings SET value = value - 1 	WHERE variable = \'totalMembers\' ';
        	$db->setQuery($query);
        	if (!$db->query()) {
	            //return the error
            	$status['error'][] = JText::_('USER_DELETION_ERROR')  . ' ' .  $db->stderr();
	            return;
        	}

        	$query = 'SELECT MAX(id_member) as id_member FROM #__members WHERE is_activated = 1';
			$db->setQuery($query);
			$resultID = $db->loadObject();
            if (!$resultID) {
                //return the error
                $status['error'][] = JText::_('USER_DELETION_ERROR') . $db->stderr();
                return;
            }

			$query = 'SELECT real_name as name FROM #__members WHERE id_member = '.$db->quote($resultID->id_member).' LIMIT 1';
			$db->setQuery($query );
			$resultName = $db->loadObject();
            if (!$resultName) {
                //return the error
                $status['error'][] = JText::_('USER_DELETION_ERROR') . $db->stderr();
                return;
            }

            $query = 'REPLACE INTO #__settings (variable, value) VALUES (\'latestMember\', ' . $resultID->id_member . '), (\'latestRealName\', ' . $db->quote($resultName->name) . ')';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_DELETION_ERROR') . $db->stderr();
                return;
            }

			$status['error'] = false;
			$status['debug'][] = JText::_('USER_DELETION'). ' ' . $userinfo->username;
		}
		return $status;
    }

    function destroySession($userinfo, $options){
        $params = JFusionFactory::getParams($this->getJname());
        $crossdomain_url = $params->get('crossdomain_url');
        if ( $crossdomain_url ) {
		    $jc = JFusionFactory::getCookies();
            $jc->addCookie($crossdomain_url, $params->get('cookie_name'), '',0,$params->get('cookie_path'),$params->get('cookie_domain'),$params->get('secure'),$params->get('httponly'));           
        } else {
		    JFusionFunction::addCookie($params->get('cookie_name'), '',0,$params->get('cookie_path'),$params->get('cookie_domain'),$params->get('secure'),$params->get('httponly'));
        }
        $status = array();
		return $status;
     }

    function createSession($userinfo, $options){

		//do not create sessions for blocked users
		if (!empty($userinfo->block) || !empty($userinfo->activation)) {
	        $status = array();
    	    $status['error'] = array();
        	$status['debug'] = array();
            $status['error'][] = JText::_('FUSION_BLOCKED_USER');
            return $status;
		}
    	$params = JFusionFactory::getParams($this->getJname());
		$status = JFusionJplugin::createSession($userinfo, $options,$this->getJname(),$params->get('brute_force'));
		return $status;
    }


    function filterUsername($username)
    {
        //no username filtering implemented yet
        return $username;
    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        $existinguser->password = sha1(strtolower($userinfo->username) . $userinfo->password_clear);
        $existinguser->password_salt = substr(md5(rand()), 0, 4);
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET passwd = ' . $db->quote($existinguser->password). ', password_salt = ' . $db->quote($existinguser->password_salt). ' WHERE id_member  = ' . $existinguser->userid;
        $db = JFusionFactory::getDatabase($this->getJname());
        $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
        }
    }

    function updateUsername($userinfo, &$existinguser, &$status)
    {

    }

    function updateEmail($userinfo, &$existinguser, &$status)
    {
        //we need to update the email
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET email_address ='.$db->quote($userinfo->email) .' WHERE id_member =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }
    
    /**
     * updateUsergroup
     *
     * @param object $userinfo      holds the new user data
     * @param object &$existinguser holds the exsisting user data
     * @param array  &$status       Status array
     *
     * @access public
     * @return void
     */
    function updateUsergroup($userinfo, &$existinguser, &$status)
    {
        $params = JFusionFactory::getParams($this->getJname());
        //get the usergroup and determine if working in advanced or simple mode
        
		$groups = JFusionFunction::getCorrectUserGroups($this->getJname(),$userinfo);
        if (count($groups)) {
            //check to see if we have a group_id in the $userinfo, if not return
            if (!isset($groups[0])) {
                $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . ": " . JText::_('ADVANCED_GROUPMODE_MASTER_NOT_HAVE_GROUPID');
                return null;
            }
            $group = $groups[0];
            
			$db = JFusionFactory::getDatabase($this->getJname());
			$query = 'UPDATE #__members SET id_group =' . $db->quote($group) . ' WHERE id_member =' . (int)$existinguser->userid;
			$db->setQuery($query);
			if (!$db->query()) {
				$status['error'][] = JText::_('GROUP_UPDATE_ERROR') . $db->stderr();
			} else {
				$status['debug'][] = JText::_('GROUP_UPDATE') . ': ' . $existinguser->group_id . ' -> ' . $group;
			}
        } else {
            $status['error'][] = JText::_('GROUP_UPDATE_ERROR');
        }
    }    
    

    function blockUser($userinfo, &$existinguser, &$status)
    {

            $db = JFusionFactory::getDatabase($this->getJname());
            $ban = new stdClass;
            $ban->id_ban_group = NULL;
            $ban->name = $existinguser->username;
            $ban->ban_time = time();
            $ban->expire_time = NULL;
            $ban->cannot_access = 1;
            $ban->cannot_register = 0;
            $ban->cannot_post = 0;
            $ban->cannot_login = 0;
            $ban->reason = 'You have been banned from this software. Please contact your site admin for more details';

            //now append the new user data
            if (!$db->insertObject('#__ban_groups', $ban, 'id_ban_group' )) {
         	   $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
	        }

            $ban_item = new stdClass;
            $ban_item->id_ban_group = $ban->id_ban_group;
            $ban_item->id_member = $existinguser->userid;
            if (!$db->insertObject('#__ban_items', $ban_item, 'id_ban' )) {
               $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
           	} else {
               $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
            }
    }

    function unblockUser($userinfo, &$existinguser, &$status)
    {
        	$db = JFusionFactory::getDatabase($this->getJname());
            $query = 'DELETE FROM #__ban_groups WHERE name = ' . $db->quote($existinguser->username);
            $db->setQuery($query);
		    if (!$db->query()) {
        	    $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        	}

            $query = 'DELETE FROM #__ban_items WHERE id_member = ' . $existinguser->userid;
            $db->setQuery($query);
	        if (!$db->query()) {
               $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
            } else {
               $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
            }


    }

    function activateUser($userinfo, &$existinguser, &$status)
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET is_activated = 1, validation_code = \'\' WHERE id_member  = ' . $existinguser->userid;
        $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function inactivateUser($userinfo, &$existinguser, &$status)
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET is_activated = 0, validation_code = '.$db->Quote($userinfo->activation).' WHERE id_member  = ' . $existinguser->userid;
        $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function createUser($userinfo, &$status)
    {
        //we need to create a new SMF user
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');

		$groups = JFusionFunction::getCorrectUserGroups($this->getJname(),$userinfo);
        if (!isset($groups[0])) {
			//TODO: change error message
            $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . ": " . JText::_('ADVANCED_GROUPMODE_MASTER_NOT_HAVE_GROUPID');
            return null;
        }
        //prepare the user variables
        $user = new stdClass;
        $user->id_member = NULL;
        $user->member_name = $userinfo->username;
        $user->real_name = $userinfo->name;
        $user->email_address = $userinfo->email;

        if (isset($userinfo->password_clear)) {
            $user->passwd = sha1(strtolower($userinfo->username) . $userinfo->password_clear);
            $user->password_salt = substr(md5(rand()), 0, 4);
        } else {
            $user->passwd = $userinfo->password;

            if (!isset($userinfo->password_salt)) {
                $user->password_salt = substr(md5(rand()), 0, 4);
            } else {
                $user->password_salt = $userinfo->password_salt;
            }

        }

        $user->posts = 0 ;
        $user->date_registered = time();

        if ($userinfo->activation){
        	$user->is_activated = 0;
        	$user->validation_code = $userinfo->activation;
        } else {
        	$user->is_activated = 1;
        	$user->validation_code = '';
        }

        $user->personal_text = '';
        $user->pm_email_notify = 1;
        $user->hide_email = 1;
        $user->id_theme = 0;
        
        $user->id_group = $groups[0];
        $user->id_post_group = $params->get('userpostgroup', 4);

        //now append the new user data
        if (!$db->insertObject('#__members', $user, 'id_member' )) {
            //return the error
            $status['error'] = JText::_('USER_CREATION_ERROR'). ': ' . $db->stderr();
            return $status;
        } else {
            //update the stats
            $query = 'UPDATE #__settings SET value = value + 1 	WHERE variable = \'totalMembers\' ';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            $date = strftime('%Y-%m-%d');
			$query = 'UPDATE #__log_activity SET registers = registers + 1 WHERE date = \''.$date.'\'';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            $query = 'REPLACE INTO #__settings (variable, value) VALUES (\'latestMember\', ' . $user->id_member . '), (\'latestRealName\', ' . $db->quote($userinfo->name) . ')';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //return the good news
            $status['debug'][] = JText::_('USER_CREATION');
            $status['userinfo'] = $this->getUser($userinfo);
            return $status;
        }
    }
}