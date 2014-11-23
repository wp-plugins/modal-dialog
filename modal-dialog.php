<?php
/* Plugin Name: Modal Dialog
Plugin URI: http://ylefebvre.ca/modal-dialog/
Description: A plugin used to display a modal dialog to visitors with text content or the contents of an external web site
Version: 3.0.8
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
Copyright 2014  Yannick Lefebvre  (email : ylefebvre@gmail.com)

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

define( 'MODAL_DIALOG_ADMIN_PAGE_NAME', 'modal-dialog' );

define( 'MDDIR', dirname( __FILE__ ) . '/' );

require_once( ABSPATH . '/wp-admin/includes/template.php' );

//class that reperesent the complete plugin
class modal_dialog_plugin {

	//constructor of class, PHP4 compatible construction for backward compatibility
	public function __construct() {

		$options    = get_option( 'MD_PP' );
		$genoptions = get_option( 'MD_General' );

		if ( $genoptions == false ) {
			$this->md_install();
		}

		//add filter for WordPress 2.8 changed backend box system !
		add_filter( 'screen_layout_columns', array( $this, 'on_screen_layout_columns' ), 10, 2 );
		//register callback for admin menu  setup
		add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );
		//register the callback been used if options of page been submitted and needs to be processed
		add_action( 'admin_post_save_modal_dialog_general', array( $this, 'on_save_changes_general' ) );
		add_action( 'admin_post_save_modal_dialog_configurations', array( $this, 'on_save_changes_configurations' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_post_meta_boxes' ) );

		add_action( 'edit_post', array( $this, 'md_editsave_post_field' ) );
		add_action( 'save_post', array( $this, 'md_editsave_post_field' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_filter( 'modal_dialog_content', 'do_shortcode', 11 );

		$genoptions = get_option( 'MD_General' );

		for ( $counter = 1; $counter <= $genoptions['numberofmodaldialogs']; $counter ++ ) {
			$optionsname = "MD_PP" . $counter;
			$options     = get_option( $optionsname );

			if ( $genoptions['disableonmobilebrowsers'] == true ) {
				require_once( plugin_dir_path( __FILE__ ) . '/Mobile_Detect.php' );
				$detect        = new MD_Mobile_Detect;
				$mobilebrowser = $detect->isMobile();
			}

			if ( $options['active'] == true ) {
				if ( $genoptions['disableonmobilebrowsers'] == false || ( $genoptions['disableonmobilebrowsers'] == true && $mobilebrowser == false ) ) {
					add_action( 'wp_head', array( &$this, 'modal_dialog_header' ), 1 );
					add_action( 'wp_footer', array( &$this, 'modal_dialog_footer' ), 1000 );
					break;
				}
			}
		}

		add_action( 'admin_head', array( $this, 'modal_dialog_admin_header' ) );

		add_action( 'comment_post_redirect', array( $this, 'comment_redirect_filter' ), 10, 2 );

		register_activation_hook( __FILE__, array( $this, 'md_install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'md_uninstall' ) );

		return $this;
	}

	function add_post_meta_boxes() {
		// Add addition section to Post/Page Edition page
		add_meta_box( 'modaldialog_meta_box', __( 'Modal Dialog', 'modal-dialog' ), array( $this, 'md_post_edit_extra' ), 'post', 'normal', 'high' );
		add_meta_box( 'modaldialog_meta_box', __( 'Modal Dialog', 'modal-dialog' ), array( $this, 'md_post_edit_extra' ), 'page', 'normal', 'high' );
	}

	function comment_redirect_filter( $location, $comment ) {
		$genoptions = get_option( 'MD_General' );

		for ( $counter = 1; $counter <= $genoptions['numberofmodaldialogs']; $counter ++ ) {
			$optionsname = "MD_PP" . $counter;
			$options     = get_option( $optionsname );

			if ( $options['showaftercommentposted'] == true ) {
				$commentanchorpos = strpos( $location, '#comment' );
				$getpresence      = strpos( $location, '?' );
				if ( $commentanchorpos != false ) {
					if ( $getpresence == false ) {
						$showdialogstring = '?showmodaldialog=' . $counter;
					} else {
						$showdialogstring = '&showmodaldialog=' . $counter;
					}
					$location = substr_replace( $location, $showdialogstring, $commentanchorpos, 0 );
				}
				break;
			}

		}

		return $location;
	}

	//for WordPress 2.8 we have to tell, that we support 2 columns !
	function on_screen_layout_columns( $columns, $screen ) {
		if ( $screen == $this->pagehooktop ) {
			$columns[$this->pagehooktop] = 1;
		} elseif ( $screen == $this->pagehooksettings ) {
			$columns[$this->pagehooksettings] = 1;
		}

		return $columns;
	}

	function modal_dialog_default_config( $confignumber ) {

		$options['dialogname']      = 'Default';
		$options['contentlocation'] = 'URL';
		$options['dialogtext']      = 'Example Dialog Text';

		if ( is_multisite() ) {
			$options['active']      = false;
		} else if ( !is_multisite() ) {
			$options['active']      = true;
		}
		$options['cookieduration']  = 365;
		$options['contenturl']      = 'http://www.google.com';
		$options['pages']           = '';
		$options['overlaycolor']    = '#00CC00';
		$options['textcolor']       = '#000000';
		$options['backgroundcolor'] = '#FFFFFF';
		$options['delay']           = 2000;
		$options['dialogwidth']     = 900;
		$options['dialogheight']    = 700;
		$options['cookiename']      = 'modal-dialog';
		$options['numberoftimes']   = 1;
		$options['exitmethod']      = 'onlyexitbutton';
		$options['autosize']        = false;
		$options['showfrontpage']   = false;

		if ( $confignumber == 1 ) {
			$options['forcepagelist'] = false;
		} elseif ( $confignumber > 1 ) {
			$options['forcepagelist'] = true;
		}

		$options['sessioncookiename']      = 'modal-dialog-session';
		$options['oncepersession']         = false;
		$options['hideclosebutton']        = false;
		$options['ignoreesckey']           = false;
		$options['centeronscroll']         = false;
		$options['manualcookiecreation']   = false;
		$options['overlayopacity']         = '0.3';
		$options['autoclose']              = false;
		$options['autoclosetime']          = 5000;
		$options['checklogin']             = false;
		$options['displayfrequency']       = 1;
		$options['showaftercommentposted'] = false;
		$options['dialogclosingcallback']  = '';
		$options['hidescrollbars']         = false;
		$options['excludepages']           = '';

		$configname = "MD_PP" . $confignumber;
		update_option( $configname, $options );
	}

	function md_install() {
		$options = get_option( 'MD_PP1' );

		if ( $options == false ) {
			$oldoptions = get_option( 'MD_PP' );

			if ( $oldoptions != false ) {
				$oldoptions['dialogname'] = 'Default';
				update_option( 'MD_PP1', $oldoptions );
			} else {
				$this->modal_dialog_default_config( 1 );
			}
		}

		$genoptions = get_option( 'MD_General' );

		if ( $genoptions == false ) {
			$genoptions['numberofmodaldialogs']    = 1;
			$genoptions['primarydialog']           = 1;
			$genoptions['disableonmobilebrowsers'] = false;

			update_option( 'MD_General', $genoptions );
		}
	}

	function md_uninstall() {
	}

	function remove_querystring_var( $url, $key ) {
		$keypos = strpos( $url, $key );
		if ( $keypos ) {
			$ampersandpos = strpos( $url, '&', $keypos );
			$newurl       = substr( $url, 0, $keypos - 1 );

			if ( $ampersandpos ) {
				$newurl .= substr( $url, $ampersandpos );
			}
		} else {
			$newurl = $url;
		}

		return $newurl;
	}

	//extend the admin menu
	function on_admin_menu() {
		//add our own option page, you can also add it to different sections or use your own one
		$this->pagehooktop      = add_menu_page( __( 'Modal Dialog General Options', 'modal-dialog' ), "Modal Dialog", 'manage_options', MODAL_DIALOG_ADMIN_PAGE_NAME, array( $this, 'on_show_page' ), plugins_url( 'icons/ModalDialog16.png', __FILE__ ) );
		$this->pagehooksettings = add_submenu_page( MODAL_DIALOG_ADMIN_PAGE_NAME, __( 'Modal Dialog - Configurations', 'modal-dialog' ), __( 'Configurations', 'modal-dialog' ), 'manage_options', 'modal-dialog-configurations', array( $this, 'on_show_page' ) );
		$this->pagehookfaq      = add_submenu_page( MODAL_DIALOG_ADMIN_PAGE_NAME, __( 'Modal Dialog - FAQ', 'modal-dialog' ), __( 'FAQ', 'modal-dialog' ), 'manage_options', 'modal-dialog-faq', array( $this, 'on_show_page' ) );

		//register  callback gets call prior your own page gets rendered
		add_action( 'load-' . $this->pagehooktop, array( &$this, 'on_load_page' ) );
		add_action( 'load-' . $this->pagehooksettings, array( &$this, 'on_load_page' ) );
		add_action( 'load-' . $this->pagehookfaq, array( &$this, 'on_load_page' ) );
	}

	//will be executed if wordpress core detects this page has to be rendered
	function on_load_page() {
		//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );

		//add several metaboxes now, all metaboxes registered during load page can be switched off/on at "Screen Options" automatically, nothing special to do therefore
		add_meta_box( 'modaldialog_general_meta_box', __( 'General Configuration', 'modal-dialog' ), array( $this, 'general_config_meta_box' ), $this->pagehooktop, 'normal', 'high' );
		add_meta_box( 'modaldialog_general_save_meta_box', __( 'Save General Configuration', 'modal-dialog' ), array( $this, 'general_save_meta_box' ), $this->pagehooktop, 'normal', 'high' );

		add_meta_box( 'modaldialog_dialog_config_selection_meta_box', __( 'Modal Dialog Selection', 'modal-dialog' ), array( $this, 'dialog_config_selection_meta_box' ), $this->pagehooksettings, 'normal', 'high' );
		add_meta_box( 'modaldialog_dialog_config_meta_box', __( 'General Configuration', 'modal-dialog' ), array( $this, 'dialog_config_meta_box' ), $this->pagehooksettings, 'normal', 'high' );
		add_meta_box( 'modaldialog_dialog_config_save_meta_box', __( 'Preview / Save', 'modal-dialog' ), array( $this, 'dialog_config_save_meta_box' ), $this->pagehooksettings, 'normal', 'high' );

		add_meta_box( 'modaldialog_dialog_faq_meta_box', __( 'Frequently Asked Questions', 'modal-dialog' ), array( $this, 'dialog_config_faq_meta_box' ), $this->pagehookfaq, 'normal', 'high' );

	}

	//executed to show the plugins complete admin page
	function on_show_page() {
		//we need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		global $wpdb;

		$genoptions = get_option( 'MD_General' );

		$config = '';

		if ( isset( $_GET['config'] ) && $config == '' ) {
			$config = $_GET['config'];

			if ( $config > $genoptions['numberofmodaldialogs'] ) {
				$config = 1;
			}
		} else {
			$config = 1;
		}

		if ( $_GET['page'] == 'modal-dialog' ) {
			$pagetitle = __( 'Modal Dialog General Settings', 'modal-dialog' );
			$formvalue = 'save_modal_dialog_general';

			if ( isset( $_GET['message'] ) && $_GET['message'] == '1' ) {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'Modal Dialog General Settings Updated', 'modal-dialog' ) . '</strong></div>';
			}
		} elseif ( $_GET['page'] == 'modal-dialog-configurations' ) {
			$pagetitle = __( 'Modal Dialog Configurations', 'modal-dialog' );
			$formvalue = 'save_modal_dialog_configurations';

			if ( isset( $_GET['message'] ) && $_GET['message'] == '1' ) {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'Modal Dialog Configuration Updated', 'modal-dialog' ) . ' (#' . $config . ')</strong></div>';
			}
		} elseif ( $_GET['page'] == 'modal-dialog-faq' ) {
			$pagetitle = __( 'Modal Dialog FAQ', 'modal-dialog' );
			$formvalue = 'save_modal_dialog_faq';
		}

		$configname = 'MD_PP' . $config;
		$options    = get_option( $configname );

		if ( $options == false ) {
			$this->modal_dialog_default_config( $config );
			$options = get_option( $configname );
		}

		//define some data can be given to each metabox during rendering
		$data['options']    = $options;
		$data['config']     = $config;
		$data['genoptions'] = $genoptions;
		?>
		<div id="modal-dialog-general" class="wrap">
			<div class='icon32'><img src="<?php echo plugins_url( '/icons/ModalDialog32.png', __FILE__ ); ?>" /></div>
			<h2><?php echo $pagetitle; ?>
				<span style='padding-left: 50px'><a href="http://ylefebvre.ca/wordpress-plugins/modal-dialog/" target="modaldialog"><img src="<?php echo plugins_url( "/icons/btn_donate_LG.gif", __FILE__ ); ?>" /></a></span>
			</h2>

			<form action="admin-post.php" method="post" id='mdform' enctype='multipart/form-data'>
				<?php wp_nonce_field( 'modal-dialog-general' ); ?>
				<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
				<input type="hidden" name="action" value="<?php echo $formvalue; ?>" />

				<div id="poststuff" class="metabox-holder">
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<?php
							if ( $_GET['page'] == 'modal-dialog' ) {
								do_meta_boxes( $this->pagehooktop, 'normal', $data );
							} elseif ( $_GET['page'] == 'modal-dialog-configurations' ) {
								do_meta_boxes( $this->pagehooksettings, 'normal', $data );
							} elseif ( $_GET['page'] == 'modal-dialog-faq' ) {
								do_meta_boxes( $this->pagehookfaq, 'normal', $data );
							}

							?>
						</div>
					</div>
					<br class="clear" />

				</div>
			</form>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function ($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
			});
			//]]>
		</script>

	<?php
	}

	//executed if the post arrives initiated by pressing the submit button of form
	function on_save_changes_general() {
		//user permission check
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}
		//cross check the given referer
		check_admin_referer( 'modal-dialog-general' );

		$options = get_option( 'MD_General' );

		foreach ( array( 'numberofmodaldialogs', 'primarydialog', 'popupscript' ) as $option_name ) {
			if ( isset( $_POST[$option_name] ) ) {
				$options[$option_name] = $_POST[$option_name];
			}
		}

		foreach ( array( 'disableonmobilebrowsers' ) as $option_name ) {
			if ( isset( $_POST[$option_name] ) ) {
				$options[$option_name] = true;
			} else {
				$options[$option_name] = false;
			}
		}

		update_option( 'MD_General', $options );

		//lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect( $this->remove_querystring_var( $_POST['_wp_http_referer'], 'message' ) . "&message=1" );
	}

	//executed if the post arrives initiated by pressing the submit button of form
	function on_save_changes_configurations() {
		//user permission check
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}
		//cross check the given referer
		check_admin_referer( 'modal-dialog-general' );

		if ( isset( $_POST['configid'] ) ) {
			$configid = $_POST['configid'];
		} else {
			$configid = 1;
		}

		$configname = 'MD_PP' . $configid;
		$options    = get_option( $configname );

		foreach (
			array(
				'dialogtext', 'contentlocation', 'cookieduration', 'contenturl', 'pages', 'overlaycolor', 'textcolor', 'backgroundcolor',
				'delay', 'dialogwidth', 'dialogheight', 'cookiename', 'numberoftimes', 'exitmethod', 'sessioncookiename', 'overlayopacity',
				'autoclosetime', 'dialogname', 'displayfrequency', 'dialogclosingcallback', 'excludepages'
			) as $option_name
		) {
			if ( isset( $_POST[$option_name] ) ) {
				$options[$option_name] = $_POST[$option_name];
			}
		}

		foreach ( array( 'active' ) as $option_name ) {
			if ( isset( $_POST[$option_name] ) && $_POST[$option_name] == "True" ) {
				$options[$option_name] = true;
			} elseif ( isset( $_POST[$option_name] ) && $_POST[$option_name] == "False" ) {
				$options[$option_name] = false;
			}
		}

		foreach (
			array(
				'autosize', 'showfrontpage', 'forcepagelist', 'oncepersession', 'hideclosebutton', 'centeronscroll', 'manualcookiecreation',
				'autoclose', 'checklogin', 'showaftercommentposted', 'hidescrollbars', 'ignoreesckey'
			) as $option_name
		) {
			if ( isset( $_POST[$option_name] ) ) {
				$options[$option_name] = true;
			} else {
				$options[$option_name] = false;
			}
		}

		update_option( $configname, $options );

		//lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect( $this->remove_querystring_var( $_POST['_wp_http_referer'], 'message' ) . "&message=1&config=" . $configid );
	}

	function general_config_meta_box( $data ) {
		$genoptions = $data['genoptions'];
		?>

		<table>
			<tr>
				<td style="width: 200px">Number of Modal Dialogs</td>
				<td>
					<input type="text" id="numberofmodaldialogs" name="numberofmodaldialogs" size="5" value="<?php echo $genoptions['numberofmodaldialogs']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Disable on mobile browsers</td>
				<td>
					<input type="checkbox" id="disableonmobilebrowsers" name="disableonmobilebrowsers" <?php if ( $genoptions['disableonmobilebrowsers'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Pop-Up Dialog Script</td>
				<td>
					<select name="popupscript" id="popupscript" style="width:250px;">
						<option value="colorbox"<?php if ( ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) || !isset( $genoptions['popupscript'] ) ) {
							echo ' selected="selected"';
						} ?>>Colorbox
						</option>
						<option value="fancybox"<?php if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {
							echo ' selected="selected"';
						} ?>>Fancybox (legacy)
						</option>
					</select>
				</td>
			</tr>
		</table>

	<?php
	}

	function general_save_meta_box( $data ) {
		?>
		<div class="submitbox">
			<input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Settings', 'modal-dialog' ); ?>" />
		</div>

	<?php
	}

	function dialog_config_selection_meta_box( $data ) {
		$genoptions = $data['genoptions'];
		$config     = $data['config'];
		?>

		<?php _e( 'Select Current Dialog Configuration', 'modal-dialog' ); ?> :
		<SELECT id="configlist" name="configlist" style='width: 300px'>
			<?php if ( $genoptions['numberofmodaldialogs'] == '' ) {
				$numberofdialogs = 1;
			} else {
				$numberofdialogs = $genoptions['numberofmodaldialogs'];
			}
			for ( $counter = 1; $counter <= $numberofdialogs; $counter ++ ): ?>
				<?php $tempoptionname = "MD_PP" . $counter;
				$tempoptions          = get_option( $tempoptionname ); ?>
				<option value="<?php echo $counter ?>" <?php if ( $config == $counter ) {
					echo 'SELECTED';
				} ?>><?php if ( $counter == 1 ) {
						_e( 'Primary ', 'modal-dialog' );
					} ?><?php _e( 'Dialog', 'modal-dialog' ); ?> <?php echo $counter ?><?php if ( $tempoptions != "" ) {
						echo " (" . $tempoptions['dialogname'] . ")";
					} ?></option>
			<?php endfor; ?>
		</SELECT>
		<INPUT type="button" name="go" value="<?php _e( 'Make Current', 'modal-dialog' ); ?>!" onClick="window.location= 'admin.php?page=modal-dialog-configurations&amp;config=' + jQuery('#configlist').val()">

	<?php
	}

	function dialog_config_meta_box( $data ) {
		$options = $data['options'];
		$config  = $data['config'];
		?>
		<input type='hidden' value='<?php echo $config; ?>' name='configid' id='configid' />
		<table>
			<tr>
				<td style="width:400px">Dialog Name</td>
				<td>
					<input type="text" id="dialogname" name="dialogname" size="40" value="<?php echo $options['dialogname']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Activate</td>
				<td>
					<select name="active" id="active" style="width:250px;">
						<option value="True"<?php if ( $options['active'] == true ) {
							echo ' selected="selected"';
						} ?>>Yes
						</option>
						<option value="False"<?php if ( $options['active'] == false ) {
							echo ' selected="selected"';
						} ?>>No
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Content Source</td>
				<td>
					<select name="contentlocation" id="contentlocation" style="width:250px;">
						<option value="URL"<?php if ( $options['contentlocation'] == 'URL' ) {
							echo ' selected="selected"';
						} ?>>Web Site Address
						</option>
						<option value="Inline"<?php if ( $options['contentlocation'] == 'Inline' ) {
							echo ' selected="selected"';
						} ?>>Specify Below in Dialog Contents
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Appearance Delay (in milliseconds)</td>
				<td><input type="text" id="delay" name="delay" size="5" value="<?php echo $options['delay']; ?>" /></td>
			</tr>
			<tr>
				<td>Web Site Address</td>
				<td colspan=3>
					<input type="text" id="contenturl" name="contenturl" size="40" value="<?php echo $options['contenturl']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Dialog Contents</td>
				<td>
					<TEXTAREA id="dialogtext" NAME="dialogtext" COLS=50 ROWS=10><?php echo esc_html( stripslashes( $options['dialogtext'] ) ); ?></TEXTAREA>
				</td>
			</tr>
			<tr>
				<td>Number of days until cookie expiration</td>
				<td>
					<input type="text" id="cookieduration" name="cookieduration" size="4" value="<?php echo $options['cookieduration']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Number of times to display modal dialog</td>
				<td>
					<input type="text" id="numberoftimes" name="numberoftimes" size="4" value="<?php echo $options['numberoftimes']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Period to display dialog (every # page loads)</td>
				<td>
					<input type="text" id="displayfrequency" name="displayfrequency" size="4" value="<?php echo( $options['displayfrequency'] == '' ? '1' : $options['displayfrequency'] ); ?>" />
				</td>
				</td>
			</tr>
			<tr>
				<td>Cookie Name</td>
				<td>
					<input type="text" id="cookiename" name="cookiename" size="30" value="<?php echo $options['cookiename']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Dialog Exit Method</td>
				<td>
					<select name="exitmethod" id="exitmethod" style="width:100px;">
						<option value="onlyexitbutton"<?php if ( $options['exitmethod'] == 'onlyexitbutton' ) {
							echo ' selected="selected"';
						} ?>>Only Close Button
						</option>
						<option value="anywhere"<?php if ( $options['exitmethod'] == 'anywhere' ) {
							echo ' selected="selected"';
						} ?>>Anywhere
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Show once per session</td>
				<td>
					<input type="checkbox" id="oncepersession" name="oncepersession" <?php if ( $options['oncepersession'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Session Cookie Name</td>
				<td>
					<input type="text" id="sessioncookiename" name="sessioncookiename" size="30" value="<?php if ( $options['sessioncookiename'] != '' ) {
						echo $options['sessioncookiename'];
					} else {
						echo 'modal-dialog-session';
					} ?>" /></td>
			</tr>
			<tr>
				<td>Set display cookies manually</td>
				<td>
					<input type="checkbox" id="manualcookiecreation" name="manualcookiecreation" <?php if ( $options['manualcookiecreation'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="button" id="deletecookies" name="deletecookies" value="Delete All Cookies" /></td>
			</tr>
			<tr>
				<td>Do not show if user logged in</td>
				<td><input type="checkbox" id="checklogin" name="checklogin" <?php if ( $options['checklogin'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Hide Close Button</td>
				<td>
					<input type="checkbox" id="hideclosebutton" name="hideclosebutton" <?php if ( $options['hideclosebutton'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Ignore Escape Key</td>
				<td>
					<input type="checkbox" id="ignoreesckey" name="ignoreesckey" <?php if ( isset( $options['ignoreesckey'] ) && $options['ignoreesckey'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Center on scroll</td>
				<td>
					<input type="checkbox" id="centeronscroll" name="centeronscroll" <?php if ( $options['centeronscroll'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Auto-Size Dialog</td>
				<td><input type="checkbox" id="autosize" name="autosize" <?php if ( $options['autosize'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Hide Scroll Bars</td>
				<td>
					<input type="checkbox" id="hidescrollbars" name="hidescrollbars" <?php if ( $options['hidescrollbars'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Dialog Width</td>
				<td>
					<input type="text" id="dialogwidth" name="dialogwidth" size="4" value="<?php echo $options['dialogwidth']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Dialog Height</td>
				<td>
					<input type="text" id="dialogheight" name="dialogheight" size="4" value="<?php echo $options['dialogheight']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Auto-Close Dialog</td>
				<td><input type="checkbox" id="autoclose" name="autoclose" <?php if ( $options['autoclose'] ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Auto-Close Time (in ms)</td>
				<td>
					<input type="text" id="autoclosetime" name="autoclosetime" size="8" value="<?php echo $options['autoclosetime']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Javascript Dialog Closure Callback</td>
				<td>
					<input type="text" id="dialogclosingcallback" name="dialogclosingcallback" size="30" value="<?php echo $options['dialogclosingcallback']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Only show on specific pages and single posts</td>
				<td><input type="checkbox" <?php if ( $config > 1 ) {
						echo "DISABLED";
					} ?> id="forcepagelist" name="forcepagelist" <?php if ( $options['forcepagelist'] == true ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Display on front page</td>
				<td>
					<input type="checkbox" id="showfrontpage" name="showfrontpage" <?php if ( $options['showfrontpage'] == true ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Pages and posts to display Modal Dialog (empty for all, comma-separated IDs)</td>
				<td><input type="text" id="pages" name="pages" size="50" value="<?php echo $options['pages']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Pages and posts not to display dialog on</td>
				<td>
					<input type="text" id="excludepages" name="excludepages" size="50" value="<?php echo $options['excludepages']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Display after new comment posted</td>
				<td>
					<input type="checkbox" id="showaftercommentposted" name="showaftercommentposted" <?php if ( $options['showaftercommentposted'] == true ) {
						echo ' checked="checked" ';
					} ?>/></td>
			</tr>
			<tr>
				<td>Overlay Color</td>
				<td>
					<input type="text" id="overlaycolor" name="overlaycolor" size="8" value="<?php echo $options['overlaycolor']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Overlay Opacity (0 to 1)</td>
				<td>
					<input type="text" id="overlayopacity" name="overlayopacity" size="8" value="<?php if ( $options['overlayopacity'] == '' ) {
						echo '0.3';
					} else {
						echo $options['overlayopacity'];
					} ?>" /></td>
			</tr>
			<tr>
				<td>Text Color (not used with web site address)</td>
				<td>
					<input type="text" id="textcolor" name="textcolor" size="8" value="<?php echo $options['textcolor']; ?>" />
				</td>
			</tr>
			<tr>
				<td>Background Color</td>
				<td>
					<input type="text" id="backgroundcolor" name="backgroundcolor" size="8" value="<?php echo $options['backgroundcolor']; ?>" />
				</td>

			</tr>
		</table>

		<script type="text/javascript">
			jQuery(document).ready(function () {
				jQuery("#deletecookies").click(function () {
					jQuery.cookie(jQuery("#cookiename").val(), null, {path: '/'});
					jQuery.cookie(jQuery("#sessioncookiename").val(), null, {path: '/'});
					alert("Deleted all cookies");
				});
			});
		</script>
	<?php
	}

	function dialog_config_save_meta_box( $data ) {
		$options = $data['options'];
		?>
		<!-- <span>
			<input type="button" class="button" id="previewdialog" name="previewdialog" value="Preview Modal Dialog" />
		</span> -->
		<span class="submitbox">
			<input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Settings', 'modal-dialog' ); ?>" />
		</span>

		<!--
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#previewdialog").click(function() {
					alert("Test");
				});
			});
		</script>
		-->

	<?php
	}

	function md_post_edit_extra( $post ) {
		global $wpdb;

		$genoptions = get_option( 'MD_General' );

		if ( $post->ID != '' ) {
			$dialogid = get_post_meta( $post->ID, "modal-dialog-id", true );
			if ( $dialogid == "" ) {
				$dialogid = 0;
			}
		} else {
			$dialogid = 0;
		}
		?>
		<table>
			<tr>
				<td colspan="2">Setting a dialog here will take priority over any other dialog specified under the Modal Dialog Settings page.</td>
			</tr>
			<tr>
				<td style='width: 200px'><?php _e( 'Modal Display to Display', 'modal-dialog' ); ?></td>
				<td>
					<SELECT id="dialogid" name="dialogid" style='width: 300px'>
						<option value="0">None</option>
						<?php if ( $genoptions['numberofmodaldialogs'] == '' ) {
							$numberofdialogs = 1;
						} else {
							$numberofdialogs = $genoptions['numberofmodaldialogs'];
						}
						for ( $counter = 1; $counter <= $numberofdialogs; $counter ++ ): ?>
							<?php $tempoptionname = "MD_PP" . $counter;
							$tempoptions          = get_option( $tempoptionname ); ?>
							<option value="<?php echo $counter ?>" <?php if ( $dialogid == $counter ) {
								echo 'SELECTED';
							} ?>><?php _e( 'Dialog', 'modal-dialog' ); ?> <?php echo $counter ?><?php if ( $tempoptions != "" ) {
									echo " (" . $tempoptions['dialogname'] . ")";
								} ?></option>
						<?php endfor; ?>
					</SELECT>
				</td>
			</tr>
		</table>
	<?php
	}

	function md_editsave_post_field( $post_id ) {
		if ( isset( $_POST['dialogid'] ) ) {
			update_post_meta( $post_id, "modal-dialog-id", $_POST['dialogid'] );
		}
	}

	function dialog_config_faq_meta_box() {
		?>

		<h2>Why is Modal Dialog not showing up on my web site?</h2>

		<p>There are typically two main reasons why Modal Dialog does not show up correctly on web pages:</p>

		<ol>
			<li>You have another plugin installed which uses jQuery on your site that has its own version of jQuery instead of using the default version that is part of the Wordpress install. To see if this is the case, go to your site and look at the page source, then search for jQuery. If you see some versions of jQuery that get loaded from plugin directories, then this is most likely the source of the problem as they would conflict with the copy of jQuery that is delivered with Wordpress.</li>

			<li>The other thing to check is to see if your theme has the wp_head and wp_footer functions in the theme's header. If these functions are not present, then the plugin will not work as expected.</li>

			<li>The last potential issue is a javascript conflict on your site. Install a browser plugin such as Firebug and look at the console to see if any javascript errors are display. Then look at the problematic plugin and try to fix the problem. A single javascript error will prevent all remaining javascript from executing.</li>
		</ol>

		<p>You can send me a link to your web site if these solutions don't help you so that I can see what is happening myself and try to provide a solution.</p>

		<h2>How can I make Modal Dialog open when I click on a button or a link?</h2>

		<p>Add the onclick property to your link or button code and call the <code>modal_dialog_open()</code> function.
		</p>

		<p>
			<code>&lt;a href=&quot;#&quot; onclick=&quot;modal_dialog_open();&quot; title=&quot;Learn More&quot;&gt;Learn More&lt;/a&gt;</code>
		</p>

		<p><code>&lt;button onclick=&quot;modal_dialog_open();&quot;&gt;Open Modal&lt;/button&gt;</code></p>

		<h2>How can I close the Modal Dialog Window Manually?</h2>

		<p>You can create a button or other control that calls the <code>modal_dialog_close()</code> function:</p>

		<p>
			<code>&lt;a href=&quot;#&quot; onclick=&quot;modal_dialog_close();&quot; title=&quot;Close dialog&quot;&gt;Learn More&lt;/a&gt;</code>
		</p>

		<p><code>&lt;button onclick=&quot;modal_dialog_close();&quot;&gt;Close Modal&lt;/button&gt;</code></p>

		<h2>How can I manually set the cookie if I ask Modal Dialog to let me do it manually?</h2>

		<p>Call the following javascript / jQuery function, setting the cookie-name to match the name entered in the Modal Dialog settings, the cookievalue and the duration to any duration that you deem acceptable.</p>

		<p>jQuery.cookie('cookie-name', cookievalue, { expires: 365 });</p>


	<?php
	}

	function modal_dialog_admin_header( $dialogname = "MD_PP1" ) {

		$genoptions = get_option( 'MD_General' );
		$options    = get_option( $dialogname );

		if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {
			echo "<link rel='stylesheet' type='text/css' media='screen' href='" . plugins_url( "fancybox/jquery.fancybox-1.3.4.css", __FILE__ ) . "'/>\n";
			echo "<!-- [if lt IE 7] -->\n";
			echo "<style type='text/css'>\n";

			echo "/*IE*/\n";
			echo "#fancybox-loading.fancybox-ie div	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_loading.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancybox-close		{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_close.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancybox-title-over	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_title_over.png", __FILE__ ) . ", sizingMethod='scale'); zoom: 1; }\n";
			echo ".fancybox-ie #fancybox-title-left	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_title_left.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancybox-title-main	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_title_main.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancybox-title-right	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_title_right.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancybox-left-ico		{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_nav_left.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancybox-right-ico	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_nav_right.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie .fancy-bg { background: transparent !important; }\n";

			echo ".fancybox-ie #fancy-bg-n	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_n.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-ne	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_ne.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-e	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_e.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-se	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_se.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-s	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_s.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-sw	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_sw.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-w	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_w.png", __FILE__ ) . ", sizingMethod='scale'); }\n";
			echo ".fancybox-ie #fancy-bg-nw	{ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" . plugins_url( "fancybox/fancy_shadow_nw.png", __FILE__ ) . ", sizingMethod='scale'); }\n";

			echo "</style>";
			echo "<!-- [endif] -->\n";
		} else if ( !isset( $genoptions['popupscript'] ) || ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) ) {

			if ( empty ( $options['overlaycolor'] ) ) {
				$options['overlaycolor'] = "rgba(200, 200, 200, " . ( empty( $options['overlayopacity'] ) ? '0.3' : $options['overlayopacity'] ) . ")";
			} else {
				$colorarray = $this->hex2rgb( $options['overlaycolor'] );

				$options['overlaycolor'] = "rgba(" . $colorarray . ", " . ( empty( $options['overlayopacity'] ) ? '0.3' : $options['overlayopacity'] ) . ")";
			}
			?>
			<style type="text/css">
				#cboxOverlay {
					background: repeat 0 0 <?php echo $options['overlaycolor']; ?>;
				}
			</style>
		<?php
		}
	}

	function modal_dialog_header( $manualdisplay = false ) {
		global $post;
		$thePostID  = $post->ID;
		$display    = false;
		$dialogname = '';

		if ( isset( $_GET['showmodaldialog'] ) ) {
			$display    = true;
			$dialogname = "MD_PP" . $_GET['showmodaldialog'];
		} elseif ( !empty( $thePostID ) ) {
			$dialogid = get_post_meta( $post->ID, "modal-dialog-id", true );
			if ( !empty( $dialogid ) && $dialogid != 0 ) {
				$display = true;
			}
			$dialogname = "MD_PP" . $dialogid;
		}

		if ( $display == false ) {
			$genoptions = get_option( 'MD_General' );

			for ( $counter = 1; $counter <= $genoptions['numberofmodaldialogs']; $counter ++ ) {
				$dialogid    = $counter;
				$optionsname = "MD_PP" . $counter;
				$options     = get_option( $optionsname );

				if ( $options['checklogin'] == false || $options['checklogin'] == '' || ( $options['checklogin'] == true && !is_user_logged_in() ) ) {
					if ( ( $options['active'] || $manualdisplay ) && !is_admin() ) {
						if ( $options['showfrontpage'] && is_front_page() ) {
							$display    = true;
							$dialogname = $optionsname;
							break;
						} elseif ( $options['showfrontpage'] == false && is_front_page() ) {
							$display = false;
						} elseif ( $options['forcepagelist'] == true ) {
							if ( $options['pages'] != '' ) {
								$pagelist = explode( ',', $options['pages'] );

								if ( $pagelist ) {
									foreach ( $pagelist as $pageid ) {
										if ( is_page( intval( $pageid ) ) || is_single( $pageid ) ) {
											$display    = true;
											$dialogname = $optionsname;
											break 2;
										} else {
											$display = false;
										}
									}
								}
							}
						} elseif ( $options['forcepagelist'] == false && !is_front_page() ) {
							$display    = true;
							$dialogname = $optionsname;
						} elseif ( $manualdisplay == true ) {
							$display    = true;
							$dialogname = $optionsname;
						}

						if ( !empty( $options['excludepages'] ) ) {
							$exclude_page_list = explode( ',', $options['excludepages'] );

							if ( $exclude_page_list ) {
								foreach ( $exclude_page_list as $excluded_page_id ) {
									if ( is_page( intval( $excluded_page_id ) ) || is_single( $excluded_page_id ) ) {
										$display = false;
									}
								}
							}
						}
					}
				} else {
					$display = false;
				}
			}
		}


		if ( $display == true ) {
			$this->modal_dialog_admin_header( $dialogname );
		}
	}

	function hex2rgb( $hex ) {
		$hex = str_replace( "#", "", $hex );

		if ( strlen( $hex ) == 3 ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}
		$rgb = array( $r, $g, $b );

		return implode( ",", $rgb ); // returns the rgb values separated by commas
	}

	function modal_dialog_footer( $manualdisplay = false ) {
		global $post;
		$thePostID = $post->ID;
		$display   = false;

		wp_reset_query();

		if ( isset( $_GET['showmodaldialog'] ) ) {
			$display  = true;
			$dialogid = $_GET['showmodaldialog'];
		} elseif ( !empty( $thePostID ) ) {
			$dialogid = get_post_meta( $post->ID, "modal-dialog-id", true );
			if ( !empty( $dialogid ) && $dialogid != 0 ) {
				$display = true;
			} else {
				$dialogid = 1;
			}
		}

		if ( $display == false ) {
			$genoptions = get_option( 'MD_General' );
			for ( $counter = 1; $counter <= $genoptions['numberofmodaldialogs']; $counter ++ ) {
				$dialogid    = $counter;
				$optionsname = "MD_PP" . $counter;
				$options     = get_option( $optionsname );

				if ( $options['checklogin'] == false || $options['checklogin'] == '' || ( $options['checklogin'] == true && !is_user_logged_in() ) ) {
					if ( ( $options['active'] || $manualdisplay ) && !is_admin() ) {
						if ( $options['showfrontpage'] && is_front_page() ) {
							$display = true;
							break;
						} elseif ( $options['showfrontpage'] == false && is_front_page() ) {
							$display = false;
						} elseif ( $options['forcepagelist'] == true ) {
							if ( $options['pages'] != '' ) {
								$pagelist = explode( ',', $options['pages'] );

								if ( $pagelist ) {
									foreach ( $pagelist as $pageid ) {
										if ( is_page( intval( $pageid ) ) || is_single( $pageid ) ) {
											$display = true;
											break 2;
										} else {
											$display = false;
										}
									}
								}
							}
						} elseif ( $options['forcepagelist'] == false && !is_front_page() ) {
							$display = true;
						} elseif ( $manualdisplay == true ) {
							$display = true;
						}

						if ( !empty( $options['excludepages'] ) ) {
							$exclude_page_list = explode( ',', $options['excludepages'] );

							if ( $exclude_page_list ) {
								foreach ( $exclude_page_list as $excluded_page_id ) {
									if ( is_page( intval( $excluded_page_id ) ) || is_single( $excluded_page_id ) ) {
										$display = false;
									}
								}
							}
						}
					}
				} else {
					$display = false;
				}
			}
		}

		if ( $display == true && $dialogid != 0 ) {
			global $wpdb;

			$optionsname = "MD_PP" . $dialogid;
			$options     = get_option( $optionsname );
			$genoptions  = get_option( 'MD_General' );

			$output = "<!-- Modal Dialog Output -->\n";

			if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {

				if ( $options['contentlocation'] == 'Inline' ) {

					$output .= "<a id=\"inline\" href=\"#data\"></a>\n";
					$output .= "<div style=\"display:none\"><div id=\"data\" style=\"color:" . $options['textcolor'] . ";background-color:" . $options['backgroundcolor'] . ";width:100%;height:100%\">";
					$output .= apply_filters( 'modal_dialog_content', stripslashes( $options['dialogtext'] ) );

					$output .= "</div></div>\n";

				} elseif ( $options['contentlocation'] == "URL" ) {
					$output .= "<a href='" . $options['contenturl'] . "' class='iframe'></a>\n";
				}

			} else if ( !isset( $genoptions['popupscript'] ) || ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) ) {

				if ( $options['contentlocation'] == 'Inline' ) {

					$output .= "<a class='inline' href='#inline_content'></a>\n";
					$output .= "<div style='display:none'>";
					$output .= "<div id='inline_content' style='padding:10px;color:" . $options['textcolor'] . ";background-color:" . $options['backgroundcolor'] . "'>";
					$output .= apply_filters( 'modal_dialog_content', stripslashes( $options['dialogtext'] ) );

					$output .= "</div></div>\n";

				} elseif ( $options['contentlocation'] == "URL" ) {
					$output .= "<a href='" . $options['contenturl'] . "' class='iframe'></a>\n";
				}

			}

			$output .= "<div id='md-content'>\n";

			$output .= "<script type=\"text/javascript\">\n";

			$output .= "function modal_dialog_open() {\n";

			if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {
				if ( $options['contentlocation'] == 'Inline' || empty( $options['contentlocation'] ) ) {
					$output .= "jQuery(\"a#inline\").trigger('click')\n";
				} elseif ( $options['contentlocation'] == 'URL' ) {
					$output .= "jQuery(\"a.iframe\").trigger('click')\n";
				}
			} else if ( !isset( $genoptions['popupscript'] ) || ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) ) {
				if ( $options['contentlocation'] == 'Inline' || empty( $options['contentlocation'] ) ) {
					$output .= "jQuery(\"a.inline\").trigger('click')\n";
				} elseif ( $options['contentlocation'] == 'URL' ) {
					$output .= "jQuery(\"a.iframe\").trigger('click')\n";
				}

			}

			$output .= "}\n";

			$output .= "function modal_dialog_close() {\n";

			if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {
				$output .= "parent.jQuery.fancybox.close();";
			} else if ( !isset( $genoptions['popupscript'] ) || ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) ) {
				$output .= "jQuery.colorbox.close();";
			}

			$output .= "}\n";

			$output .= "jQuery(document).ready(function() {\n";

			if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {
				if ( $options['contentlocation'] == 'Inline' || empty( $options['contentlocation'] ) ) {
					$output .= "jQuery(\"a#inline\").fancybox({\n";
				} elseif ( $options['contentlocation'] == 'URL' ) {
					$output .= "jQuery(\"a.iframe\").fancybox({\n";
				}

				if ( $options['exitmethod'] == 'onlyexitbutton' ) {
					$output .= "'hideOnOverlayClick': false,\n";
					$output .= "'hideOnContentClick': false,\n";
				} elseif ( $options['exitmethod'] == 'anywhere' ) {
					$output .= "'hideOnOverlayClick': true,\n";
					$output .= "'hideOnContentClick': false,\n";
				}

				if ( true == $options['hideclosebutton'] ) {
					$output .= "'enableEscapeButton': false,\n";
				} else {
					$output .= "'enableEscapeButton': true,\n";
				}

				if ( isset( $options['ignoreesckey'] ) && true == $options['ignoreesckey'] ) {
					$output .= "'showCloseButton': false,\n";
				} else {
					$output .= "'showCloseButton': true,\n";
				}

				if ( $options['centeronscroll'] == true ) {
					$output .= "'centerOnScroll': true,\n";
				}

				if ( $options['hidescrollbars'] == true ) {
					$output .= "'scrolling': 'no',\n";
				}

				if ( $options['dialogclosingcallback'] != '' ) {
					$output .= "'onClosed': function() {" . $options['dialogclosingcallback'] . "},\n";
				}

				if ( $options['autosize'] == true || empty( $options['autosize'] ) ) {
					$output .= "'autoDimensions': true,\n";
				} elseif ( $options['autosize'] == false ) {
					$output .= "'autoDimensions': false,\n";
				}

				if ( $options['sessioncookiename'] != '' ) {
					$sessioncookiename = $options['sessioncookiename'];
				} else {
					$sessioncookiename = 'modaldialogsession';
				}

				$output .= "'overlayColor': '" . $options['overlaycolor'] . "',\n";
				$output .= "'width': '" . $options['dialogwidth'] . "',\n";
				$output .= "'height': '" . $options['dialogheight'] . "',\n";

				if ( $options['overlayopacity'] == '' ) {
					$options['overlayopacity'] = '0.3';
				}

				$output .= "'overlayOpacity': " . $options['overlayopacity'] . "\n";
				$output .= "});\n";
			} else if ( !isset( $genoptions['popupscript'] ) || ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) ) {

				if ( $options['contentlocation'] == 'Inline' || empty( $options['contentlocation'] ) ) {
					$output .= "jQuery(\"a.inline\").colorbox({\n";
					$output .= "inline: true,";
					$output .= "returnFocus: false,";
				} elseif ( $options['contentlocation'] == 'URL' ) {
					$output .= "jQuery(\"a.iframe\").colorbox({\n";
					$output .= "iframe: true,";
					$output .= "returnFocus: false,";
				}

				if ( $options['exitmethod'] == 'onlyexitbutton' ) {
					$output .= "overlayClose: false,\n";
				} elseif ( $options['exitmethod'] == 'anywhere' ) {
					$output .= "overlayClose: true,\n";
				}

				if ( $options['hideclosebutton'] == true ) {
					$output .= "closeButton: false,\n";
				} else {
					$output .= "closeButton: true,\n";
				}

				if ( isset( $options['ignoreesckey'] ) && true == $options['ignoreesckey'] ) {
					$output .= "escKey: false,\n";
				} else {
					$output .= "escKey: true,\n";
				}

				if ( $options['centeronscroll'] == true ) {
					$output .= "fixed: true,\n";
				}

				if ( $options['hidescrollbars'] == true ) {
					$output .= "scrolling: 'false',\n";
				}

				if ( $options['dialogclosingcallback'] != '' ) {
					$output .= "onClosed: function() {" . $options['dialogclosingcallback'] . "},\n";
				}

				if ( $options['autosize'] == true ) {
					$output .= "width: '80%',\n";
					$output .= "height: '80%',\n";
				} else {
					$output .= "width: '" . $options['dialogwidth'] . "',\n";
					$output .= "height: '" . $options['dialogheight'] . "',\n";
				}

				if ( $options['sessioncookiename'] != '' ) {
					$sessioncookiename = $options['sessioncookiename'];
				} else {
					$sessioncookiename = 'modaldialogsession';
				}

				if ( $options['overlayopacity'] == '' ) {
					$options['overlayopacity'] = '0.3';
				}

				$output .= "overlayOpacity: " . $options['overlayopacity'] . "\n";

				$output .= "});\n";
			}

			if ( $options['oncepersession'] == true ) {
				$output .= "var sessioncookie = jQuery.cookie('" . $sessioncookiename . "');\n";
				$output .= "if (sessioncookie == null)\n";
				$output .= "{\n";
				if ( $options['manualcookiecreation'] == false ) {
					$output .= "\tjQuery.cookie('" . $sessioncookiename . "', 0, { path: '/' });\n";
				}
			}

			$output .= "\tvar cookievalue = jQuery.cookie('" . $options['cookiename'] . "');\n";

			if ( $options['displayfrequency'] != 1 && $options['displayfrequency'] != '' && $options['showaftercommentposted'] == false ) {
				$output .= "\tvar cookiechecksvalue = jQuery.cookie('" . $options['cookiename'] . "_checks');\n";
				$output .= "\tif (cookiechecksvalue == null) cookiechecksvalue = 0;\n";
			}

			$output .= "\tif (cookievalue == null) cookievalue = 0;\n";

			$output .= "\tif (cookievalue < " . $options['numberoftimes'] . ")\n";

			$output .= "\t{\n";

			if ( $options['displayfrequency'] != 1 && $options['displayfrequency'] != '' && $options['showaftercommentposted'] == false ) {
				$output .= "\t\tcookiechecksvalue++;\n";
				$output .= "\t\tjQuery.cookie('" . $options['cookiename'] . "_checks', cookiechecksvalue";

				if ( $options['cookieduration'] > 0 ) {
					$output .= ", { expires: " . $options['cookieduration'] . ", path: '/'}";
				} else {
					$output .= ", { path: '/' }";
				}

				$output .= ");\n";

				$output .= "\t\tif (cookiechecksvalue % " . $options['displayfrequency'] . " == 0) {\n";
			}

			if ( $options['manualcookiecreation'] == false ) {
				$output .= "\t\tcookievalue++;\n";
				$output .= "\t\tjQuery.cookie('" . $options['cookiename'] . "', cookievalue";

				if ( $options['cookieduration'] > 0 ) {
					$output .= ", { expires: " . $options['cookieduration'] . ", path: '/'}";
				} else {
					$output .= ", { path: '/' }";
				}

				$output .= ");\n";
			}

			$output .= "\t\tsetTimeout(\n";
			$output .= "\t\t\tfunction(){\n";

			$output .= "\t\t\t\tmodal_dialog_open(); ";

			$output .= "\t\t\t}, " . $options['delay'] . ");\n";
			$output .= "\t\t};\n";

			if ( $options['displayfrequency'] != 1 && $options['displayfrequency'] != '' && $options['showaftercommentposted'] == false ) {
				$output .= '}';
			}

			if ( $options['oncepersession'] == true ) {
				$output .= "\t}\n";
			}

			$output .= "});\n";
			if ( $options['autoclose'] == true && $options['autoclosetime'] != 0 ) {
				$output .= "setTimeout('modal_dialog_close();'," . $options['autoclosetime'] . ");\n";
			}
			$output .= "</script>\n";

			$output .= "</div>\n";

			$output .= "<!-- End of Modal Dialog Output -->\n";

			echo $output;
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );

		$genoptions = get_option( 'MD_General' );

		wp_enqueue_script( 'jquerycookies', plugins_url( "cookie.js", __FILE__ ), "", "1.0" );

		if ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'fancybox' ) {
			wp_enqueue_script( 'fancyboxpack', plugins_url( "fancybox/jquery.fancybox-1.3.4.pack.js", __FILE__ ), "", "1.3.4" );
		} else if ( !isset( $genoptions['popupscript'] ) || ( isset( $genoptions['popupscript'] ) && $genoptions['popupscript'] == 'colorbox' ) ) {
			wp_enqueue_script( 'colorboxpack', plugins_url( "colorbox/jquery.colorbox-min.js", __FILE__ ), "", "1.5.6" );
			wp_enqueue_style( 'colorboxstyle', plugins_url( "colorbox/colorbox.css", __FILE__ ), "", "1.5.6" );
		}
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'jquerycookies', plugins_url( "cookie.js", __FILE__ ), "", "1.0" );
	}
}

global $my_modal_dialog_plugin;
$my_modal_dialog_plugin = new modal_dialog_plugin();

?>