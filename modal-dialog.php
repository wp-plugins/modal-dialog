<?php
/*Plugin Name: Modal Dialog
Plugin URI: http://yannickcorner.nayanna.biz/modal-dialog/
Description: A plugin used to create a page with a list of TV shows
Version: 1.0.1
Author: Yannick Lefebvre
Author URI: http://yannickcorner.nayanna.biz   
Copyright 2010  Yannick Lefebvre  (email : ylefebvre@gmail.com)    

This program is free software; you can redistribute it and/or modify   
it under the terms of the GNU General Public License as published by    
the Free Software Foundation; either version 2 of the License, or    
(at your option) any later version.    

This program is distributed in the hope that it will be useful,    
but WITHOUT ANY WARRANTY; without even the implied warranty of    
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    
GNU General Public License for more details.    

You should have received a copy of the GNU General Public License    
along with this program; if not, write to the Free Software    
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA*/

if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'modal-dialog.php')) {
	define('MD_FILE', trailingslashit(ABSPATH.PLUGINDIR).'modal-dialog.php');
}
else if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'modal-dialog/modal-dialog.php')) {
	define('MD_FILE', trailingslashit(ABSPATH.PLUGINDIR).'modal-dialog/modal-dialog.php');
}

function md_install() {
	$options  = get_option('MD_PP');

	if ($options == false) {
		$options['contentlocation'] = "URL";
		$options['dialogtext'] = "Example Dialog Text";
		$options['active'] = true;
		$options['cookieduration'] = 365;
		$options['contenturl'] = "http://www.google.com";
		$options['pages'] = "";
		$options['overlaycolor'] = "#00CC00";
		$options['textcolor'] = "#000000";
		$options['backgroundcolor'] = "#CCCCCC";
		$options['delay'] = 2000;
		$options['dialogwidth'] = 900;
		$options['dialogheight'] = 700;
		$options['cookiename'] = 'modal-dialog';
		$options['numberoftimes'] = 1;
		$options['exitmethod'] = 'onlyexitbutton';
		
		update_option('MD_PP',$options);
	}
}
register_activation_hook(MD_FILE, 'md_install');



