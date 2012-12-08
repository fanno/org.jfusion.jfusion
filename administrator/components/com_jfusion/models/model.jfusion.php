<?php

/**
 * Model for all jfusion related function
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

/**
 * Class for general JFusion functions
 *
 * @category  JFusion
 * @package   Models
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org
 */
class JFusionFunction
{
	/**
	 * Returns the JFusion plugin name of the software that is currently the master of user management
	 *
	 * @return object master details
	 */
	public static function getMaster()
	{
		static $jfusion_master;
		if (!isset($jfusion_master)) {
			$db = JFactory::getDBO();
			$query = 'SELECT * from #__jfusion WHERE master = 1 and status = 1';
			$db->setQuery($query);
			$jfusion_master = $db->loadObject();
			if ($jfusion_master) {
				return $jfusion_master;
			}
		} else {
			return $jfusion_master;
		}
	}
	
	/**
	 * Returns the JFusion plugin name of the software that are currently the slaves of user management
	 *
	 * @return object slave details
	 */
	public static function getSlaves()
	{
		static $jfusion_slaves;
		if (!isset($jfusion_slaves)) {
			$db = JFactory::getDBO();
			$query = 'SELECT * from #__jfusion WHERE slave = 1 and status = 1';
			$db->setQuery($query);
			$jfusion_slaves = $db->loadObjectList();
		}
		return $jfusion_slaves;
	}
	
	/**
	 * By default, returns the JFusion plugin of the software that is currently the slave of user management, minus the joomla_int plugin.
	 * If activity, search, or discussion is passed in, returns the plugins with that feature enabled
	 *
	 * @param array $criteria the type of plugins to retrieve
	 *
	 * @return object plugin details
	 */
	public static function getPlugins($criteria = 'slave')
	{
		switch ($criteria) {
			case 'slave':
				static $slave_plugins;
				break;
			case 'activity':
				static $activity_plugins;
				break;
			case 'search':
				static $search_plugins;
				break;
			case 'discussion':
				static $discussion_plugins;
				break;
			default:
				return;
		}
		$plugins =& ${
			$criteria . "_plugins"};
			if (empty($plugins)) {
				$query = "SELECT * FROM #__jfusion WHERE ($criteria = 1 AND status = 1 AND name NOT LIKE 'joomla_int')";
				$db = JFactory::getDBO();
				$db->setQuery($query);
				$plugins = $db->loadObjectList();
			}
			return $plugins;
	}
	
    /**
     * Changes plugin status in both Joomla 1.5 and Joomla 1.6
     *
     * @return object master details
     */
	public static function getPluginStatus($element,$folder) {
		//get joomla specs
        $db = JFactory::getDBO();
        if(JFusionFunction::isJoomlaVersion('1.6')){
            $query = 'SELECT published FROM #__extensions WHERE element=' . $db->Quote($element) . ' AND folder=' . $db->Quote($folder);
        } else {
            $query = 'SELECT published FROM #__plugins WHERE element=' . $db->Quote($element) . ' AND folder=' . $db->Quote($folder);
        }
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
	}

    /**
     * Creates a JFusion Joomla compatible URL
     *
     * @param string  $url    string url to be parsed
     * @param string  $itemid string itemid of the JFusion menu item or the name of the plugin for direct link
     * @param string  $jname  optional jname if available to prevent having to find it based on itemid
     * @param boolean $route  boolean optional switch to send url through JRoute::_() (true by default)
     * @param boolean $xhtml  boolean optional switch to turn & into &amp; if $route is true (true by default)
     *
     * @return string Parsed URL
     */
    public static function routeURL($url, $itemid, $jname = '', $route = true, $xhtml = true)
    {
        if (!is_numeric($itemid)) {
            if ($itemid == 'joomla_int') {
                //special handling for internal URLs
                if ($route) {
                    return JRoute::_($url, $xhtml);
                } else {
                    return $url;
                }
            } else {
                //we need to create direct link to the plugin
                $params = & JFusionFactory::getParams($itemid);
                $url = $params->get('source_url') . $url;
                if ($xhtml) {
                    $url = str_replace('&', '&amp;', $url);
                }
                return $url;
            }
        } else {
            //we need to create link to a joomla itemid
            if (empty($jname)) {
                //determine the jname from the plugin
                static $routeURL_jname;
                if (!is_array($routeURL_jname)) {
                    $routeURL_jname = array();
                }
                if (!isset($routeURL_jname[$itemid])) {
                    $menu = & JSite::getMenu();
                    $menu_param = & $menu->getParams($itemid);
                    $plugin_param = unserialize(base64_decode($menu_param->get('JFusionPluginParam')));
                    $routeURL_jname[$itemid] = $plugin_param['jfusionplugin'];
                    $jname = $routeURL_jname[$itemid];
                } else {
                    $jname = $routeURL_jname[$itemid];
                }
            }
            //make the URL relative so that external software can use this function
            $params = & JFusionFactory::getParams($jname);
            $source_url = $params->get('source_url');
            $url = str_replace($source_url, '', $url);

            $config = JFactory::getConfig();
            $sefenabled = $config->getValue('config.sef');
            $params = & JFusionFactory::getParams($jname);
            $sefmode = $params->get('sefmode', 1);
            if ($sefenabled && !$sefmode) {
                //otherwise just tak on the URL
                $baseURL = JFusionFunction::getPluginURL($itemid, false);
                $url = $baseURL . $url;
                if ($xhtml) {
                    $url = str_replace('&', '&amp;', $url);
                }
            } else {
                //fully parse the URL if sefmode = 1
                $u = & JURI::getInstance($url);
                $u->setVar('jfile', $u->getPath());
                $u->setVar('option', 'com_jfusion');
                $u->setVar('Itemid', $itemid);
                $query = $u->getQuery(false);
                $fragment = $u->getFragment();
                if (isset($fragment)) {
                    $query.= '#' . $fragment;
                }
                if ($route) {
                    $url = JRoute::_('index.php?' . $query, $xhtml);
                } else {
                    $url = 'index.php?' . $query;
                }
            }

            return $url;
        }
    }

