<?php

/**
 * This is view file for versioncheck
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    ViewsAdmin
 * @subpackage Versioncheck
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
//display the paypal donation button
JFusionFunctionAdmin::displayDonate();
?>

<table>
	<tr>
		<td width="100px">
			<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
		</td>
		<td width="100px">
			<img src="components/com_jfusion/images/versioncheck.png" height="75px" width="75px">
		</td>
		<td>
		<h2>
			<?php echo JText::_('VERSION_CHECKER'); ?>
		</h2>
		</td>
	</tr>
</table>
<br/>

<style type="text/css">
	tr.good0 { background-color: #ecfbf0; }
	tr.good1 { background-color: #d9f9e2; }
	tr.bad0 { background-color: #f9ded9; }
	tr.bad1 { background-color: #f9e5e2; }
	table.adminform td {width: 33%;}
</style>

<table class="adminform" style="border-spacing:1px;">
	<thead>
		<tr>
			<th class="title" align="left">
				<?php echo JText::_('SERVER_SOFTWARE'); ?>
			</th>
			<th class="title" align="center">
				<?php echo JText::_('YOUR_VERSION'); ?>
			</th>
			<th class="title" align="center">
				<?php echo JText::_('MINIMUM_VERSION'); ?>
			</th>
		</tr>
	</thead>
	<tbody>
        <?php
        foreach ($this->system as $software) {
        ?>
            <tr class = "<?php echo $software->class ;?>">
                <td>
                    <?php echo $software->name; ?>
                </td>
                <td>
                    <?php echo $software->oldversion; ?>
                </td>
                <td>
                    <?php echo $software->version ;?>
                </td>
            </tr>
        <?php 
        }
        ?>
	</tbody>
</table>
<?php
if ($this->server_compatible) {
//output the good news
?>
    <table style="background-color:#d9f9e2;width:100%;">
	    <tr>
		    <td>
		    	<img src="components/com_jfusion/images/check_good.png" height="30px" width="30px">
		    </td>
		    <td>
				<h2>
					<?php echo JText::_('SERVER_UP2DATE'); ?>
			    </h2>
		    </td>
	    </tr>
    </table>

<?php
} else {
//output the bad news and automatic upgrade option
?>
    <table style="background-color:#f9ded9;">
	    <tr>
		    <td width="50px">
		    	<img src="components/com_jfusion/images/check_bad.png" height="30px" width="30px">
		    </td>
		    <td>
		    	<h2>
		    		<?php echo JText::_('SERVER_OUTDATED'); ?>
		    	</h2>
		    </td>
		</tr>
	</table>
<?php
}
?>

<br/><br/>
<table class="adminform" style="border-spacing:1px;">
	<thead>
		<tr>
			<th class="title" align="left">
				<?php echo JText::_('JFUSION_SOFTWARE'); ?>
			</th>
			<th class="title" align="center">
				<?php echo JText::_('YOUR_VERSION'); ?>
			</th>
			<th class="title" align="center">
				<?php echo JText::_('CURRENT_VERSION'); ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ($this->components as $component) {
			?>
			<tr class = "<?php echo $component->class ;?>">
				<td>
					<?php echo JText::_('JFUSION') . ' ' . $component->name . ' ' . JText::_('VERSION') ;?>
				</td>
				<td>
					<?php echo $component->oldversion;
					if ($component->rev) echo ' Rev(' . $component->rev.')';
					?>
				</td>
				<td>
					<?php echo $component->version ;?>
				</td>
			</tr>
		<?php 
		}
		?>
	</tbody>
</table>
<br/>

<table class="adminform" style="border-spacing:1px;">
	<thead>
		<tr>
			<th class="title" align="left">
				<?php echo JText::_('JFUSION_PLUGINS'); ?>
			</th>
			<th class="title" align="center">
				<?php echo JText::_('YOUR_VERSION'); ?>
			</th>
			<th class="title" align="center">
				<?php echo JText::_('CURRENT_VERSION'); ?>
			</th>
		</tr>
	</thead>
	<tbody>
        <?php
        foreach ($this->jfusion_plugins as $jfusion_plugin) {
            ?>
            <tr class = "<?php echo $jfusion_plugin->class ;?>">
                <td>
                    <?php echo JText::_('JFUSION') . ' ' . $jfusion_plugin->name . ' ' . JText::_('VERSION') ;?>
                </td>
                <td>
                    <?php echo $jfusion_plugin->oldversion;
                    if ($jfusion_plugin->rev) echo ' Rev(' . $jfusion_plugin->rev.')';
                    ?>
                </td>
                <td>
                    <?php echo $jfusion_plugin->version ;?>
                </td>
            </tr>
        <?php 
        }
        ?>
	</tbody>
</table>
<?php
if ($this->up2date) {
    //output the good news

    ?>
    <table style="background-color:#d9f9e2;width:100%;">
	    <tr>
		    <td>
			    <img src="components/com_jfusion/images/check_good.png" height="30px" width="30px">
		    <td>
			    <h2>
			    	<?php echo JText::_('JFUSION_UP2DATE'); ?>
			    </h2>
		    </td>
	    </tr>
    </table>
    <?php
} else {
    //output the bad news and automatic upgrade option
    ?>
    <table style="background-color:#f9ded9;">
	    <tr>
		    <td width="50px">
		    	<img src="components/com_jfusion/images/check_bad.png" height="30px" width="30px">
			</td>
			<td>
				<h2>
					<?php echo JText::_('JFUSION_OUTDATED'); ?>
				</h2>
			</td>
			<td>
			    <script type="text/javascript">
			    <!--
	            window.addEvent('domready',function() {
	            	$('update_jfusion').addEvent('click', function(e) {
	                    new Event(e).stop();

	                    confirmSubmit('release');
	                });
	            });
			    // -->
			    </script>
		
                <input id="update_jfusion" type="button" value="<?php echo JText::_('UPGRADE_JFUSION'); ?>"/>
		    </td>
	    </tr>
    </table>
<br/><br/>

<?php
}
?>
<br/><br/><br/>
<table style="background-color:#ffffce;width:100%;">
	<tr>
		<td width="50px">
			<img src="components/com_jfusion/images/advanced.png" height="75px" width="75px">
		</td>
		<td>
			<h3>
			<?php echo JText::_('ADVANCED') . ' ' . JText::_('VERSION') . ' ' . JText::_('MANAGEMENT'); ?>
			</h3>
			<script type="text/javascript">
			<!--
			function confirmSubmit(action)
			{
				var install_url,confirm_text;
				if (action == 'build') {
				    confirm_text = '<?php echo JText::_('UPGRADE_CONFIRM_BUILD'); ?>';
				    install_url = 'http://jfusion.googlecode.com/svn/branches/1.8.x/jfusion_package.zip';
				} else if (action == 'svn') {
				    confirm_text = '<?php echo JText::_('UPGRADE_CONFIRM_SVN'); ?> ' + document.adminForm2.svn_build.value;
				    install_url = 'http://jfusion.googlecode.com/svn-history/r' + document.adminForm2.svn_build.value + '/branches/1.8.x/jfusion_package.zip';
				} else {
				    confirm_text = '<?php echo JText::_('UPGRADE_CONFIRM_RELEASE') . ' ' . $this->JFusion->version; ?>';
				    install_url = 'http://jfusion.googlecode.com/svn/branches/jfusion_package.zip';
				}
				
				var agree = confirm(confirm_text);
				if (agree) {
				    document.adminForm2.install_url.value = install_url;
				    $('install').submit();
				    return true ;
				}
			}

			
			window.addEvent('domready',function() {
			    $('release').addEvent('click', function(e) {
			    	confirmSubmit('release');
                });
			});
            window.addEvent('domready',function() {
            	$('update_jfusion').addEvent('click', function(e) {
                    confirmSubmit('release');
                });
            });
            
			window.addEvent('domready',function() {
			    $('build').addEvent('click', function(e) {
			    	confirmSubmit('build');
                });
			});
			window.addEvent('domready',function() {
			    $('svn').addEvent('click', function(e) {
			    	confirmSubmit('svn');
                });
			});
			// -->
			</script>
	
			<form enctype="multipart/form-data" action="index.php" method="post" id="install" name="adminForm2">
				<input type="hidden" name="install_url" value="" />
			    <input type="hidden" name="type" value="" />
			    <input type="hidden" name="installtype" value="url" />
			    <?php if(JFusionFunction::isJoomlaVersion('1.6')){ ?>
                <input type="hidden" name="task" value="install.install" />
			    <?php } else { ?>
			    <input type="hidden" name="task" value="doInstall" /> 
			    <?php } ?>
			    <input type="hidden" name="option" value="com_installer" />
			    <?php echo JHTML::_('form.token'); ?>
				<b>
					<?php echo JText::_('ADVANCED_WARNING'); ?>
				</b>
				<br/>
				
				<input id="release" type="button" value="<?php echo JText::_('INSTALL') . ' ' . JText::_('LATEST') . ' ' . JText::_('RELEASE'); ?>"/>
				<br/>
				<input id="build" type="button" value="<?php echo JText::_('INSTALL') . ' ' . JText::_('LATEST') . ' SVN Build'; ?>"/>
				<br/>
				SVN build:
				<input type="text" name="svn_build" size="4"/>
				<input id="svn" type="button" value="<?php echo JText::_('INSTALL') . ' ' . JText::_('SPECIFIC') . ' SVN Build'; ?>"/>
				<br/>
			</form>
		</td>
	</tr>
</table>
<br/><br/>