if ( ! class_exists( 'MD_Admin' ) ) {
	class MD_Admin {		
		function add_config_page() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				add_options_page('Modal Dialog for Wordpress', 'Modal Dialog', 9, basename(__FILE__), array('MD_Admin','config_page'));
				add_filter( 'plugin_action_links', array( 'MD_Admin', 'filter_plugin_actions'), 10, 2 );
				add_filter( 'ozh_adminmenu_icon', array( 'MD_Admin', 'add_ozh_adminmenu_icon' ) );
							}
		} // end add_MD_config_page()

		function filter_plugin_actions( $links, $file ){
			//Static so we don't call plugin_basename on every plugin row.
			static $this_plugin;
			if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
			if ( $file == $this_plugin ){
				$settings_link = '<a href="options-general.php?page=modal-dialog.php">' . __('Settings') . '</a>';
				
				array_unshift( $links, $settings_link ); // before other links
			}
			return $links;
		}

		function config_page() {
			global $dlextensions;
			global $wpdb;
			
			$adminpage == "";

			if ( isset($_GET['reset']) && $_GET['reset'] == "true") {
				$options['contentlocation'] = "URL";
				$options['dialogtext'] = "Example Dialog Text";
				$options['active'] = true;
				$options['cookieduration'] = 365;
				$options['contenturl'] = "http://www.google.com";
				$options['pages'] = "";
				$options['overlaycolor'] = "#00CC00";
				$options['textcolor'] = "#000000";
				$options['backgroundcolor'] = "#CCCCCCC";
				$options['delay'] = 2000;
				$options['dialogwidth'] = 900;
				$options['dialogheight'] = 700;
				$options['cookiename'] = 'modal-dialog';
				$options['numberoftimes'] = 1;
				$options['exitmethod'] = 'onlyexitbutton';
		
				update_option('MD_PP',$options);
			}
			if ( isset($_POST['submit']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the Modal Dialog for WordPress options.'));
				check_admin_referer('mdpp-config');
				
				foreach (array('dialogtext', 'contentlocation', 'cookieduration', 'contenturl', 'pages', 'overlaycolor', 'textcolor', 'backgroundcolor',
						'delay', 'dialogwidth', 'dialogheight', 'cookiename', 'numberoftimes', 'exitmethod') as $option_name) {
						if (isset($_POST[$option_name])) {
							$options[$option_name] = $_POST[$option_name];
						}
					}
					
				foreach (array('active') as $option_name) {
					if (isset($_POST[$option_name]) && $_POST[$option_name] == "True") {
						$options[$option_name] = true;
					} elseif (isset($_POST[$option_name]) && $_POST[$option_name] == "False") {
						$options[$option_name] = false;
					}
				}
					
				update_option('MD_PP', $options);
				
				echo '<div id="message" class="updated fade"><p><strong>Modal Dialog Settings Updated</strong></div>';
			}
			
			$mdpluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

			$options  = get_option('MD_PP');
			?>
			<div class="wrap">
				<h2>Modal Dialog Configuration</h2>		
				<?php if (($adminpage == "") || ($adminpage == "general")): ?>
				<form name="dmadminform" action="" method="post" id="dm-config">
				<?php
					if ( function_exists('wp_nonce_field') )
						wp_nonce_field('mdpp-config');
					?>
					<table>
					<tr>
						<td style='width: 300'>Activate</td>
						<td>
							<select name="active" id="active" style="width:200px;">
								<option value="True"<?php if ($options['active'] == true) { echo ' selected="selected"';} ?>>Yes</option>
								<option value="False"<?php if ($options['active'] == false) { echo ' selected="selected"';} ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>Content Source</td>
						<td>
							<select name="contentlocation" id="contentlocation" style="width:200px;">
								<option value="URL"<?php if ($options['contentlocation'] == 'URL') { echo ' selected="selected"';} ?>>Web Site Address</option>
								<option value="Inline"<?php if ($options['contentlocation'] == 'Inline') { echo ' selected="selected"';} ?>>Specify Below in Dialog Contents</option>
							</select>
						</td>
						<td style='padding-left: 20px'>Appearance Delay</td>
						<td><input type="text" id="delay" name="delay" size="5" value="<?php echo $options['delay']; ?>"/></td>
					</tr>
					<tr>
						<td>Web Site Address</td>
						<td colspan=5><input type="text" id="contenturl" name="contenturl" size="120" value="<?php echo $options['contenturl']; ?>"/></td>
					</tr>
					<tr>
						<td style='vertical-align: top; width: 150px'>Dialog Contents</td>
						<td colspan=5><TEXTAREA id="dialogtext" NAME="dialogtext" COLS=100 ROWS=10><?php echo $options['dialogtext']; ?></TEXTAREA>
						</td>
					</tr>
					<tr>
						<td>Number of days until cookie expiration</td>
						<td><input type="text" id="cookieduration" name="cookieduration" size="4" value="<?php echo $options['cookieduration']; ?>"/></td>
						<td>Number of times to display modal dialog</td>
						<td><input type="text" id="numberoftimes" name="numberoftimes" size="4" value="<?php echo $options['numberoftimes']; ?>"/></td>
					</tr>
					<tr>
						<td>Cookie Name</td>
						<td><input type="text" id="cookiename" name="cookiename" size="30" value="<?php echo $options['cookiename']; ?>"/></td>
						<td>Dialog Exit Method</td>
						<td>
							<select name="exitmethod" id="exitmethod" style="width:200px;">
								<option value="onlyexitbutton"<?php if ($options['exitmethod'] == 'onlyexitbutton') { echo ' selected="selected"';} ?>>Only Close Button</option>
								<option value="anywhere"<?php if ($options['exitmethod'] == 'anywhere') { echo ' selected="selected"';} ?>>Anywhere</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>Dialog Width</td>
						<td><input type="text" id="dialogwidth" name="dialogwidth" size="4" value="<?php echo $options['dialogwidth']; ?>"/></td>
						<td>Dialog Height</td>
						<td><input type="text" id="dialogheight" name="dialogheight" size="4" value="<?php echo $options['dialogheight']; ?>"/></td>
					</tr>
					<tr>
						<td>Pages to display Modal Dialog (empty for all, comma-separated IDs)</td>
						<td colspan=5><input type="text" id="pages" name="pages" size="120" value="<?php echo $options['pages']; ?>"/></td>
					</tr>
					<tr>
						<td>Overlay Color</td>
						<td><input type="text" id="overlaycolor" name="overlaycolor" size="8" value="<?php echo $options['overlaycolor']; ?>"/></td>
						<td>Text Color (not used with web site address)</td>
						<td><input type="text" id="textcolor" name="textcolor" size="8" value="<?php echo $options['textcolor']; ?>"/></td>
						<td>Background Color</td>
						<td><input type="text" id="backgroundcolor" name="backgroundcolor" size="8" value="<?php echo $options['backgroundcolor']; ?>"/></td>
					</tr>
					</table>
					<p style="border:0;" class="submit"><input type="submit" name="submit" value="Update Settings &raquo;" /></p>
				</form>
				<?php endif; ?>				
			</div>
			<?php
		} // end config_page()

		function restore_defaults() {
			update_option('MD_PP',$options);
		}
	} // end class MD_Admin
} //endif