    /**
     * Returns either the Joomla wrapper URL or the full URL directly to the forum
     *
     * @param string $url    relative path to a webpage of the integrated software
     * @param string $jname  name of the JFusion plugin used
     * @param string $view   name of the JFusion view used
     * @param string $itemid the itemid
     *
     * @return string full URL to the filename passed to this function
     */
    public static function createURL($url, $jname, $view, $itemid = '')
    {
        if (!empty($itemid)) {
            //use the itemid only to identify plugin name and view type
            $base_url = 'index.php?option=com_jfusion&amp;Itemid=' . $itemid;
        } else {
            $base_url = 'index.php?option=com_jfusion&amp;Itemid=-1&amp;view=' . $view . '&amp;jname=' . $jname;
        }
        if ($view == 'direct') {
            $params = & JFusionFactory::getParams($jname);
            $url = $params->get('source_url') . $url;
            return $url;
        } elseif ($view == 'wrapper') {
            //use base64_encode to encode the URL for passing.  But, base64_code uses / which throws off SEF urls.  Thus slashes
            //must be translated into something base64_encode will not generate and something that will not get changed by Joomla or Apache.
            $url = $base_url . '&amp;wrap=' . str_replace("/", "_slash_", base64_encode($url));
            $url = JRoute::_($url);
            return $url;
        } elseif ($view == 'frameless') {
            //split the filename from the query
            $parts = explode('?', $url);
            if (isset($parts[1])) {
                $base_url.= '&amp;jfile=' . $parts[0] . '&amp;' . $parts[1];
            } else {
                $base_url.= '&amp;jfile=' . $parts[0];
            }
            $url = JRoute::_($base_url);
            return $url;
        }
    }

    /**
     * Updates the JFusion user lookup table during login
     *
     * @param object  $userinfo  object containing the userdata
     * @param string  $joomla_id The Joomla ID of the user
     * @param string  $jname     name of the JFusion plugin used
     * @param boolean $delete    deletes an entry from the table
     *
     * @return string nothing
     */
    public static function updateLookup($userinfo, $joomla_id, $jname = '', $delete = false)
    {
        $db = JFactory::getDBO();
        //we don't need to update the lookup for internal joomla unless deleting a user
        if ($jname == 'joomla_int') {
            if ($delete) {
                //Delete old user data in the lookup table
                $query = 'DELETE FROM #__jfusion_users WHERE id =' . $joomla_id . ' OR username = ' . $db->Quote($userinfo->username) . ' OR LOWER(username) = ' . strtolower($db->Quote($userinfo->email));
                $db->setQuery($query);
                if (!$db->query()) {
                    JError::raiseWarning(0, $db->stderr());
                }
                //Delete old user data in the lookup table
                $query = 'DELETE FROM #__jfusion_users_plugin WHERE id =' . $joomla_id . ' OR username = ' . $db->Quote($userinfo->username) . ' OR LOWER(username) = ' . strtolower($db->Quote($userinfo->email));
                $db->setQuery($query);
                if (!$db->query()) {
                    JError::raiseWarning(0, $db->stderr());
                }
            }
            return;
        }
        //check to see if we have been given a joomla id
        if (empty($joomla_id)) {
            $query = "SELECT id FROM #__users WHERE username = " . $db->Quote($userinfo->username);
            $db->setQuery($query);
            $joomla_id = $db->loadResult();
            if (empty($joomla_id)) {
                return;
            }
        }
        if (empty($jname)) {
            $queries = array();
            //we need to update each master/slave
            $query = "SELECT name FROM #__jfusion WHERE master = 1 OR slave = 1";
            $db->setQuery($query);
            $jnames = $db->loadObjectList();
            foreach ($jnames as $jname) {
                if ($jname != "joomla_int") {
                    $user = & JFusionFactory::getUser($jname->name);
                    $puserinfo = $user->getUser($userinfo);
                    if ($delete) {
                        $queries[] = "(id = $joomla_id AND jname = " . $db->Quote($jname->name) . ")";
                    } else {
                        $queries[] = "(" . $db->Quote($puserinfo->userid) . "," . $db->Quote($puserinfo->username) . ", $joomla_id, " . $db->Quote($jname->name) . ")";
                    }
                    unset($user);
                    unset($puserinfo);
                }
            }
            if (!empty($queries)) {
                if ($delete) {
                    $query = "DELETE FROM #__jfusion_users_plugin WHERE " . implode(' OR ', $queries);
                } else {
                    $query = "REPLACE INTO #__jfusion_users_plugin (userid,username,id,jname) VALUES (" . implode(',', $queries) . ")";
                }
                $db->setQuery($query);
                if (!$db->query()) {
                    JError::raiseWarning(0, $db->stderr());
                }
            }
        } else {
            if ($delete) {
                $query = "DELETE FROM #__jfusion_users_plugin WHERE id = $joomla_id AND jname = '$jname'";
            } else {
                $query = "REPLACE INTO #__jfusion_users_plugin (userid,username,id,jname) VALUES ({$db->Quote($userinfo->userid) },{$db->Quote($userinfo->username) },$joomla_id,{$db->Quote($jname) })";
            }
            $db->setQuery($query);
            if (!$db->query()) {
                JError::raiseWarning(0, $db->stderr());
            }
        }
    }

    /**
     * Returns the userinfo data for JFusion plugin based on the userid
     *
     * @param string  $jname      name of the JFusion plugin used
     * @param string  $userid     The ID of the user
     * @param boolean $isJoomlaId if true, returns the userinfo data based on Joomla, otherwise the plugin
     * @param string  $username   If the userid is that of the plugin's, we need the username to find the user in case there is no record in the lookup table
     *
     * @return object database Returns the userinfo as a Joomla database object
     *
     */
    public static function lookupUser($jname, $userid, $isJoomlaId = true, $username = '')
    {
        //initialise some vars
        $db = JFactory::getDBO();
        $result = '';
        if (!empty($userid)) {
            $column = ($isJoomlaId) ? 'a.id' : 'a.userid';
            $query = 'SELECT a.*, b.email FROM #__jfusion_users_plugin AS a INNER JOIN #__users AS b ON a.id = b.id WHERE ' . $column . ' = ' . $db->Quote($userid) . ' AND a.jname = ' . $db->Quote($jname);
            $db->setQuery($query);
            $result = $db->loadObject();
        }
        //for some reason this user is not in the lookup table so let's find them
        if (empty($result)) {
            if ($isJoomlaId) {
                //we have a joomla id so let's setup a temp $userinfo
                $query = "SELECT username, email FROM #__users WHERE id = $userid";
                $db->setQuery($query);
                $result = $db->loadResult();
                $joomla_id = $userid;
            } else {
                //we have a plugin's id so we need to find Joomla's id then setup a temp $userinfo
                //first try JFusion's user table
                $query = "SELECT a.id, a.email FROM #__users AS a INNER JOIN #__jfusion_users as b ON a.id = b.id WHERE b.username = " . $db->Quote($username);
                $db->setQuery($query);
                $result = $db->loadObject();
                //not created by JFusion so let's check the Joomla table directly
                if (empty($result)) {
                    $query = "SELECT id, email FROM #__users WHERE username = " . $db->Quote($username);
                    $db->setQuery($query);
                    $result = $db->loadObject();
                }
                if (!empty($result)) {
                    //we have a user
                    $result->username = $username;
                    $joomla_id = $result->id;
                }
            }
            if (!empty($result) && !empty($joomla_id) && !empty($jname)) {
                //get the plugin's userinfo - specifically we need the userid which it will provide
                $user = & JFusionFactory::getUser($jname);
                $existinguser = $user->getUser($result);
                if (!empty($existinguser)) {
                    //update the lookup table with the new acquired info
                    JFusionFunction::updateLookup($existinguser, $joomla_id, $jname);
                    //return the results
                    $result = new stdClass();
                    $result->userid = $existinguser->userid;
                    $result->username = $existinguser->username;
                    $result->id = $joomla_id;
                    $result->jname = $jname;
                } else {
                    //the user does not exist in the software which means they were probably a guest or deleted from the integrated software
                    //we can't create the user as we have no password
                    $result = new stdClass();
                    $result->userid = '0';
                    $result->username = $username;
                    $result->id = $joomla_id;
                    $result->jname = $jname;
                }
            }
        }
        return $result;
    }

