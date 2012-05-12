<?php

/**
 * This is view file for syncstatus
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    ViewsAdmin
 * @subpackage Syncstatus
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Renders the main admin screen that shows the configuration overview of all integrations
 *
 * @category   JFusion
 * @package    ViewsAdmin
 * @subpackage Syncstatus
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class jfusionViewsyncstatus extends JView
{
     /**
     * displays the view
     *
     * @param string $tpl template name
     *
     * @return string html output of view
     */
    function display($tpl = null)
    {
        $mainframe = JFactory::getApplication();
        //add css
        $document = JFactory::getDocument();
        $document->addStyleSheet('components/com_jfusion/css/jfusion.css');
        $template = $mainframe->getTemplate();
        $document->addStyleSheet("templates/$template/css/general.css");
        JHTML::_('behavior.modal');
        JHTML::_('behavior.tooltip');

        //Load usersync library
        include_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'models' . DS . 'model.usersync.php';
        if (!isset($this->syncid)) {
            $syncid = JRequest::getVar('syncid');
            $this->assignRef('syncid', $syncid);
        }

        if (!isset($this->syncdata)) {
            //get the syncdata
            $syncdata = JFusionUsersync::getSyncdata($syncid);
            $this->assignRef('syncdata', $syncdata);

        }
        //append log
        $mainframe = JFactory::getApplication();
        $client             = JRequest::getWord( 'filter_client', 'site' );
        $option = JRequest::getCmd('option');
        $filter_order       = $mainframe->getUserStateFromRequest( "$option.$client.filter_order",      'filter_order',     'id',       'cmd' );
        $filter_order_Dir   = $mainframe->getUserStateFromRequest( "$option.$client.filter_order_Dir",  'filter_order_Dir', '',         'word' );
        $limit              = $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
        $limitstart         = $mainframe->getUserStateFromRequest( $option.'.limitstart', 'limitstart', 0, 'int' );
        $syncdata['log'] = JFusionUsersync::getLogData($this->syncid, 'all', $limitstart, $limit, $filter_order, $filter_order_Dir);
        $filter = array('order' => $filter_order, 'dir' => $filter_order_Dir, 'limit' => $limit, 'limitstart' => $limitstart, 'client' => $client);

        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__jfusion_sync_details WHERE syncid = {$db->Quote($this->syncid)}";
        $db->setQuery($query);
        $total = $db->loadResult();
        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total, $limitstart, $limit);

        $this->assignRef('pageNav', $pageNav);
        $this->assignRef('filter', $filter);

        if (!empty($this->sync_completed)) {
            //ajax calling this page so die so that header info is not put into the body
            die(parent::display($tpl));
        }

        parent::display($tpl);
    }
}
