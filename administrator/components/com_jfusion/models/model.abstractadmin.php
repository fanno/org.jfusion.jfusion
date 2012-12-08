<?php

/**
 * Abstract admin file
 *
 * PHP version 5
 *
 * @category  JFusion
 * @package   Models
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'defines.php';

/**
 * Abstract interface for all JFusion functions that are accessed through the Joomla administrator interface
 *
 * @category  JFusion
 * @package   Models
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org
 */
class JFusionAdmin
{
    /**
     * returns the name of this JFusion plugin
     *
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
    }

    /**
     * Returns the a list of users of the integrated software
     * @param int $limitstart optional
     * @param int $limit optional
     * @return array List of usernames/emails
     */
    function getUserList($limitstart = null, $limit = null)
    {
        return 0;
    }

    /**
     * Returns the the number of users in the integrated software. Allows for fast retrieval total number of users for the usersync
     *
     * @return integer Number of registered users
     */
    function getUserCount()
    {
        return 0;
    }

    /**
     * Returns the a list of usersgroups of the integrated software
     *
     * @return array List of usergroups
     */
    function getUsergroupList()
    {
        return 0;
    }

    /**
     * Function used to display the default usergroup in the JFusion plugin overview
     *
     * @return string Default usergroup name
     */
    function getDefaultUsergroup()
    {
        return '';
    }

    /**
     * Checks if the software allows new users to register
     *
     * @return boolean True if new user registration is allowed, otherwise returns false
     */
    function allowRegistration()
    {
        $result = true;
        return $result;
    }

    /**
     * returns the name of user table of integrated software
     *
     * @return string table name
     */
    function getTablename()
    {
        return '';
    }

    /**
     * Function finds config file of integrated software and automatically configures the JFusion plugin
     *
     * @param string $softwarePath path to root of integrated software
     *
     * @return object JParam JParam objects with ne newly found configuration
     */
    function setupFromPath($softwarePath)
    {
        return 0;
    }

    /**
     * Function that checks if the plugin has a valid config
     *
     * @return array result of the config check
     */
    function checkConfig()
    {
        $status = array();
        $jname = $this->getJname();
        //for joomla_int check to see if the source_url does not equal the default
        $params = JFusionFactory::getParams($jname);
        if ($jname == 'joomla_int') {
            $source_url = $params->get('source_url');
            if (!empty($source_url)) {
                $status['config'] = 1;
                $status['message'] = JText::_('GOOD_CONFIG');
                return $status;
            } else {
                $status['config'] = 0;
                $status['message'] = JText::_('NOT_CONFIGURED');
                return $status;
            }
        }
        $db = JFusionFactory::getDatabase($jname);
        $jdb = JFactory::getDBO();
        if (JError::isError($db) || !$db || !method_exists($jdb, 'setQuery')) {
            $status['config'] = 0;
            $status['message'] = JText::_('NO_DATABASE');
            return $status;
        } elseif (!$db->connected()) {
            $status['config'] = 0;
            $status['message'] = JText::_('NO_DATABASE');
            return $status;   
        } else {
            //added check for missing files of copied plugins after upgrade
            $admin_file = JFUSION_PLUGIN_PATH . DS . $jname . DS . 'admin.php';
            if (!file_exists($admin_file)) {
                $status['config'] = 0;
                $status['message'] = JText::_('NO_FILES');
                return $status;
            }
            
            $cookie_domain = $params->get('cookie_domain');
            $jfc = JFusionFactory::getCookies();
            list($url) = $jfc->getApiUrl($cookie_domain);
            if ($url) {
            	require_once(JPATH_SITE.DS.'components'.DS.'com_jfusion'.DS.'jfusionapi.php');
            	
            	$joomla_int = JFusionFactory::getParams('joomla_int');
            	$api = new JFusionAPI($url,$joomla_int->get('secret'));
            	if (!$api->ping()) {
            		list ($message) = $api->getError();
            		$status['config'] = 0;
            		$status['message'] = $api->url. ' ' .$message;
            		return $status;
            	}
            }
            
            //get the user table name
            $tablename = $this->getTablename();
            // lets check if the table exists, now using the Joomla API
            $table_list = $db->getTableList();
            $table_prefix = $db->getPrefix();
            if (!is_array($table_list)) {
                    $status['config'] = 0;
                    $status['message'] = $table_prefix . $tablename . ': ' . JText::_('NO_TABLE');
                    return $status;
            }
            if (array_search($table_prefix . $tablename, $table_list) === false) {
                //do a final check for case insensitive windows servers
                if (array_search(strtolower($table_prefix . $tablename), $table_list) === false) {
                    $status['config'] = 0;
                    $status['message'] = $table_prefix . $tablename . ': ' . JText::_('NO_TABLE');
                    return $status;
                } else {
                    $status['config'] = 1;
                    $status['message'] = JText::_('GOOD_CONFIG');
                    return $status;
                }
            } else {
                $status['config'] = 1;
                $status['message'] = JText::_('GOOD_CONFIG');
                return $status;
            }
        }
    }