    /**
     * Checks to see if a JFusion plugin is properly configured
     *
     * @param string $jname name of the JFusion plugin used
     *
     * @return bolean returns true if plugin is correctly configured
     */
    public static function validPlugin($jname)
    {
        $db = JFactory::getDBO();
        $query = 'SELECT status FROM #__jfusion WHERE name =' . $db->Quote($jname);
        $db->setQuery($query);
        $result = $db->loadResult();
        if ($result == '1') {
            $result = true;
            return $result;
        } else {
            $result = false;
            return $result;
        }
    }

    /**
     * Delete old user data in the lookup table
     *
     * @param object $userinfo userinfo of the user to be deleted
     *
     * @return string nothing
     */
    public static function removeUser($userinfo)
    {
        //Delete old user data in the lookup table
        $db = JFactory::getDBO();
        $query = 'DELETE FROM #__jfusion_users WHERE id =' . $userinfo->id . ' OR username =' . $db->Quote($userinfo->username) . ' OR LOWER(username) = ' . strtolower($db->Quote($userinfo->email));
        $db->setQuery($query);
        if (!$db->query()) {
            JError::raiseWarning(0, $db->stderr());
        }
        $query = 'DELETE FROM #__jfusion_users_plugin WHERE id =' . $userinfo->id;
        $db->setQuery($query);
        if (!$db->query()) {
            JError::raiseWarning(0, $db->stderr());
        }
    }

    /**
     * Adds a cookie to the php header
     *
     * @param string $name         cookie name
     * @param string $value        cookie value
     * @param int    $expires_time cookie expiry time
     * @param string $cookiepath   cookie path
     * @param string $cookiedomain cookie domain
     * @param string $secure       is the secute
     * @param string $httponly     is the cookie http only
     *
     * @return string nothing
     */
    public static function addCookie($name, $value, $expires_time, $cookiepath, $cookiedomain, $secure=false, $httponly=false)
    {
    	$cookies = JFusionFactory::getCookies();
    	$cookies->addCookie($name, $value, $expires_time, $cookiepath, $cookiedomain, $secure, $httponly);
/*
        if ($expires_time != 0) {
            $expires = time() + intval($expires_time);
        } else {
            $expires = 0;
        }
        // Versions of PHP prior to 5.2 do not support HttpOnly cookies and IE is buggy when specifying a blank domain so set the cookie manually
        $cookie = "Set-Cookie: {$name}=" . urlencode($value);
        if ($expires > 0) {
            $cookie.= "; expires=" . gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires);
        }
        if (!empty($cookiepath)) {
            $cookie.= "; path={$cookiepath}";
        }
        if (!empty($cookiedomain)) {
            $cookie.= "; domain={$cookiedomain}";
        }
        if ($secure == true) {
            $cookie.= "; Secure";
        }
        if ($httponly == true) {
            $cookie.= "; HttpOnly";
        }
        header($cookie, false);
*/
        //$document    = JFactory::getDocument();
        //$document->addCustomTag($cookie);

    }

    /**
     * Raise warning function that can handle arrays
     *
     * @param string $type      type of warning
     * @param string $warning   warning itself
     * @param int    $redundant not used
     *
     * @return string nothing
     */
    public static function raiseWarning($type, $warning, $redundant = 0)
    {
        if (is_array($warning)) {
            foreach ($warning as $warningtype => $warningtext) {
                //if still an array implode for nicer display
                if (is_array($warningtext)) {
                    $warningtext = implode('</li><li>' . $warningtype . ': ', $warningtext);
                }
                JError::RaiseNotice('500', $warningtype . ': ' . $warningtext);
            }
        } else {
            JError::RaiseNotice('500', $type . ': ' . $warning);
        }
    }

