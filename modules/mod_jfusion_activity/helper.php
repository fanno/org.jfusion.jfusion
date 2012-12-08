<?php
/**
 * This is the activity module helper file
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    Modules
 * @subpackage Activity
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * This is the activity module helper file
 *
 * @category   JFusion
 * @package    Modules
 * @subpackage Activity
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class modjfusionActivityHelper
{

    /**
     * render a plugin
     *
	 * @param array  $results array of activity results
     * @param string $jname  name of element
     * @param string $config value of element
     * @param string $params node of element
     *
     * @return string html
     */

	function appendAutoOutput(&$results, $jname, &$config, &$params) {
		$forum =& JFusionFactory::getForum($jname);
		$public =& JFusionFactory::getPublic($jname);
		if(is_array($results)) {
			foreach($results as $r) {
				//get the Joomla userid
				$userlookup = JFusionFunction::lookupUser($jname, $r->userid, false, $r->username);

				//get the avatar of the logged in user
				if ($config['avatar']) {
					// retrieve avatar
					$avatarSrc =& $config['avatar_software'];
					if(!empty($avatarSrc) && $avatarSrc!='jfusion' && !empty($userlookup)) {
						$avatar = JFusionFunction::getAltAvatar($avatarSrc, $userlookup->id);
					} else {
						$avatar = $forum->getAvatar($r->userid);
					}

					if(empty($avatar)) {
						$avatar = JFusionFunction::getJoomlaURL().'components/com_jfusion/images/noavatar.png';
					}

					$maxheight =& $config['avatar_height'];
					$maxwidth =& $config['avatar_width'];
					$size = ($config['avatar_keep_proportional']) ? @getimagesize($avatar) : false;

					//size the avatar to fit inside the dimensions if larger
					if($size!==false && ($size[0] > $maxwidth || $size[1] > $maxheight)) {
						$wscale = $maxwidth/$size[0];
						$hscale = $maxheight/$size[1];
						$scale = min($hscale, $wscale);
						$w = floor($scale*$size[0]);
						$h = floor($scale*$size[1]);
					} elseif($size!==false) {
						//the avatar is within the limits
						$w = $size[0];
						$h = $size[1];
					} else {
						//getimagesize failed
						$w = $maxwidth;
						$h = $maxheight;
					}

					$r->output->avatar_source = $avatar;
					$r->output->avatar_width = $w;
					$r->output->avatar_height = $h;

				} else {
					$r->output->avatar = '';
				}

				//process user info
				$r->output->display_name = ($config['display_name'] && !empty($r->name)) ? $r->name : $r->username;
				if (empty($r->guest) && $config['userlink']) {
					if ($config['userlink_software']=='custom' && !empty($config['userlink_custom'])  && !empty($userlookup)) {
						$user_url = $config['userlink_custom'].$userlookup->id;
					} else {
						$user_url = JFusionFunction::routeURL($forum->getProfileURL($r->userid, $r->username), $config['itemid'], $jname);
					}

					if(empty($user_url)) {
						$user_url = '';
					}

					$r->output->profile_url = $user_url;
				} else {
					$r->output->profile_url = '';
				}

				//process date info
				if($config['showdate']) {
					if ($config['showdate']==2) {
						list( $count,$name ) = modjfusionActivityHelper::timeSince($r->dateline);
					
						$r->output->date = ($count == 1) ? '1 '.JText::_($name.'_AGO') : $count.' '.JText::_($name.'S_AGO');
					} else {
						jimport('joomla.utilities.date');
						$JDate =  new JDate($r->dateline);
						$JDate->setOffset($config['tz_offset']);
						if (empty($config['date_format'])) {
							$r->output->date = $JDate->toISO8601(true);
						} else {
							$r->output->date = $JDate->toFormat($config['date_format'],true);
						}
					}
				} else {
					$r->output->date = '';
				}

				//process subject or body info
				$subject = (($config['replace_subject'] == 0 && empty($r->subject)) || $config['replace_subject'] == 1) ? $r->body : $r->subject;

				//make sure that a message is always shown
				if (empty($subject)) {
					$subject = JText::_('NO_SUBJECT');
				} elseif (!empty($config['character_limit_subject']) && JString::strlen($subject) > $config['character_limit_subject']) {
					//we need to shorten the subject
					$subject = JString::substr($subject,0,$config['character_limit_subject']) . '...';
				}

				$r->output->subject = $subject;

				//combine all info into an urlstring
				if ($config['linktype'] == LINKPOST) {
					$r->output->subject_url = JFusionFunction::routeURL($forum->getPostURL($r->threadid, $r->postid), $config['itemid'], $jname);
				} else {
					$r->output->subject_url = JFusionFunction::routeURL($forum->getThreadURL($r->threadid), $config['itemid'], $jname);
				}

				if($config['mode'] == LAT) {
					if($config['show_reply_num']) {
						$existingthread = $forum->getThread($r->threadid);
						$count = (!empty($existingthread)) ? $forum->getReplyCount($existingthread) : 0;
						$reply = ($count==1) ? "REPLY" : "REPLIES";
						$r->output->reply_count = $count . " " . JText::_($reply);
					}

					$r->body = "";
				} else {
					//gotta make it presentable
					if($config['display_body']==1){
						$status = $public->prepareText($r->body, 'activity', $params, $r);
						if(!empty($config['character_limit']) && empty($status['limit_applied']) && JString::strlen($r->body) > $config['character_limit']) {
							$r->body = JString::substr($r->body,0,$config['character_limit']) . '...';
						}
					} else {
						$r->body = "";
					}
				}

				if($config['shownew']) {
                    $r->output->newpost = $forum->checkReadStatus($r);
				} else {
				    $r->output->newpost = '';
				}
				$r->output->body = $r->body;
			}
		} else {
			//prevents an error in the output
			$results = array();
		}
	}
	
	function timeSince($original) {
		// array of time period chunks
		$chunks = array(
			array(60 * 60 * 24 * 365 , 'YEAR'),
			array(60 * 60 * 24 * 30 , 'MONTH'),
			array(60 * 60 * 24 * 7, 'WEEK'),
			array(60 * 60 * 24 , 'DAY'),
			array(60 * 60 , 'HOUR'),
			array(60 , 'MINUTE'),
			array(1 , 'SECOND')
		);
		$today = time(); /* Current unix time */

		$since = $today - $original;
	
		// $j saves performing the count function each time around the loop
		for ($i = 0, $j = count($chunks); $i < $j; $i++) {
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			//finding the biggest chunk (if the chunk fits, break)
	        if (($count = floor($since / $seconds)) != 0) {
	            break;
	        }
		}
		return array($count,$name);
	}	
}
?>
