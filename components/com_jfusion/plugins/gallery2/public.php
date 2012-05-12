<?php

/**
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage Gallery2 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * load the JFusion framework
 */
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jfusion.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.abstractpublic.php';
/**
 * JFusion plugin class for Gallery2
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage Gallery2 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionPublic_gallery2 extends JFusionPublic {
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname() 
    {
        return 'gallery2';
    }
    function getRegistrationURL() {
        return '?g2_view=core.UserAdmin&g2_subView=register.UserSelfRegistration';
    }
    function getLostPasswordURL() {
        return '?g2_view=core.UserAdmin&g2_subView=core.UserRecoverPassword';
    }
    /*
    function getLostUsernameURL()
    {
    return '';
    }*/
    function getBuffer($data) {
    	$this->data = $data;    	
        $jPluginParam = & $data->jPluginParam;
        //Handle PHP based Gallery Rewrite
        $segments = JRequest::getVar('jFusion_Route');
        if (!empty($segments)) {
            $path_info = '/' . implode('/', unserialize($segments));
            $path_info = str_replace(':', '-', $path_info);
            $_SERVER['PATH_INFO'] = $path_info;
        }
        require JFUSION_PLUGIN_PATH . DS . $this->getJname() . DS . 'gallery2.php';
        jFusion_g2BridgeCore::loadGallery2Api($this->getJname(),true);
        global $gallery, $user;
        $album = $jPluginParam->get('album', -1);
        if ($album != - 1) {
            $gallery->setConfig('defaultAlbumId', $album);
            $gallery->setConfig('breadcrumbRootId', $album);
        }
        $theme = $jPluginParam->get('show_templateList', '');
        if (!empty($theme)) {
            GalleryEmbed::setThemeForRequest($theme);
        }
        //Check displaying Sidebar
        GalleryCapabilities::set('showSidebarBlocks', ($jPluginParam->get("dispSideBar") == 1));
        // Start the Embed Handler
        ob_start();
        //$ret = $gallery->setActiveUser($userinfo);
        $g2data = GalleryEmbed::handleRequest();
        $output = ob_get_contents();
        ob_end_clean();
        // Handle File Output
        if (trim($output)) {
            if (preg_match('%<h2>\s(?<head>.*)\s</h2>%', $output, $match1) && preg_match('%<p class="giDescription">\s(?<desc>.*)\s</p>%', $output, $match2)) {
                echo "<pre>";
                var_dump($match1);
                var_dump($match2);
                echo "</pre>";
                if (isset($match1["head"]) && isset($match2["desc"])) {
                    JError::raiseError(500, $match1["head"], $match2["desc"]);
                } else {
                    JError::raiseError(500, 'Gallery2 Internal Error');
                }
            } else {
                print $output;
                exit();
            }
        }
        /* Register Sidebare for Module Usage */
        if (isset($g2data["sidebarBlocksHtml"])) {
            jFusion_g2BridgeCore::setVar($this->getJname(),"sidebar", $g2data["sidebarBlocksHtml"]);
        }
        jFusion_g2BridgeCore::setPathway($this->getJname());
        if (isset($g2data['bodyHtml']) && isset($g2data['headHtml'])) {
            $buffer = "<html><head>" . $g2data['headHtml'] . "</head><body>" . $g2data['bodyHtml'] . "</body></html>";
            $data->body = & $g2data['bodyHtml'];
            $data->header = & $g2data['headHtml'];
            $data->buffer = & $buffer;
        }
    }

    function parseBody(&$data) {
        $regex_body = array();
        $replace_body = array();
        //fix for form actions    	
        $data->body = preg_replace_callback('#action="(.*?)"(.*?)>#m',array( &$this,'fixAction'), $data->body);

    }

    function parseHeader(&$data) {
    }

    /**
     * Fix action
     *
     * @param string $url     string with html
     * @param string $extra   string with html
     * @param string $baseURL string with html
     *
     * @return string html
     */
	function fixAction($matches)
    {
		$url = $matches[1];
		$extra = $matches[2];
		$baseURL = $this->data->baseURL;
		    	
        //JError::raiseWarning(500, $url);
        $url = htmlspecialchars_decode($url);
        $Itemid = JRequest::getInt('Itemid');
        $extra = stripslashes($extra);
        if (substr($baseURL, -1) != '/') {
            //non-SEF mode
            $url_details = parse_url($url);
            $url_variables = array();
            if (isset($url_details['query'])) {
                parse_str($url_details['query'], $url_variables);
            }
            //set the correct action and close the form tag
            $replacement = 'action="' . $url . '"' . $extra . '>';
            $replacement.= '<input type="hidden" name="Itemid" value="' . $Itemid . '"/>';
			$replacement.= '<input type="hidden" name="option" value="com_jfusion"/>';
        } else {
            //check to see what SEF mode is selected
            $params = JFusionFactory::getParams($this->getJname());
            $sefmode = $params->get('sefmode');
            if ($sefmode == 1) {
                //extensive SEF parsing was selected
                $url = JFusionFunction::routeURL($url, $Itemid);
                $replacement = 'action="' . $url . '"' . $extra . '>';
                return $replacement;
            } else {
                //simple SEF mode
                $url_details = parse_url($url);
                $url_variables = array();
                if (isset($url_details['query'])) {
                    parse_str($url_details['query'], $url_variables);
                }
                $replacement = 'action="' . $baseURL . '"' . $extra . '>';
            }
        }
        unset($url_variables['option'], $url_variables['Itemid']);
        if (is_array($url_variables)){
        	foreach ($url_variables as $key => $value){
        		$replacement .=  '<input type="hidden" name="'. $key .'" value="'.$value . '"/>';
        	}
        }
        return $replacement;
    }

    function getSearchResults(&$text, &$phrase, &$pluginParam, $itemid) {
        require JFUSION_PLUGIN_PATH . DS . $this->getJname() . DS . 'gallery2.php';
        jFusion_g2BridgeCore::loadGallery2Api($this->getJname(),true, $itemid);
        global $gallery;
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $urlGenerator = $gallery->getUrlGenerator();
        /* start preparing */
        $text = trim($text);
        if ($text == '') {
            return array();
        }
        $return = array();
        //Limitation so prevent overheads -1 = unlimited
        $limit = - 1;
        list(, $result['GalleryCoreSearch']) = GalleryEmbed::search($text, 'GalleryCoreSearch', 0, $limit);
        foreach ($result as $section => $resultArray) {
            if ($resultArray['count'] == 0) {
                continue;
            }
            foreach ($resultArray['results'] as $array) {
                $info = new stdClass();
                $info->href = $urlGenerator->generateUrl(array('view' => 'core.ShowItem', 'itemId' => $array['itemId']));
                list($ret, $item) = GalleryCoreApi::loadEntitiesById($array['itemId']);
                $info->title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
                $info->title = preg_replace('/\r\n/', ' ', $info->title);
                $info->section = $section;
                $info->created = $item->getcreationTimestamp();
                $description = $item->getdescription();
                $info->text = empty($description) ? $item->getSummary() : $description;
                $info->browsernav = 2;
                $item->getparentId();
                if ($item->getparentId() != 0) {
                    list($ret, $parent) = GalleryCoreApi::loadEntitiesById($item->getparentId());
                    $parent = $parent->getTitle() ? $parent->getTitle() : $parent->getPathComponent();
                    $info->section = preg_replace('/\r\n/', ' ', $parent);
                    if (strpos(strtolower($info->section), 'gallery') !== 0) {
                        $info->section = 'Gallery/' . $info->section;
                    }
                }
                
                $config['itemid'] = $itemid;
                $config['debug'] = true;
                $pluginParam->set('g2_itemId',$array['itemId']);
                
                $forum = JFusionFactory::getForum($this->getJname());
                $info->galleryImage = $forum->renderImageBlock($config, 'image_block', $pluginParam);
                
                list(, $views) = GalleryCoreApi::fetchItemViewCount($array['itemId']);
                $return[] = $info;
            }
        }
        return $return;
    }
    /************************************************
    * Functions For JFusion Who's Online Module
    ***********************************************/
    /**
     * Returns a query to find online users
     * Make sure columns are named as userid, username, username_clean (if applicable), name (of user), and email
     */
    function getOnlineUserQuery($limit) {
        $limiter = (!empty($limit)) ? "LIMIT 0,$limit" : '';
        //get a unix time from 5 mintues ago
        date_default_timezone_set('UTC');
        $now = time();
        $active = strtotime("-5 minutes", $now);
        $query = "SELECT DISTINCT u.g_id AS userid, u.g_userName as username, u.g_fullName AS name  " . "FROM #__User AS u INNER JOIN #__SessionMap AS s ON s.g_userId = u.g_id " . "WHERE s.g_modificationTimestamp > $active $limiter";
        return $query;
    }
    /**
     * Returns number of members
     * @return int
     */
    function getNumberOnlineMembers() {
        //get a unix time from 5 mintues ago
        date_default_timezone_set('UTC');
        $now = time();
        $active = strtotime("-5 minutes", $now);
        $db = & JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT COUNT(*) FROM #__SessionMap s " . "WHERE g_modificationTimestamp > $active AND s.g_userId != 5";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }
    /**
     * Returns number of guests
     * @return int
     */
    function getNumberOnlineGuests() {
        //get a unix time from 5 mintues ago
        date_default_timezone_set('UTC');
        $now = time();
        $active = strtotime("-5 minutes", $now);
        $db = & JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT COUNT(*) FROM #__SessionMap s " . "WHERE g_modificationTimestamp > $active AND s.g_userId = 5";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }
}