    /**
     * Raise warning function that can handle arrays
     *
     * @return array array with the php info values
     */
    public static function phpinfoArray()
    {
        //get the phpinfo and parse it into an array
        ob_start();
        phpinfo();
        $phpinfo = array('phpinfo' => array());
        if (preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (strlen($match[1])) {
                    $phpinfo[$match[1]] = array();
                } else if (isset($match[3])) {
                    $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
                } else {
                    $phpinfo[end(array_keys($phpinfo))][] = $match[2];
                }
            }
        }
        return $phpinfo;
    }

	/**
	 * Updates the discussion bot lookup table
	 * @param $contentid
	 * @param $threadinfo object with postid, threadid, and forumid
	 * @param $jname
	 * @param $published
	 */
	public static function updateDiscussionBotLookup($contentid, &$threadinfo, $jname, $published = 1, $manual = 0)
	{
		$fdb = JFactory::getDBO();
		$modified = JFactory::getDate()->toUnix();
        $option = JRequest::getCmd('option');

        //populate threadinfo with other fields if necessary for content generation purposes
        //mainly used if the thread was just created
        if (empty($threadinfo->component)) {
            $threadinfo->contentid = $contentid;
            $threadinfo->component = $option;
            $threadinfo->modified = $modified;
            $threadinfo->jname = $jname;
            $threadinfo->published = $published;
            $threadinfo->manual = $manual;
        }

		$query = "REPLACE INTO #__jfusion_discussion_bot SET
					contentid = $contentid,
					component = {$fdb->Quote($option)},
					forumid = $threadinfo->forumid,
					threadid = $threadinfo->threadid,
					postid = $threadinfo->postid,
					modified = '$modified',
					jname = '$jname',
					published = $published,
					manual = $manual";
		$fdb->setQuery($query);
		$fdb->query();
	}

    /**
     * Creates the URL of a Joomla article
     *
     * @param string &$contentitem contentitem
     * @param string $text         string to place as the link
     * @param string $jname        jname
     *
     * @return string link
     */
    public static function createJoomlaArticleURL(&$contentitem, $text, $jname='')
    {
        $mainframe = JFactory::getApplication();
        $option = JRequest::getVar('option');

        if ($option == 'com_k2') {
            include_once JPATH_SITE . DS . 'components' . DS . 'com_k2' . DS . 'helpers' . DS . 'route.php';
            $article_url = urldecode(K2HelperRoute::getItemRoute($contentitem->id.':'.urlencode($contentitem->alias),$contentitem->catid.':'.urlencode($contentitem->category->alias)));
        } else {
            if (empty($contentitem->slug)) {
                //article was edited and saved from editor
                $db = JFactory::getDBO();
                $query = 'SELECT CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'.
                ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'.
                ' FROM #__content AS a' .
                ' LEFT JOIN #__categories AS cc ON a.catid = cc.id' .
                ' WHERE a.id = ' . $contentitem->id;
                $db->setQuery($query);
                $result = $db->loadObject();

                if (!empty($result)) {
                    $contentitem->slug = $result->slug;
                    $contentitem->catslug = $result->catslug;
                }
            }

            include_once JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php';
            if (JFusionFunction::isJoomlaVersion('1.6')) {
                $article_url = ContentHelperRoute::getArticleRoute($contentitem->slug, $contentitem->catslug);
            } else {
                $article_url = ContentHelperRoute::getArticleRoute($contentitem->slug, $contentitem->catslug, $contentitem->sectionid);
            }
        }

        if ($mainframe->isAdmin()) {
            //setup JRoute to use the frontend router
            $app    = JApplication::getInstance('site');
            $router = &$app->getRouter();
            $uri = $router->build($article_url);
            $article_url = $uri->toString();
            //remove /administrator from path
            $article_url = str_replace('/administrator', '', $article_url);
        } else {
            $article_url = JRoute::_($article_url);
        }

        //make the URL absolute and clean it up a bit
        $joomla_url = JFusionFunction::getJoomlaURL();

        $juri = new JURI($joomla_url);
        $path = $juri->getPath();
        if ($path != '/') {
            $article_url = str_replace($path, '', $article_url);
        }

        if (substr($joomla_url, -1) == '/') {
            if ($article_url[0] == '/') {
                $article_url = substr($joomla_url, 0, -1) . $article_url;
            } else {
                $article_url = $joomla_url . $article_url;
            }
        } else {
            if ($article_url[0] == '/') {
                $article_url = $joomla_url . $article_url;
            } else {
                $article_url = $joomla_url . '/' . $article_url;
            }
        }

        $link = "<a href='".$article_url."'>$text</a>";

        return $link;
    }

    /**
     * Parses text from bbcode to html, html to bbcode, or html to plaintext
     * $options include:
     * strip_all_html - if $to==bbcode, strips all unsupported html from text (default is false)
     * bbcode_patterns - if $to==bbcode, adds additional html to bbcode rules; array [0] startsearch, [1] startreplace, [2] endsearch, [3] endreplace
     * parse_smileys - if $to==html, disables the bbcode smiley parsing; useful for plugins that do their own smiley parsing (default is true)
	 * custom_smileys - if $to==html, adds custom smileys to parser; array in the format of array[$smiley] => $path.  For example $options['custom_smileys'][':-)'] = 'http://mydomain.com/smileys/smile.png';
     * html_patterns - if $to==html, adds additional bbcode to html rules;
     *     Must be an array of elements with the custom bbcode as the key and the value in the format described at http://nbbc.sourceforge.net/readme.php?page=usage_add
     *     For example $options['html_patterns']['mono'] = array('simple_start' => '<tt>', 'simple_end' => '</tt>', 'class' => 'inline', 'allow_in' => array('listitem', 'block', 'columns', 'inline', 'link'));
     * character_limit - if $to==html OR $to==plaintext, limits the number of visible characters to the user
     * plaintext_line_breaks - if $to=='plaintext', should line breaks when converting to plaintext be replaced with <br /> (br) (default), converted to spaces (space), or left as \n (n)
     * plain_tags - if $to=='plaintext', array of custom bbcode tags (without brackets) that should be stripped
     * @param string $text    the actual text
     * @param string $to      what to convert the text to; bbcode, html, or plaintext
     * @param array  $options array with parser options
     *
     * @return string with converted text
     */
    public static function parseCode($text, $to, $options = '')
    {
        $options = !is_array($options) ? array() : $options;

        if ($to == 'plaintext') {
            if (!isset($options['plaintext_line_breaks'])) {
                $options['plaintext_line_breaks'] = 'br';
            }

            $bbcode =& JFusionFactory::getCodeParser();
            $bbcode->SetPlainMode(true);
            if (isset($options['plain_tags']) && is_array($options['plain_tags'])) {
                foreach ($options['plain_tags'] as $tag) {
                    $bbcode->AddRule($tag, array('class' => 'inline', 'allow_in' => array('block', 'inline', 'link', 'list', 'listitem', 'columns', 'image')));
                }
            }

            if (!empty($options['character_limit'])) {
                $bbcode->SetLimit($options['character_limit']);
            }

            //first thing is to protect our code blocks
            $text = preg_replace("#\[code\](.*?)\[\/code\]#si", "[code]<!-- CODE BLOCK -->$1<!-- END CODE BLOCK -->[/code]", $text, '-1', $code_count);

            $text = $bbcode->Parse($text);
            $text = $bbcode->UnHTMLEncode(strip_tags($text));

            //re-encode our code blocks
            if (!empty($code_count)) {
				$text = preg_replace_callback("#<!-- CODE BLOCK -->(.*?)<!-- END CODE BLOCK -->#si",array( 'JFusionFunction','_callback_htmlspecialchars'), $text);
            }

            //catch newly unencoded tags
            $text = strip_tags($text);

            if ($options['plaintext_line_breaks'] == 'br') {
                $text = $bbcode->nl2br($text);
            } elseif ($options['plaintext_line_breaks'] == 'space') {
                $text = str_replace("\n", "  ", $text);
            }
        } elseif ($to == 'html') {
            //Encode html entities added by the plugin's prepareText function
            $text = htmlentities($text);

            $bbcode =& JFusionFactory::getCodeParser();

            //do not parse & into &amp;
            $bbcode->SetAllowAmpersand(true);

            if (isset($options['html_patterns']) && is_array($options['html_patterns'])) {
                foreach ($options['html_patterns'] as $name => $rule) {
                    $bbcode->AddRule($name, $rule);
                }
            }

            if (!empty($options['parse_smileys'])) {
                $bbcode->SetSmileyURL(JFusionFunction::getJoomlaURL() . 'components/com_jfusion/images/smileys');
            } else {
                $bbcode->SetEnableSmileys(false);
            }

            if (!empty($options['custom_smileys'])) {
                foreach ($options['custom_smileys'] AS $smiley => $path) {
                    $bbcode->AddSmiley($smiley, $path);
                }
            }

            if (!empty($options['character_limit'])) {
                $bbcode->SetLimit($options['character_limit']);
            }

            //disabled this as it caused issues with images and youtube links
            //$bbcode->SetDetectURLs(true);
            //$bbcode->SetURLPattern('<a href="{$url/h}" target="_blank">{$text/h}</a>');

            //first thing is to protect our code blocks
            $text = preg_replace("#\[code\](.*?)\[\/code\]#si", "[code]<!-- CODE BLOCK -->$1<!-- END CODE BLOCK -->[/code]", $text, '-1', $code_count);

            $text = $bbcode->Parse($text);

            //Decode for output
            $text = html_entity_decode($text);

            //re-encode our code blocks
            if (!empty($code_count)) {
                $text = preg_replace_callback("#<!-- CODE BLOCK -->(.*?)<!-- END CODE BLOCK -->#si",array( 'JFusionFunction','_callback_htmlspecialchars'), $text);
            }
        } elseif ($to == 'bbcode') {
            if (!isset($options['bbcode_patterns'])) {
                $options['bbcode_patterns'] = '';
            }
            if (!isset($options['strip_all_html'])) {
                $options['strip_all_html'] = true;
            }

            //remove all linebreaks to prevent massive empty space in bbcode
            $text = str_replace(array("\n","\r","\n\r"), "", $text);

            static $search, $replace;
            if (!is_array($search)) {
                $search = $replace = array();
                $search[] = "#<(blockquote|cite)[^>]*>(.*?)<\/\\1>#si";
                $replace[] = "[quote]$2[/quote]";
                $search[] = "#<ol[^>]*>(.*?)<\/ol>#si";
                $replace[] = "[list=1]$1[/list]";
                $search[] = "#<ul[^>]*>(.*?)<\/ul>#si";
                $replace[] = "[list]$1[/list]";
                $search[] = "#<li[^>]*>(.*?)<\/li>#si";
                $replace[] = "[*]$1";
                $search[] = "#<img [^>]*src=['|\"](?!\w{0,10}://)(.*?)['|\"][^>]*>#si";
                $replace[] = array( 'JFusionFunction','_callback_parseTag_img');
                $search[] = "#<img [^>]*src=['|\"](.*?)['|\"][^>]*>#sim";
                $replace[] = "[img]$1[/img]";
                $search[] = "#<a [^>]*href=['|\"]mailto:(.*?)['|\"][^>]*>(.*?)<\/a>#si";
                $replace[] = "[email=$1]$2[/email]";
                $search[] = "#<a [^>]*href=['|\"](?!\w{0,10}://|\#)(.*?)['|\"][^>]*>(.*?)</a>#si";
                $replace[] = array( 'JFusionFunction','_callback_url');
                $search[] = "#<a [^>]*href=['|\"](.*?)['|\"][^>]*>(.*?)<\/a>#si";
                $replace[] = "[url=$1]$2[/url]";
                $search[] = "#<(b|i|u)>(.*?)<\/\\1>#si";
                $replace[] = "[$1]$2[/$1]";
                $search[] = "#<font [^>]*color=['|\"](.*?)['|\"][^>]*>(.*?)<\/font>#si";
                $replace[] = "[color=$1]$2[/color]";
                $search[] = "#<p>(.*?)<\/p>#si";
                $replace[] = array( 'JFusionFunction','_callback_parseTag_p');
            }
            $searchNS = $replaceNS = array();
            //convert anything between code or pre tags to html entities to prevent conversion
            $searchNS[] = "#<(code|pre)[^>]*>(.*?)<\/\\1>#si";
            $replaceNS[] = array( 'JFusionFunction','_callback_code');
            $morePatterns = & $options['bbcode_patterns'];
            if (is_array($morePatterns) && isset($morePatterns[0]) && isset($morePatterns[1])) {
                $searchNS = array_merge($searchNS, $morePatterns[0]);
                $replaceNS = array_merge($replaceNS, $morePatterns[1]);
            }
            $searchNS = array_merge($searchNS, $search);
            $replaceNS = array_merge($replaceNS, $replace);
            if (is_array($morePatterns) && isset($morePatterns[2]) && isset($morePatterns[3])) {
                $searchNS = array_merge($searchNS, $morePatterns[2]);
                $replaceNS = array_merge($replaceNS, $morePatterns[3]);
            }
            $text = str_ireplace(array("<br />", "<br>", "<br/>"), "\n", $text);

			foreach ($searchNS as $k => $v) {
	            //check if we need to use callback
	            if(is_array($replaceNS[$k])){
	                $text = preg_replace_callback($searchNS[$k],$replaceNS[$k], $text);
	            } else {
	                $text = preg_replace($searchNS[$k], $replaceNS[$k], $text);
	            }
	        }

            //decode html entities that we converted for code and pre tags
            $text = preg_replace_callback("#\[code\](.*?)\[\/code\]#si",array( 'JFusionFunction','_callback_code_decode'), $text);
            //to prevent a billion line breaks in post, let's convert three line breaks into two
            $text = str_ireplace("\n\n\n", "\n\n", $text);
            //and one more time for good measure (there's gotta be a better way)
            $text = str_ireplace("\n\n\n", "\n\n", $text);
            //Change to ensure that the discussion bot posts the article to the forums when there
            //is an issue with preg_replace( '/\p{Z}/u', ' ', $text ) returning an empty string
            //or a series of whitespace.
            //Change to code David Coutts 03/08/2009
            $text_utf8space_to_space = preg_replace('/\p{Z}/u', ' ', $text);
            //Check to see if the returned function is not empty or purely spaces/
            if (strlen(rtrim($text_utf8space_to_space)) > 0) {
                //function returned properly set the output text to be the right trimmed output of the string
                $text = rtrim($text_utf8space_to_space);
            }

            if ($options['strip_all_html']) {
                $text = strip_tags($text);
            }
        }
        return $text;
    }

    /**
     * Used by the JFusionFunction::parseCode function to parse various tags when parsing to bbcode.
     * For example, some Joomla editors like to use an empty paragraph tag for line breaks which gets
     * parsed into a lot of unnecesary line breaks
     * @param $matches mixed values from preg functions
     * @return string to replace search subject with
     */
    public static function parseTag($matches, $tag = 'p')
    {
        if ($tag == 'p') {
            $text = trim($matches);
            //remove the slash added to double quotes and slashes added by the e modifier
            $text = str_replace('\"', '"', $text);
            if(empty($text) || ord($text) == 194) {
                //p tags used simply as a line break
                return "\n";
            } else {
                return $text . "\n\n";
            }
        } elseif ($tag == 'img') {
            $joomla_url = JFusionFunction::getJoomlaURL();
            $juri = new JURI($joomla_url);
            $path = $juri->getPath();
            if ($path != '/'){
                $matches = str_replace($path,'',$matches);
            }
            $url = JRoute::_($joomla_url . $matches);
            return $url;
        }
    }

    /**
     * Reconnects Joomla DB if it gets disconnected
     *
     * @return string nothing
     */
    public static function reconnectJoomlaDb($forceReload=false)
    {
        //check to see if the Joomla database is still connnected
        $db = JFactory::getDBO();
        jimport('joomla.database.database');
        jimport('joomla.database.table');
        $conf = JFactory::getConfig();
        $database = $conf->getValue('config.db');
        $connected = true;
        if (!method_exists($db,'connected')){
            $connected = false;	
        } elseif (!$db->connected()){
            $connected = false;
        }
        
        if (!$connected||$forceReload) {
            $driver = $conf->getValue('config.dbtype');

            //reset current database connection
            if (JFusionFunction::isJoomlaVersion('1.7')) {
                JFactory::$database = null;
            } else {
                $db = null;
            }

            //create new connection
            if ($driver == 'mysql') {
                jimport('joomla.database.database');
                jimport( 'joomla.database.table' );
                $host       = $conf->getValue('config.host');
                $user       = $conf->getValue('config.user');
                $password   = $conf->getValue('config.password');
                $database   = $conf->getValue('config.db');
                $prefix     = $conf->getValue('config.dbprefix','');
                $debug      = $conf->getValue('config.debug');
                $options    = array ( 'driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );

                require_once dirname(__FILE__) . DS . 'mysql.reconnect.php';
                $newDBO = new JFusionReconnectMySQL($options);

                if ( JFusionFunction::isJoomlaVersion('1.7') ) {
                    JFactory::$database = $newDBO;
                }
                $db = $newDBO;
                if ($db->getErrorNum() > 0) {
                    die('Jfusion: Could not reconnect to database <br />' . $db->getErrorNum().' - '.$db->getErrorMsg());
                }
                $db->debug( $debug );
            } else {
            	//connection still current no need to reload
                $db = JFactory::getDBO();
            }
        }
        //try to select the joomla database
        if (!$db->select($database)) {
	        //oops database select failed
        	if (!$forceReload) {
        		//try to create a new database object first
	    	    JFusionFunction::reconnectJoomlaDb(true);
	        } else {
	        	//even new database connection fails to resolve error
	        	//only option now is to die
	        	die('JFusion error: could not select Joomla database when trying to restore Joomla database object');
   	        }
        } else {
            //database reconnect succesful, some final tidy ups
       	
        	//add utf8 support
            $db->setQuery('SET names \'utf8\'');
            $db->query();
            //legacy $database must be restored
            if (JPluginHelper::getPlugin('system', 'legacy')) {
                $GLOBALS['database'] = & $db;
            }
        }
    }



    /**
     * Retrieves the URL to a userprofile of a Joomla supported component
     *
     * @param string  $software    string name of the software
     * @param int     $uid         int userid of the user
     * @param boolean $isPluginUid indicator of plugin in uid
     * @param string  $jname       jname of the plugin
     * @param string  $username    username
     *
     * @return string URL
     */
    public static function getAltProfileURL($software, $uid, $isPluginUid = false, $jname = '', $username = '')
    {
        $db = JFactory::getDBO();
        if ($isPluginUid && !empty($jname)) {
            $userlookup = JFusionFunction::lookupUser($jname, $uid, false, $username);
            if (!empty($userlookup)) {
                $uid = $userlookup->id;
            } else {
                return '';
            }
        }
        if (!empty($uid)) {
            if ($software == "cb") {
                $query = "SELECT id FROM #__menu WHERE type = 'component' AND link LIKE '%com_comprofiler%' LIMIT 1";
                $db->setQuery($query);
                $itemid = $db->loadResult();
                $url = JRoute::_('index.php?option=com_comprofiler&task=userProfile&Itemid=' . $itemid . '&user=' . $uid);
            } elseif ($software == "jomsocial") {
                $query = "SELECT id FROM #__menu WHERE type = 'component' AND link LIKE '%com_community%' LIMIT 1";
                $db->setQuery($query);
                $itemid = $db->loadResult();
                $url = JRoute::_('index.php?option=com_community&view=profile&Itemid=' . $itemid . '&userid=' . $uid);
            } elseif ($software == "joomunity") {
                $query = "SELECT id FROM #__menu WHERE type = 'component' AND link LIKE '%com_joomunity%' LIMIT 1";
                $db->setQuery($query);
                $itemid = $db->loadResult();
                $url = JRoute::_('index.php?option=com_joomunity&Itemid=' . $itemid . '&cmd=Profile.View.' . $uid);
            } else {
                $url = false;
            }
        } else {
            $url = false;
        }
        return $url;
    }

    /**
     * Retrieves the source of the avatar for a Joomla supported component
     *
     * @param string  $software    software name
     * @param int     $uid         uid
     * @param boolean $isPluginUid boolean if true, look up the Joomla id in the look up table
     * @param string  $jname       needed if $isPluginId = true
     * @param string  $username    username
     *
     * @return string nothing
     */
    public static function getAltAvatar($software, $uid, $isPluginUid = false, $jname = '', $username = '')
    {
        $db = JFactory::getDBO();
        if ($isPluginUid && !empty($jname)) {
            $userlookup = JFusionFunction::lookupUser($jname, $uid, false, $username);
            if (!empty($userlookup)) {
                $uid = $userlookup->id;
            } else {
                //no user was found
                $avatar = JFusionFunction::getJoomlaURL() . 'components/com_jfusion/images/noavatar.png';
                return $avatar;
            }
        }
        if ($software == "cb") {
            $query = "SELECT avatar FROM #__comprofiler WHERE user_id = '$uid'";
            $db->setQuery($query);
            $result = $db->loadResult();
            if (!empty($result)) {
                $avatar = JFusionFunction::getJoomlaURL() . 'images/comprofiler/'.$result;
            } else {
                $avatar = JFusionFunction::getJoomlaURL() . 'components/com_comprofiler/plugin/templates/default/images/avatar/nophoto_n.png';
            }
        } elseif ($software == "jomsocial") {
            $query = "SELECT avatar FROM #__community_users WHERE userid = '$uid'";
            $db->setQuery($query);
            $result = $db->loadResult();
            if (!empty($result)) {
                $avatar = JFusionFunction::getJoomlaURL() . $result;
            } else {
                $avatar = JFusionFunction::getJoomlaURL() . 'components/com_community/assets/default_thumb.jpg';
            }
        } elseif ($software == "joomunity") {
            $query = "SELECT user_picture FROM #__joom_users WHERE user_id = '$uid'";
            $db->setQuery($query);
            $result = $db->loadResult();
            $avatar = JFusionFunction::getJoomlaURL() . 'components/com_joomunity/files/avatars/' . $result;
        } elseif ($software == "gravatar") {
            $query = "SELECT email FROM #__users WHERE id = '$uid'";
            $db->setQuery($query);
            $email = $db->loadResult();
            $avatar = "http://www.gravatar.com/avatar.php?gravatar_id=" . md5(strtolower($email)) . "&size=40";
        } else {
            $avatar = JFusionFunction::getJoomlaURL() . 'components/com_jfusion/images/noavatar.png';
        }
        return $avatar;
    }

    /**
     * Gets the source_url from the joomla_int plugin
     *
     * @return string Joomla's source URL
     */
    public static function getJoomlaURL()
    {
        static $joomla_source_url;
        if (empty($joomla_source_url)) {
            $params = & JFusionFactory::getParams('joomla_int');
            $joomla_source_url = $params->get('source_url');
        }
        return $joomla_source_url;
    }

    /**
     * Gets the base url of a specific menu item
     *
     * @param int $itemid int id of the menu item
     * @param boolean $xhtml  return URL with encoded amperstands
     *
     * @return string parsed base URL of the menu item
     */
    public static function getPluginURL($itemid, $xhtml = true)
    {
        static $jfusionPluginURL;
        if (!is_array($jfusionPluginURL)) {
            $jfusionPluginURL = array();
        }
        if (!isset($jfusionPluginURL[$itemid])) {
            $joomla_url = JFusionFunction::getJoomlaURL();
            $baseURL = JRoute::_('index.php?option=com_jfusion&Itemid=' . $itemid, false);
            if (!strpos($baseURL, '?')) {
                $baseURL = preg_replace('#\.[\w]{3,4}\z#is', '', $baseURL);
                if (substr($baseURL, -1) != '/') {
                    $baseURL.= '/';
                }
            }
            $juri = new JURI($joomla_url);
            $path = $juri->getPath();
            if ($path != '/') {
                $baseURL = str_replace($path, '', $baseURL);
            }
            if (substr($joomla_url, -1) == '/') {
                if ($baseURL[0] == '/') {
                    $baseURL = substr($joomla_url, 0, -1) . $baseURL;
                } else {
                    $baseURL = $joomla_url . $baseURL;
                }
            } else {
                if ($baseURL[0] == '/') {
                    $baseURL = $joomla_url . $baseURL;
                } else {
                    $baseURL = $joomla_url . '/' . $baseURL;
                }
            }

            $jfusionPluginURL[$itemid] = $baseURL;
        }

        //let's clean up the URL here before passing it
        if($xhtml) {
            $url = str_replace('&', '&amp;', $jfusionPluginURL[$itemid]);
        } else {
            $url = $jfusionPluginURL[$itemid];
        }
        return $url;
    }

    /**
     * hides sensitive information
     *
     * @param object $userinfo userinfo
     *
     * @return string parsed userinfo object
     */
    public static function anonymizeUserinfo($userinfo)
    {
        if ( is_object($userinfo) ) {
            $userclone = clone $userinfo;
            $userclone->password_clear = '******';
            if (isset($userclone->password)) {
                $userclone->password = substr($userclone->password, 0, 6) . '********';
            }
            if (isset($userclone->password_salt)) {
                $userclone->password_salt = substr($userclone->password_salt, 0, 4) . '*****';
            }
        } else {
            $userclone = $userinfo;
        }
        return $userclone;
    }

    /**
     * checks if the user is an admin
     *
     * @return boolean to indicate admin status
     */
    public static function isAdministrator()
    {
        $mainframe = JFactory::getApplication();
        if ($mainframe->isAdmin()) {
            //we are on admin side, lets confirm that the user has access to user manager
            $juser = JFactory::getUser();
            if ($juser->authorize('com_users', 'manage')) {
                $debug = true;
            } else {
                $debug = false;
            }
        } else {
            $debug = false;
        }
        return $debug;
    }

    /**
     * Converts a string to all ascii characters
     *
     * @param string $input str to convert
     *
     * @return string converted string
     */
    public static function strtoascii($input)
    {
        $output = '';
        foreach (str_split($input) as $char) {
            $output.= '&#' . ord($char) . ';';
        }
        return $output;
    }

    /**
     * Retrieves the current timezone based on user preference
     * Defaults to Joomla's global config for timezone
     * Hopefully the need for this will be deprecated in Joomla 1.6
     *
     * @return timezone in -6 format
     */
    public static function getJoomlaTimezone()
    {
        static $timezone;
        if (!isset($timezone)) {
            $mainframe = JFactory::getApplication();
            $timezone = $mainframe->getCfg('offset');
            $JUser = JFactory::getUser();
            if (!$JUser->guest) {
                $timezone = $JUser->getParam('timezone', $timezone);
            }
        }
        return $timezone;
    }

    /**
     * Returns value from version_compare what version joomla is
     *
     * @param string $v version to check
     * @param string $jname name of joomla_ext plugin
     *
     * @return true/false
     */
    public static function isJoomlaVersion($v='1.6',$jname='joomla_int') {
        static $versions;
		if (!isset($versions[$jname][$v])) {
	        if ($jname=='joomla_int') {
	        	jimport('joomla.version');
	        	//file has now moved in Joomla 2.5
	        	//manual include added as JImport has strange behaviours when called from outside core
	        	if (file_exists(JPATH_ROOT.DS.'libraries'.DS.'cms'.DS.'version'.DS.'version.php')) {
	        		include_once(JPATH_ROOT.DS.'libraries'.DS.'cms'.DS.'version'.DS.'version.php');
	        	} elseif (file_exists(JPATH_ROOT.DS.'includes'.DS.'version.php')) {
	        		include_once(JPATH_ROOT.DS.'includes'.DS.'version.php');
	        	}
				$version = new JVersion;
		    	if (version_compare($version->getShortVersion(), $v) >= 0) {
		        	$versions[$jname][$v] = true;
		    	} else {
		        	$versions[$jname][$v] = false;
		    	}
	        } else {
				// must be joomla_ext
        		$admin = JFusionFactory::getAdmin($jname);
        		$version = $admin->getVersion();
		    	if (version_compare($version, $v) >= 0) {
		        	$versions[$jname][$v] = true;
		    	} else {
		        	$versions[$jname][$v] = false;
		    	}
	        }
		}
        return $versions[$jname][$v];
    }

    /**
     * return the correct usergroups for a given user
     *
     * @param string $jname plugin name
     * @param object $userinfo user with correct usergroups
     *
     * @return true/false
     */
    public static function getCorrectUserGroups($jname,$userinfo) {
        $params = & JFusionFactory::getParams($jname);
        $usergroups = $params->get('usergroup',null);
		$multiusergroup = $params->get('multiusergroup',null);
        if ($usergroups !== null) {
	        $usergroups = (substr($usergroups, 0, 2) == 'a:') ? unserialize($usergroups) : $usergroups;
	        //check to make sure that if using the advanced group mode, $userinfo->group_id exists
	        if (is_array($usergroups) && !isset($userinfo->group_id)) {
	            return array();
	        }

	        $usergroup = (is_array($usergroups)) ? $usergroups[$userinfo->group_id] : $usergroups;

			//Is there other information stored in the usergroup?
	        if (is_array($usergroup)) {
	            //use the first var in the array
	            $keys = array_keys($usergroup);
	            $usergroup = $usergroup[$keys[0]];
	        }
	        return array($usergroup);
        } else if ($multiusergroup !== null) {
        	$master = JFusionFunction::getMaster();

	        $multiusergroupdefault = $params->get('multiusergroupdefault');
	        $multiusergroup = (substr($multiusergroup, 0, 2) == 'a:') ? unserialize($multiusergroup) : $multiusergroup;

			if (!is_array($multiusergroup)) {
				return array($multiusergroup);
	        }

	        if (isset($userinfo->groups)) {
	        	$groups = $userinfo->groups;
	        } else {
	        	$groups[] = $userinfo->group_id;
	        }

        	$mastergroups = isset($multiusergroup[$master->name]) ? $multiusergroup[$master->name] : array();
        	$slavegroups = isset($multiusergroup[$jname]) ? $multiusergroup[$jname] : array();

	        foreach ($mastergroups as $key => $mastergroup) {
	        	if ( count($mastergroup) == count($groups) ) {
					$count = 0;
	        		foreach ($mastergroup as $value) {
	    	    		if (in_array($value, $groups, true)) {
	    					$count++;
						}
	        		}
	        		if (count($groups) == $count ) {
	        			return $slavegroups[$key];
	        		}
	        	}
	        }
			if (isset($slavegroups[$multiusergroupdefault])) {
				return $slavegroups[$multiusergroupdefault];
			}
        }
		return array();
    }

    /**
     * compare set of usergroup with a user returs true if the usergroups are correct
     *
     * @param object $userinfo user with current usergroups
     * @param array $usergroups array with the correct usergroups
     *
     * @return true/false
     */
    public static function compareUserGroups($userinfo,$usergroups) {
    	if (!is_array($usergroups)) {
    		$usergroups = array($usergroups);
    	}
    	if (isset($userinfo->groups)) {
			$count = 0;
			if ( count($usergroups) == count($userinfo->groups) ) {
				foreach ($usergroups as $key => $group) {
    	    		if (in_array($group, $userinfo->groups, true)) {
    					$count++;
					}
	        	}
				if (count($userinfo->groups) == $count) {
					return true;
				}
			}
    	} else {
    		foreach ($usergroups as $key => $group) {
    			if ($group == $userinfo->group_id) {
    				return true;
    			}
        	}
    	}
		return false;
    }

    public static function loadLanguage($extension,$type,$name, $basePath = null){
		$extension = $extension.'_'.$type.'_'.$name;
    	if(JFusionFunction::isJoomlaVersion('1.6')) {
			if ($basePath == null) {
				$basePath = JPATH_ADMINISTRATOR;
			}
			$lang = JFactory::getLanguage();
			return
				$lang->load(strtolower($extension), $basePath, null, false, false)
			||	$lang->load(strtolower($extension), JPATH_PLUGINS .DS.$type.DS.$name, null, false, false)
			||	$lang->load(strtolower($extension), $basePath, $lang->getDefault(), false, false)
			||	$lang->load(strtolower($extension), JPATH_PLUGINS .DS.$type.DS.$name, $lang->getDefault(), false, false);
    	} else {
			if ($basePath == null) {
				$basePath = JPATH_BASE;
			}
			jimport('joomla.plugin.plugin');
			JPlugin::loadLanguage($extension, $basePath);
    	}
    }

    /*
     * Convert a utf-8 joomla string in to a valid encoding matching the table/filed it will be sent to
     *
     * @param string $string string to convert
     * @param string $jname used to get the database object, and point to the static stored data
     * @param string $table table that we will be looking at
     * @param string $field field that we will be looking at
     *
     * @return string converted string
     */
    public static function encodeDBString($string, $jname, $table, $field) {
        static $data;
        $data = array();
        if (!isset($data[$jname][$table])) {
            $db = & JFusionFactory::getDatabase($jname);
            $query = 'SHOW FULL FIELDS FROM '.$table;
            $db->setQuery($query);
            $fields = $db->loadObjectList();

            foreach ($fields as $f) {
                if ($f->Collation) {
                    $data[$jname][$table][$f->Field] = $f->Collation;
                }
            }
        }

        if (isset($data[$jname][$table][$field]) ) {
        	list($charset) = explode('_', $data[$jname][$table][$field]);
            switch ($charset) {
                case 'latin1':
                	$encoding = 'ISO-8859-1';
                    break;
                case 'utf8':
                	//do nothing
                    break;
                default:
                	JError::raiseError(500, 'JFusion Encoding support missing: '.$charset);
                    break;
            }
            if (isset($encoding)) {
            	$converted = false;
	            if (function_exists ('iconv')) {
                    $converted = iconv('utf-8', $encoding, $string);
	            } else if (function_exists('mb_convert_encoding')) {
                    $converted = mb_convert_encoding($string, $encoding, 'utf-8');
                } else {
                    JError::raiseError(500, 'JFusion: missing iconv or mb_convert_encoding');
                }
                if ($converted !== false) {
                	$string = $converted;
                } else {
                    JError::raiseError(500, 'JFusion Encoding failed '.$charset);
                }
            }
        }
        return $string;
    }  

    public static function _callback_htmlspecialchars($matches)
    {
        return htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
    }

    public static function _callback_code($matches)
    {
        return '[code]'.htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8').'[/code]';
    }

    public static function _callback_code_decode($matches)
    {
        return '[code]'.htmlspecialchars_decode($matches[1], ENT_QUOTES).'[/code]';
    }

    public static function _callback_parseTag_img($matches)
    {
        return '[img]'.JFusionFunction::parseTag($matches[1],'img').'[/img]';
    }

    public static function _callback_parseTag_p($matches)
    {
        return JFusionFunction::parseTag($matches[1], 'p');
    }

    public static function _callback_url($matches)
    {
    	return '[url='.JRoute::_(JFusionFunction::getJoomlaURL().$matches[1]).']'.$matches[2].'[/url]';
    }
}