function md_footer_data() {

	$options  = get_option('MD_PP');
	
	if ($options['active'])
	{
		if ($options['pages'] != "")
		{
			$pagelist = explode(',', $options['pages']);
			if (in_array(get_the_ID(), $pagelist))
				$display = true;
			else
				$display = false;
		}
		else
			$display = true;	
			
		if ($display == true)
		{
			global $wpdb;
			
			$options = get_option('MD_PP');
			
			$output = "<!-- Modal Dialog Output -->\n";
			
			if ($options['contentlocation'] == 'Inline')
			{
				$output .= "<a id=\"inline\" href=\"#data\"></a>\n";
				$output .= "<div style=\"display:none\"><div id=\"data\">";
				$output .= $options['dialogtext'];
				
				$output .= "</div></div>\n";
			}
			elseif ($options['contentlocation'] == "URL")
			{
				$output .= "<a href='" . $options['contenturl']. "' class='iframe'></a>\n";
			}
			
			$output .= "<div id='md-content'>\n";
			
				$output .= "<script type=\"text/javascript\">\n";
					
				$output .= "jQuery(document).ready(function() {\n";
				
				if ($options['contentlocation'] == 'Inline')
					$output .= "jQuery(\"a#inline\").fancybox({\n";
				elseif ($options['contentlocation'] == 'URL')
					$output .= "jQuery(\"a.iframe\").fancybox({\n";
					
				if ($options['exitmethod'] == 'onlyexitbutton')
				{
					$output .= "'hideOnOverlayClick': false,\n";
					$output .= "'hideOnContentClick': false,\n";	
				}
				elseif ($options['exitmethod'] == 'anywhere')
				{
					$output .= "'hideOnOverlayClick': true,\n";
					$output .= "'hideOnContentClick': true,\n";	
				}
				$output .= "'overlayColor': '" . $options['overlaycolor'] . "',\n";
				$output .= "'frameWidth': " . $options['dialogwidth'] . ",\n";
				$output .= "'frameHeight': " . $options['dialogheight'] . "\n";
				$output .= "});\n";
				$output .= "var cookievalue = jQuery.cookie('" . $options['cookiename'] . "');\n";
				$output .= "if (cookievalue == null) cookievalue = 0;\n";
				$output .= "if (cookievalue < " . $options['numberoftimes'] . ")\n";
				$output .= "{\n";
				$output .= "\tcookievalue++;\n";
				$output .= "\tjQuery.cookie('" . $options['cookiename'] . "', cookievalue";
				
				if ($options['cookieduration'] > 0)
					$output .= ", { expires: " . $options['cookieduration'] .  "}";
				
				$output .= ");\n";
				$output .= "\tsetTimeout(\n";
				$output .= "function(){\n";
				
				if ($options['contentlocation'] == 'Inline')
					$output .= "jQuery(\"a#inline\").trigger('click')\n";
				elseif ($options['contentlocation'] == 'URL')
					$output .= "jQuery(\"a.iframe\").trigger('click')\n";
					
				$output .= "}, " . $options['delay'] . ");\n";
				$output .= "}\n";
			
			$output .= "});\n";
			$output .= "</script>\n";
			
			$output .= "</div>\n";
			
			$output .= "<!-- End of Modal Dialog Output -->\n";

			echo $output;
		}
	}
}

function modal_dialog_header() {

	$options = get_option('MD_PP');
	
	if ($options['active'])
	{
		if ($options['pages'] != "")
		{
			$pagelist = explode(',', $options['pages']);
			if (in_array(get_the_ID(), $pagelist))
				$display = true;
			else
				$display = false;
		}
		else
			$display = true;		
		
		if ($display == true)
		{
			echo "<link rel='stylesheet' type='text/css' media='screen' href='". WP_PLUGIN_URL . "/modal-dialog/fancybox/jquery.fancybox-1.3.1.css'/>\n";
			echo "<STYLE>\n";
			echo "\t#fancy_div {\n";
			echo "\t\tbackground-color: " . $options['backgroundcolor'] . " !important;\n";
			echo "\t\tcolor: " . $options['textcolor'] . " !important\n";
			echo "\t}";
			echo "</STYLE>";	
		}
	}
}

$version = "1.0";

// adds the menu item to the admin interface
add_action('admin_menu', array('MD_Admin','add_config_page'));

add_action('wp_footer', 'md_footer_data');
add_action('wp_head', 'modal_dialog_header');

$options  = get_option('MD_PP');

if ($options['active'] == true)
{
	if ($options['pages'] != "")
		{
			$pagelist = explode(',', $options['pages']);
			if (in_array(get_the_ID(), $pagelist))
				$display = true;
			else
				$display = false;
		}
		else
			$display = true;	
	
	if ($display == true)
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('fancyboxpack', WP_PLUGIN_URL . "/modal-dialog/fancybox/jquery.fancybox-1.3.1.pack.js", "", "1.3.1");
		wp_enqueue_script('jquerycookies', WP_PLUGIN_URL . "/modal-dialog/jquery.cookie.js", "", "1.0");
	}
}

?>