    /**
     * Function that checks if the plugin has a valid config
     *
     * @return array nothing as jerror is used for output
     */
    function debugConfig()
    {
        //get registration status
        $new_registration = $this->allowRegistration();
        $jname = $this->getJname();
        //get the data about the JFusion plugins
        $db = JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query);
        $plugin = $db->loadObject();
        //output a warning to the administrator if the allowRegistration setting is wrong
        if ($new_registration && $plugin->slave == '1') {
            JError::raiseNotice(0, $jname . ': ' . JText::_('DISABLE_REGISTRATION'));
        }
        if (!$new_registration && $plugin->master == '1') {
            JError::raiseNotice(0, $jname . ': ' . JText::_('ENABLE_REGISTRATION'));
        }
        //most dual login problems are due to incorrect cookie domain settings
        //therefore we should check it and output a warning if needed.
        $params = JFusionFactory::getParams($this->getJname());
        $cookie_domain = $params->get('cookie_domain');
        $correct_domain = '';
        $correct_array = explode('.', html_entity_decode($_SERVER['SERVER_NAME']));

        //check for domain names with double extentions
        if (isset($correct_array[count($correct_array) - 2]) && isset($correct_array[count($correct_array) - 1])) {
            //domain array
            $domain_array = array('com', 'net', 'org', 'co', 'me');
            if (in_array($correct_array[count($correct_array) - 2], $domain_array)) {
                $correct_domain = '.' . $correct_array[count($correct_array) - 3] . '.' . $correct_array[count($correct_array) - 2] . '.' . $correct_array[count($correct_array) - 1];
            } else {
                $correct_domain = '.' . $correct_array[count($correct_array) - 2] . '.' . $correct_array[count($correct_array) - 1];
            }
            if (($correct_domain != $cookie_domain) && !($this->allowEmptyCookieDomain())) {
                JError::raiseNotice(0, $jname . ': ' . JText::_('BEST_COOKIE_DOMAIN') . ' ' . $correct_domain);
            }
        }
        //also check the cookie path as it can intefere with frameless
        $params = JFusionFactory::getParams($this->getJname());
        $cookie_path = $params->get('cookie_path');
        if (($correct_domain != $cookie_domain) && !($this->allowEmptyCookiePath())) {
            JError::raiseNotice(0, $jname . ': ' . JText::_('BEST_COOKIE_PATH') . ' /');
        }
        //check that master plugin does not have advanced group mode data stored
        $master = JFusionFunction::getMaster();
        $params = JFusionFactory::getParams($jname);
        if (!empty($master) && $master->name == $jname && substr($params->get('usergroup'), 0, 2) == 'a:') {
            JError::raiseWarning(0, $jname . ': ' . JText::_('ADVANCED_GROUPMODE_ONLY_SUPPORTED_FORSLAVES'));
        }
        // allow additional checking of the configuration
        $this->debugConfigExtra();
    }

    /**
     * Function that determines if the empty cookie path is allowed
     *
     * @return bool
     */
    function allowEmptyCookiePath()
    {
        return false;
    }

    /**
     * Function that determines if the empty cookie domain is allowed
     *
     * @return bool
     */
    function allowEmptyCookieDomain()
    {
        return false;
    }

    /**
     * Function to implement any extra debug checks for plugins
     *
     * @return bool
     */
    function debugConfigExtra()
    {
    }

    /**
     * Get an usergroup element
     *
     * @param string $name         name of element
     * @param string $value        value of element
     * @param string $node         node of element
     * @param string $control_name name of controler
     *
     * @return string html
     */
    function usergroup($name, $value, $node, $control_name)
    {
        $jname = $this->getJname();
        //get the master plugin to be throughout
        $master = JFusionFunction::getMaster();
        $advanced = 0;

        if(JFusionFunction::isJoomlaVersion('1.6')){
			// set output format options in 1.6 only
			JHTML::setFormatOptions(array('format.eol' => "", 'format.indent' => ""));
		}
        //detect is value is a serialized array
        if (substr($value, 0, 2) == 'a:') {
            $value = unserialize($value);
            //use advanced only if this plugin is not set as master
            if (!empty($master) && $master->name != $this->getJname()) {
                $advanced = 1;
            }
        }
        if (JFusionFunction::validPlugin($this->getJname())) {
            $usergroups = $this->getUsergroupList();
            if (!empty($usergroups)) {
                $simple_usergroup = "<table style=\"width:100%; border:0\">";
                $simple_usergroup.= "<tr><td>" . JText::_('DEFAULT_USERGROUP') . "</td><td>" . JHTML::_('select.genericlist', $usergroups, $control_name . '[' . $name . ']', '', 'id', 'name', $value) . "</td></tr>";
                $simple_usergroup.= "</table>";
                //escape single quotes to prevent JS errors
                $simple_usergroup = str_replace("'", "\'", $simple_usergroup);
            } else {
                $simple_usergroup = '';
            }
        } else {
            return JText::_('SAVE_CONFIG_FIRST');
        }
        //check to see if current plugin is a slave
        $db = JFactory::getDBO();
        $query = 'SELECT slave FROM #__jfusion WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query);
        $slave = $db->loadResult();
        $list_box = '<select onchange="usergroupSelect(this.selectedIndex);">';
        if ($advanced == 1) {
            $list_box.= '<option value="0">Simple</option>';
        } else {
            $list_box.= '<option value="0" selected="selected">Simple</option>';
        }
        if ($slave == 1 && !empty($master)) {
            //allow usergroup sync
            if ($advanced == 1) {
                $list_box.= '<option selected="selected" value="1">Advanced</option>';
            } else {
                $list_box.= '<option value="1">Advanced</option>';
            }
            //prepare the advanced options
            $JFusionMaster = JFusionFactory::getAdmin($master->name);
            $master_usergroups = $JFusionMaster->getUsergroupList();
            $advanced_usergroup = "<table class=\"usergroups\">";
            if ($advanced == 1) {
                foreach ($master_usergroups as $master_usergroup) {
                    $select_value = (!isset($value[$master_usergroup->id])) ? '' : $value[$master_usergroup->id];
                    $advanced_usergroup.= "<tr><td>" . $master_usergroup->name . '</td>';
                    $advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $usergroups, $control_name . '[' . $name . '][' . $master_usergroup->id . ']', '', 'id', 'name', $select_value) . '</td></tr>';
                }
            } else {
                foreach ($master_usergroups as $master_usergroup) {
                    $advanced_usergroup.= "<tr><td>" . $master_usergroup->name . '</td>';
                    $advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $usergroups, $control_name . '[' . $name . '][' . $master_usergroup->id . ']', '', 'id', 'name', '') . '</td></tr>';
                }
            }
            $advanced_usergroup.= "</table>";
            //escape single quotes to prevent JS errors
            $advanced_usergroup = str_replace("'", "\'", $advanced_usergroup);
        } else {
            $advanced_usergroup = '';
        }
        $list_box.= '</select>';
        ?><script type="text/javascript">
        function usergroupSelect(option)
        {
            var myArray = new Array();
            myArray[0] = '<?php echo $simple_usergroup; ?>';
            myArray[1] = '<?php echo $advanced_usergroup; ?>';
            document.getElementById("JFusionUsergroup").innerHTML = myArray[option];
        }
        </script>
        <?php
       	$return = '<table><tr><td>'.JText::_('USERGROUP'). ' '. JText::_('MODE').'</td><td>'.$list_box.'</td></tr><tr><td COLSPAN=2><div id="JFusionUsergroup">';        
       	if ($advanced == 1) {
       		$return .= $advanced_usergroup;
       	} else {
       		$return .= $simple_usergroup;
       	}
		$return .= '</div></td></tr></table>';
        return $return;
    }

    /**
     * Get an multiusergroup element
     *
     * @param string $name         name of element
     * @param string $value        value of element
     * @param string $node         node of element
     * @param string $control_name name of controler
     *
     * @return string html
     */
    function multiusergroup($name, $value, $node, $control_name)
    {
        $jname = $this->getJname();
        //get the master plugin to be throughout
        $master = JFusionFunction::getMaster();
        $advanced = 0;

        if(JFusionFunction::isJoomlaVersion('1.6')){
			// set output format options in 1.6 only
			JHTML::setFormatOptions(array('format.eol' => "", 'format.indent' => ""));
		}
        //detect is value is a serialized array
        if (substr($value, 0, 2) == 'a:') {
            $value = unserialize($value);
            //use advanced only if this plugin is not set as master
            if (!empty($master) && $master->name != $this->getJname()) {
                $advanced = 1;
            }
        }
        if (JFusionFunction::validPlugin($this->getJname())) {
            $usergroups = $this->getUsergroupList();
            if (!empty($usergroups)) {
                $simple_usergroup = "<table style=\"width:100%; border:0\">";
                $simple_usergroup.= "<tr><td>" . JText::_('DEFAULT_USERGROUP') . "</td><td>" . JHTML::_('select.genericlist', $usergroups, $control_name . '[' . $name . ']', '', 'id', 'name', $value) . "</td></tr>";
                $simple_usergroup.= "</table>";
                //escape single quotes to prevent JS errors
                $simple_usergroup = str_replace("'", "\'", $simple_usergroup);
            } else {
                $simple_usergroup = '';
            }
        } else {
            return JText::_('SAVE_CONFIG_FIRST');
        }
        
		$params = JFusionFactory::getParams($jname);
		$multiusergroupdefault = $params->get('multiusergroupdefault');
        
		if ( !empty($master) ) {
			$JFusionMaster = JFusionFactory::getAdmin($master->name);
			$master_usergroups = $JFusionMaster->getUsergroupList();
		}
		
        //check to see if current plugin is a slave
        $db = JFactory::getDBO();
        $query = 'SELECT slave FROM #__jfusion WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query);
        $slave = $db->loadResult();
        $list_box = '<select onchange="usergroupSelect(this.selectedIndex);">';
        if ($advanced == 1) {
            $list_box.= '<option value="0">Simple</option>';
        } else {
            $list_box.= '<option value="0" selected="selected">Simple</option>';
        }
		$jfGroupCount = 0;
        if ($slave == 1 && !empty($master)) {
            //allow usergroup sync
            if ($advanced == 1) {
                $list_box.= '<option selected="selected" value="1">Advanced</option>';
            } else {
                $list_box.= '<option value="1">Advanced</option>';
            }
            //prepare the advanced options
            $advanced_usergroup = "<table id=\"usergroups\" class=\"usergroups\">";
            $advanced_usergroup.= '<tr><td>'.$JFusionMaster->getJname().'</td><td>'.$this->getJname().'</td><td>Default Group</td><td></td></tr>';
            
            $master_control_name = $control_name . '[' . $name . ']['.$JFusionMaster->getJname().']';
            $this_control_name = $control_name . '[' . $name . ']['.$this->getJname().']';
            if ($advanced == 1) {
				if ( isset($value[$JFusionMaster->getJname()]) && isset($value[$this->getJname()]) && count($value[$JFusionMaster->getJname()]) < count($value[$this->getJname()])) {
					$groups = isset($value[$this->getJname()]) ? $value[$this->getJname()] : array(); 
				} else {
					$groups = isset($value[$JFusionMaster->getJname()]) ? $value[$JFusionMaster->getJname()] : array();
				}
				
				foreach ($groups as $key => $select_value) {
                    $jfGroupCount++;
					$select_value =  isset($value[$JFusionMaster->getJname()][$key]) ? $value[$JFusionMaster->getJname()][$key] : array();
					$advanced_usergroup.= '<tr id="usergroups_row'.$jfGroupCount.'">';
                   	if ($JFusionMaster->isMultiGroup()) {
                   		$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $master_usergroups , $master_control_name.'['.$jfGroupCount.'][]', 'MULTIPLE SIZE="10"', 'id', 'name', $select_value) . '</td>';	                   		
                   	} else {
						$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $master_usergroups , $master_control_name.'['.$jfGroupCount.'][]', '', 'id', 'name', $select_value) . '</td>';	                   		
                   	}
                   	
                    $select_value = isset($value[$this->getJname()][$key]) ? $value[$this->getJname()][$key] : array();
                   	if ($this->isMultiGroup()) {
                    	$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $usergroups, $this_control_name.'['.$jfGroupCount.'][]', 'MULTIPLE SIZE="10"', 'id', 'name', $select_value) . '</td>';
                    } else {
                    	$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $usergroups, $this_control_name.'['.$jfGroupCount.'][]', '', 'id', 'name', $select_value) . '</td>';
                    }
                    
                    $checked = '';
                    if ($multiusergroupdefault == $key) {
                    	$checked = 'checked';
                    }
					$advanced_usergroup.= '<td><input type="radio" '.$checked.' name="'.$control_name . '[' . $name . 'default]" value="'.$jfGroupCount.'"></td>';                    
					$advanced_usergroup.= '<td><a href="javascript:removeRow('.$jfGroupCount.')">Remove</a></td>';
                    
					$advanced_usergroup.= '</tr>';
				}
            } else {
				$jfGroupCount++;
				$select_value = '';
				$advanced_usergroup.= '<tr id="usergroups_row'.$jfGroupCount.'">';
				if ($JFusionMaster->isMultiGroup()) {
					$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $master_usergroups , $master_control_name.'['.$jfGroupCount.'][]', 'MULTIPLE SIZE="10"', 'id', 'name', $select_value) . '</td>';	                   		
				} else {
					$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $master_usergroups , $master_control_name.'['.$jfGroupCount.'][]', '', 'id', 'name', $select_value) . '</td>';	                   		
				}
                   
				$select_value = '';
                if ($this->isMultiGroup()) {
					$advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $usergroups, $this_control_name.'['.$jfGroupCount.'][]', 'MULTIPLE SIZE="10"', 'id', 'name', $select_value) . '</td>';
				} else {
                    $advanced_usergroup.= '<td>' . JHTML::_('select.genericlist', $usergroups, $this_control_name.'['.$jfGroupCount.'][]', '', 'id', 'name', $select_value) . '</td>';
				}   
				$checked = 'checked';
				$advanced_usergroup.= '<td><input type="radio" '.$checked.' name="'.$control_name . '[' . $name . 'default]" value="'.$jfGroupCount.'"></td>';
				$advanced_usergroup.= '<td><a href="javascript:removeRow('.$jfGroupCount.')">Remove</a></td>';
                    
				$advanced_usergroup.= '</tr>';
            }
            
            $advanced_usergroup.= "</table>";
            //escape single quotes to prevent JS errors
            $advanced_usergroup = str_replace("'", "\'", $advanced_usergroup);
        } else {
            $advanced_usergroup = '';
        }
        $list_box.= '</select>';
        
        if (!empty($master)) {
	        $new = new stdClass;
	        if ($master_usergroups) {
		    	foreach ($master_usergroups as $master_usergroup) {
					$key = $master_usergroup->id;
					$new->$key = $master_usergroup->name;
				}
	        }
			$master_usergroups = $new;
	
	        $new = new stdClass;
	        if ($master_usergroups) {        
				foreach ($usergroups as $usergroup) {
					$key = $usergroup->id;
					$new->$key = $usergroup->name;
				}
	        }
			$usergroups = $new;
        	
	        $plugin['name'] = $this->getJname();
	        $plugin['master'] = $JFusionMaster->getJname();
	        $plugin['count'] = $jfGroupCount;
	        $plugin[$this->getJname()]['groups'] = $usergroups;
	        $plugin[$this->getJname()]['type'] = $this->isMultiGroup() ? 'multi':'single';
	        $plugin[$JFusionMaster->getJname()]['groups'] = $master_usergroups;
			$plugin[$JFusionMaster->getJname()]['type'] = $JFusionMaster->isMultiGroup() ? 'multi':'single';
        }
        
        ?><script type="text/javascript">
			var jfPlugin = <?php echo json_encode($plugin);?>
	        
	        function usergroupSelect(option)
	        {
	            var myArray = new Array();
	            myArray[0] = '<?php echo $simple_usergroup; ?>';
	            myArray[1] = '<?php echo $advanced_usergroup; ?>';
	            document.getElementById("JFusionUsergroup").innerHTML = myArray[option];

	            var addgroupset = document.getElementById('addgroupset');
	            if (option== 1) {
	            	addgroupset.style.display = 'block';
	            } else {
	            	addgroupset.style.display = 'none';
	            }
	        }

	        function addRow() {
	        	jfPlugin['count']++;
	        	var count = jfPlugin['count'];

	        	var master = jfPlugin['master'];
	        	var name = jfPlugin['name'];
	        	
	        	var elTrNew = document.createElement('tr');
	        	elTrNew.id = 'usergroups_row'+count;

	        	var elTdmaster = document.createElement('td');
	        	elTdmaster.appendChild(createSelect(master));
	        	  
	        	var elTdjname = document.createElement('td');
	        	elTdjname.appendChild(createSelect(name));

	        	var elInputNew = document.createElement('input');
	        	elInputNew.type = 'radio';
	        	elInputNew.name = 'params[multiusergroupdefault]';
				elInputNew.value = count;
					        	
	        	var elTddefault = document.createElement('td');
	        	elTddefault.appendChild(elInputNew);

	        	var elANew = document.createElement('a');	        	
	        	elANew.href = 'javascript:removeRow('+count+')';
	        	elANew.innerHTML = 'Remove';
	        	
	        	var elTdremove = document.createElement('td');
	        	elTdremove.appendChild(elANew);
	        	  
	        	elTrNew.appendChild(elTdmaster);
	        	elTrNew.appendChild(elTdjname);
	        	elTrNew.appendChild(elTddefault);
	        	elTrNew.appendChild(elTdremove);
	        	var divEls = document.getElementById("usergroups");
	        	divEls.appendChild(elTrNew);
	        }

	        function removeRow(row) {
	        	var trEl = document.getElementById("usergroups_row"+row);
	        	trEl.style.display = 'none';
	        	trEl.innerHTML = '';
	        }
	        
	        function createSelect(name,target) {
	        	var count = jfPlugin['count'];
	        	var type = jfPlugin[name]['type'];
	        	var groups = jfPlugin[name]['groups'];
	        		        	
				var elSelNew = document.createElement('select');
				if (type=='multi') {
					elSelNew.size=10;
					elSelNew.multiple='multiple';
				}
				elSelNew.name='params[multiusergroup]['+name+']['+count+'][]';
				for (x in groups) {
					var elOptNew = document.createElement('option');
					elOptNew.text = groups[x];
					elOptNew.value = x;
					elSelNew.appendChild(elOptNew);
				}
				return elSelNew;
	        }
        </script>
        <?php
        $addbutton='';
       	$return = '<table><tr><td>'.JText::_('USERGROUP'). ' '. JText::_('MODE').'</td><td>'.$list_box.'</td></tr><tr><td colspan="2"><div id="JFusionUsergroup">';
       	if ($advanced == 1) {
       	    if ((!empty($master) && $JFusionMaster->isMultiGroup())||$this->isMultiGroup()) {
				$addbutton = '<a id="addgroupset" href="javascript:addRow()">Add Group Pair</a>';
       		}
       		$return .= $advanced_usergroup;
       	} else {
       		if ((!empty($master) && $JFusionMaster->isMultiGroup())||$this->isMultiGroup()) {
				$addbutton = '<a id="addgroupset" style="display: none;" href="javascript:addRow()">Add Group Pair</a>';
        	}
        	$return .= $simple_usergroup;
        }
		$return .= '</div>'.$addbutton.'</td></tr></table>';
        return $return;
    }

    /**
     * Function returns the path to the modfile
     *
     * @param string $filename file name
     * @param int    &$error   error number
     * @param string &$reason  error reason
     *
     * @return string $mod_file path and file of the modfile.
     */
    function getModFile($filename, &$error, &$reason)
    {
        //check to see if a path is defined
        $params = JFusionFactory::getParams($this->getJname());
        $path = $params->get('source_path');
        if (empty($path)) {
            $error = 1;
            $reason = JText::_('SET_PATH_FIRST');
        }
        //check for trailing slash and generate file path
        if (substr($path, -1) == DS) {
            $mod_file = $path . $filename;
        } else {
            $mod_file = $path . DS . $filename;
        }
        //see if the file exists
        if (!file_exists($mod_file) && $error == 0) {
            $error = 1;
            $reason = JText::_('NO_FILE_FOUND');
        }
        return $mod_file;
    }

    /**
     * Called when JFusion is uninstalled so that plugins can run uninstall processes such as removing auth mods
     * @return array    [0] boolean true if successful uninstall
     *                  [1] mixed reason(s) why uninstall was unsuccessful
     */
    function uninstall()
    {
        return array(true, '');
    }
    
	/*
	 * do plugin support multi usergroups
	 */
	function isMultiGroup()
	{
		static $muiltisupport;
		if (!isset($muiltisupport)) {
			$params = JFusionFactory::getParams($this->getJname());
			$multiusergroup = $params->get('multiusergroup',null);
			if ($multiusergroup !== null) {
				$muiltisupport = true;
			} else {
				$muiltisupport = false;
			}
		}
		return $muiltisupport;
	}

	/*
	 * do plugin support multi usergroups
	 * return UNKNOWN for unknown
	 * return JNO for NO
	 * return JYES for YES
	 * return other ??
	 */
	function requireFileAccess()
	{
		return 'UNKNOWN';
	}
	
	/*
	 * import function for importing config in to a plugin
	*/
	function import($name, $value, $node, $control_name)
	{
		$jname = $this->getJname();
		$action = JRequest::getVar('action');
		$VersionCurrent = JFusionFunctionAdmin::currentVersion();
	
		$document =& JFactory::getDocument();
	
		$js = 'function doImport() {
		document.adminForm.action.value=\'import\';
		document.adminForm.jname.value=\''.$jname.'\';
		document.adminForm.encoding=\'multipart/form-data\';
		submitbutton(\'plugineditor\');
	}';
	
		$document->addScriptDeclaration($js);
	
		$output = '<input name="file" size="60" type="file"><br/>
		<table style="border: 0px;"><tr style="padding: 0px;"><td style="padding: 0px; width: 150px;">'.JText::_('DATABASE_TYPE').'</td><td style="padding: 0px;"><input name="database_type" id="database_type" value="" class="text_area" size="20" type="text"></td></tr>
		<tr style="padding: 0px;"><td style="padding: 0px; width: 150px;">'.JText::_('DATABASE_HOST').'</td><td style="padding: 0px;"><input name="database_host" id="database_host" value="" class="text_area" size="20" type="text"></td></tr>
		<tr style="padding: 0px;"><td style="padding: 0px; width: 150px;">'.JText::_('DATABASE_NAME').'</td><td style="padding: 0px;"><input name="database_name" id="database_name" value="" class="text_area" size="20" type="text"></td></tr>
		<tr style="padding: 0px;"><td style="padding: 0px; width: 150px;">'.JText::_('DATABASE_USER').'</td><td style="padding: 0px;"><input name="database_user" id="database_user" value="" class="text_area" size="20" type="text"></td></tr>
		<tr style="padding: 0px;"><td style="padding: 0px; width: 150px;">'.JText::_('DATABASE_PASSWORD').'</td><td style="padding: 0px;"><input name="database_password" id="database_password" value="" class="text_area" size="20" type="text"></td></tr>
		<tr style="padding: 0px;"><td style="padding: 0px; width: 150px;">'.JText::_('DATABASE_PREFIX').'</td><td style="padding: 0px;"><input name="database_prefix" id="database_prefix" value="" class="text_area" size="20" type="text"></td></tr></table>';
	
		//custom for development purposes / local use only; note do not commit your URL to SVN!!!
		$developmentURL = '';
		$url = (empty($developmentURL)) ? ($VersionCurrent=='SVN') ? 'http://jfusion.googlecode.com/svn/trunk/jfusion_universal.xml' : 'http://www.jfusion.org/jfusion_universal.xml' : $developmentURL;
		$ConfigList = JFusionFunctionAdmin::getFileData($url);
		if(empty($ConfigList)){
			//try a mirror
			$url = (empty($developmentURL)) ? 'http://jfusion.googlecode.com/svn/branches/jfusion_universal.xml' : 'http://jfusion.googlecode.com/svn/trunk/jfusion_universal.xml';
			$ConfigList = JFusionFunctionAdmin::getFileData($url);
		}
	
		$xmlList = JFactory::getXMLParser('Simple');
		$xmlList->loadString($ConfigList);
	
		if ( isset($xmlList->document) ) {
			$output .= JText::_('IMPORT_FROM_SERVER').'<br/>';
			$output .= '<input type=radio name="xmlname" value="" checked> None<br/>';
	
	
	
	
			foreach ($xmlList->document->children() as $key => $val) {
				$pluginName = $val->attributes('name');
				if ($pluginName) {
					$pluginVersion = $val->attributes('version')?$val->attributes('version'):JText::_('UNKNOWN');
					$pluginDesc = $val->attributes('desc')?$val->attributes('desc'):JText::_('NONE');
					$pluginCreator = $val->attributes('creator')?$val->attributes('creator'):JText::_('UNKNOWN');
					$output .= '<input type=radio name="xmlname" value="'.$pluginName.'"> '.ucfirst($pluginName).' <a href="javascript: doShowHide(\'plugin'.$key.'\');" id="xplugin'.$key.'"">[+]</a><div style="display:none;" id="plugin'.$key.'">';
					$output .= JText::_('VERSION').': '.$pluginVersion.'<br/>';
					$output .= JText::_('DESCRIPTION').': '.ucfirst($pluginDesc).'<br/>';
					$output .= JText::_('CREATOR').': '.$pluginCreator.'</div><br/>';
				}
			}
			$output .= '<div style="text-align: right;"><input type="Button" onclick="javascript: doImport();" value="'.JText::_('IMPORT').'" /></div>';
		} else {
			$output .= 'No conection to jfusion Server, try import later!!<br/>';
		}
	
		if( $action == 'import' && isset($xmlList->document) ) {
			jimport('joomla.utilities.simplexml');
			$file = JRequest::getVar( 'file', '', 'FILES','ARRAY');
	
			$xmlname = JRequest::getVar('xmlname');
	
			$xmlFile = JFactory::getXMLParser('Simple');
			if( !empty($xmlname) ) {
				//custom for development purposes / local use only; note do not commit your URL to SVN!!!
				$developmentURL = '';
				$url = (empty($developmentURL)) ? ($VersionCurrent=='SVN') ? 'http://jfusion.googlecode.com/svn/trunk/configs/jfusion_'.$xmlname.'_config.xml' : 'http://www.jfusion.org/configs/jfusion_'.$xmlname.'_config.xml' : $developmentURL;
				$ConfigFile = JFusionFunctionAdmin::getFileData($url);
				if(empty($ConfigFile)){
					//try a mirror
					$url = (empty($developmentURL)) ? 'http://jfusion.googlecode.com/svn/branches/configs/jfusion_'.$xmlname.'_config.xml' : 'http://jfusion.googlecode.com/svn/trunk/configs/jfusion_'.$xmlname.'_config.xml';
					$ConfigFile = JFusionFunctionAdmin::getFileData($url);
				}
				if ( !empty($ConfigFile) ) {
					$xmlFile->loadString($ConfigFile);
				} else {
					JError::raiseWarning(0, $jname . ': ' . JText::_('ERROR_DOWNLOADING_FILE') );
					return $output;
				}
			} else {
				if( $file['error'] > 0 ) {
					$msg = '';
					switch ($file['error']) {
						case UPLOAD_ERR_INI_SIZE:
							$msg = JText::_('UPLOAD_ERR_INI_SIZE');
							break;
						case UPLOAD_ERR_FORM_SIZE:
							$msg = JText::_('UPLOAD_ERR_FORM_SIZE');
							break;
						case UPLOAD_ERR_PARTIAL:
							$msg = JText::_('UPLOAD_ERR_PARTIAL');
							break;
						case UPLOAD_ERR_NO_FILE:
							$msg = JText::_('UPLOAD_ERR_NO_FILE');
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$msg = JText::_('UPLOAD_ERR_NO_TMP_DIR');
							break;
						case UPLOAD_ERR_CANT_WRITE:
							$msg = JText::_('UPLOAD_ERR_CANT_WRITE');
							break;
						case UPLOAD_ERR_EXTENSION:
							$msg = JText::_('UPLOAD_ERR_EXTENSION');
							break;
						default:
							$msg = 'Unknown upload error';
					}
					JError::raiseWarning(0, $jname . ': ' . JText::_('ERROR').': '.$msg );
					return $output;
				} else {
					if(!$xmlFile->loadFile($file['tmp_name']) ) {
						JError::raiseWarning(0, $jname . ': ' . JText::_('ERROR_LOADING_FILE').': '.$file['tmp_name'] );
						return $output;
					}
				}
			}
	
			$info = $config = null;
			foreach ($xmlFile->document->children() as $key => $val) {
				switch ($val->name()) {
					case 'info':
						$info = $val;
						break;
					case 'config':
						$config = $val->children();
						break;
				}
			}
	
			if (!$info || !$config) {
				JError::raiseWarning(0, $jname . ': ' . JText::_('ERROR_FILE_SYNTAX').': '.$file['type'] );
				return $output;
			}
	
			$conf = array();
			foreach ($config as $key => $val) {
				$conf[$val->attributes('name')] = htmlspecialchars_decode($val->data());
				if ( strpos($conf[$val->attributes('name')], 'a:') === 0 ) $conf[$val->attributes('name')] = unserialize($conf[$val->attributes('name')]);
			}
	
			$database_type = JRequest::getVar('database_type');
			$database_host = JRequest::getVar('database_host');
			$database_name = JRequest::getVar('database_name');
			$database_user = JRequest::getVar('database_user');
			$database_password = JRequest::getVar('database_password');
			$database_prefix = JRequest::getVar('database_prefix');
	
			if( !empty($database_type) ) $conf['database_type'] = $database_type;
			if( !empty($database_host) ) $conf['database_host'] = $database_host;
			if( !empty($database_name) ) $conf['database_name'] = $database_name;
			if( !empty($database_user) ) $conf['database_user'] = $database_user;
			if( !empty($database_password) ) $conf['database_password'] = $database_password;
			if( !empty($database_prefix) ) $conf['database_prefix'] = $database_prefix;
	
			JFusionFunctionAdmin::saveParameters($jname, $conf);
	
			$mainframe = & JFactory::getApplication();
			$mainframe->redirect('index.php?option=com_jfusion&task=plugineditor&jname='.$jname,JText::_('IMPORT_SUCCESS_MSG_PRESS_SAVE'));
			exit();
		}
	
		return $output;
	}
	
	/*
	 * export function for importing config in to a plugin
	*/
	function export($name, $value, $node, $control_name)
	{
		$jname = $this->getJname();
		$params = JFusionFactory::getParams($jname);
		$action = JRequest::getVar('action');
		$dbinfo = JRequest::getVar('dbinfo');
	
		if( $action == 'export' ) {
			jimport('joomla.utilities.simplexml');
	
			$arr = array();
			foreach ($params->_registry["_default"]["data"] as $key => $val) {
				if( !$dbinfo && substr($key,0,8) == 'database' && substr($key,0,13) != 'database_type' ) {
					continue;
				}
				$arr[$key] = $val;
			}
	
			$xml = JFactory::getXMLParser('Simple');
			$xml->loadString('<jfusionconfig></jfusionconfig>');
	
			$info = $xml->document->addChild('info');
			$info->addAttribute  ('jfusionversion',  JFusionFunctionAdmin::currentVersion());
	
			//get the current JFusion version number
			$filename = JFUSION_PLUGIN_PATH .DS.$jname.DS.'jfusion.xml';
			if (file_exists($filename) && is_readable($filename)) {
				//get the version number
				$parser = JFactory::getXMLParser('Simple');
				$parser->loadFile($filename);
				$info->addAttribute('pluginversion', $parser->document->version[0]->data());
			} else {
				$info->addAttribute('pluginversion', 'UNKNOWN');
			}
	
			$info->addAttribute('date', date("F j, Y, H:i:s"));
	
			$info->addAttribute  ('jname', $jname);
	
			$db =& JFactory::getDBO();
			$query = 'SELECT original_name FROM #__jfusion WHERE name =' . $db->Quote($jname);
			$db->setQuery($query);
			$result = $db->loadResult();
	
			$info->addAttribute  ('original_name', $result);
	
			$config = $xml->document->addChild('config');
	
			foreach ($arr as $key => $val) {
				$attrs = array();
				$attrs['name'] = $key;
				$node = $config->addChild('key',$attrs);
				if (is_array($val)) $val = serialize($val);
				$node->setData($val);
			}
	
			header('Content-disposition: attachment; filename=jfusion_'.$jname.'_config.xml');
			header('content-type: text/xml');
			header('Pragma: no-cache');
			header('Expires: 0');
	
			echo $xml->document->toString();
	
			exit();
		}
	
		$document =& JFactory::getDocument();
	
		$js = 'function doExport() {
		document.adminForm.action.value=\'export\';
		document.adminForm.jname.value=\''.$jname.'\';
		submitbutton(\'plugineditor\');
	}';
		$document->addScriptDeclaration($js);
	
		$output = JText::_('EXPORT_DATABASE_INFO').' <input name="dbinfo" type="checkbox"><br/>';
		$output .= '<div style="text-align: right;"><input type="Button" onclick="javascript: doExport();" value="'.JText::_('EXPORT').'" /></div>';
		return $output;
	}
	
	/*
	 * mapping out extra header parsers
	*/
	function headermap($name, $value, $node, $control_name)
	{
		$params = JFusionFactory::getParams($this->getJname());
		$value = $params->get('headermap');
		return $this->pair('map', $value, $node, $control_name,'header');
	}
	
	/*
	 * mapping out extra body parsers
	*/
	function bodymap($name, $value, $node, $control_name)
	{
		$params = JFusionFactory::getParams($this->getJname());
		$value = $params->get('bodymap');
		return $this->pair('map', $value, $node, $control_name,'body');
	}
	
	/*
	 * shared code for headermap and bodymap to display pairs.
	*/
	function pair($name, $value, $node, $control_name,$type)
	{
		if (is_string($value)) $value = unserialize($value);
	
		$type .= $name;
	
		$output = '';
	
		if (!is_array($value)) {
			$output .= '<input type="text" name="params['.$type.'][value][0]" id="params'.$type.'value0" size="100"/>';
			$output .= '<input type="text" name="params['.$type.'][name][0]" id="params'.$type.'name0" size="100"/>';
			$output .= '<div id="params'.$type.'"></div>';
		} else {
			$i = 0;
			foreach ($value['value'] as $key => $val) {
				$val = htmlentities($val);
				$name = htmlentities($value['name'][$key]);
				if ( $i ) $output .= '<div id="params'.$type.$i.'">';
				$output .= '<input value="'.$val.'" type="text" name="params['.$type.'][value]['.$i.']" id="params'.$type.'value'.$i.'" size="60" />';
				$output .= '<input value="'.$name.'" type="text" name="params['.$type.'][name]['.$i.']" id="params'.$type.'name'.$i.'" size="60" />';
				if ( $i ) {
					$output .= '<a href="javascript:removePair(\''.$type.'\',\''.$type.$i.'\');">Delete</a></div>';
				} else {
					$output .= '<div id="params'.$type.'">';
				}
				$i++;
			}
			$output .= '</div>';
		}
	
		$output .= '<div id="add'.$type.'" style="display:block;"><a href="javascript:addPair(\''.$type.'\',100);">Add Another Pair</a></div>';
	
		return $output;
	}
}
