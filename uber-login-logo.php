<?php
/**
 * Plugin Name: Uber Login Logo
 * Plugin URI: http://www.uberweb.com.au/uber-login-logo-wordpress-plugin/
 * Description: Change your login logo.
 * Version: 1.5.1
 * Author: UberWeb
 * Author URI: http://www.uberweb.com.au/
 * Text Domain: uber-login-logo
 * Domain Path: /languages/
 * License: GPLv2 or later
 *
 * @package Uber_Login_Logo
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Enable the plugin for the init hook, but only if WP is loaded. Calling this php file directly will do nothing.
if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
	add_action( 'wp_loaded', array( 'UberLoginLogo', 'init' ) );
}

/**
 * Main class for Uber Login Logo, does it all.
 *
 * @package Uber_Login_Logo
 * @todo Uninstall plugin hook
 * @todo I18n Support
 */
class UberLoginLogo {

	/**
	 * The current plugin version.
	 *
	 * @const VERSION
	 */
	const VERSION = '1.5.1';

	/**
	 * Link to uberweb site.
	 *
	 * @const UBERURL Link to uberweb site
	 */
	const UBERURL = 'http://www.uberweb.com.au';

	/**
	 * Fire up the plugin and register them hooks
	 */
	public static function init() {
		load_plugin_textdomain( 'uber-login-logo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_action( 'admin_menu', array( 'UberLoginLogo', 'register_admin_menu' ) );
		add_filter( 'plugin_action_links', array( 'UberLoginLogo', 'register_plugin_settings_link' ), 10, 2 );
		add_action( 'wp_ajax_getImageData', array( 'UberLoginLogo', 'get_image_data' ) );
		add_action( 'wp_ajax_displayPreviewImg', array( 'UberLoginLogo', 'display_preview_img' ) );
		add_action( 'login_head', array( 'UberLoginLogo', 'replace_login_logo' ) );
		add_filter( 'login_headerurl', array( 'UberLoginLogo', 'replace_login_url' ) );
		add_filter( 'login_headertext', array( 'UberLoginLogo', 'replace_login_title' ) );
		register_uninstall_hook( self::get_base_name(), array( 'UberLoginLogo', 'uninstall' ) );

		// Load only on plugin admin page.
		if ( isset( $_GET['page'] ) && self::get_base_name() === $_GET['page'] ) { //phpcs:ignore
			add_action( 'admin_enqueue_scripts', array( 'UberLoginLogo', 'my_admin_scripts_and_styles' ) );
		}
	}
	/**
	 * Load scripts and styles for plugin admin page
	 */
	public static function my_admin_scripts_and_styles() {
		wp_register_style(
			'uber-login-logo',
			self::get_plugin_dir() . '/uber-login-logo-min.css',
			array(),
			self::VERSION
		);
		wp_register_script(
			'uber-login-logo',
			self::get_plugin_dir() . '/uber-login-logo-min.js',
			array( 'jquery', 'media-upload', 'thickbox', 'underscore' ),
			self::VERSION,
			true
		);

		wp_enqueue_media();
		wp_enqueue_style( 'uber-login-logo' );
		wp_enqueue_script( 'uber-login-logo' );
	}

	/**
	 * Setup admin menu and add options page
	 */
	public static function register_admin_menu() {
		if ( function_exists( 'add_options_page' ) ) {
			$page_title = __( 'Uber Login Logo Settings', 'uber-login-logo' );
			$menu_title = 'Uber Login Logo';
			$capability = 'manage_options';
			$menu_slug  = self::get_base_name();
			$function   = array( 'UberLoginLogo', 'show_options_page' );

			add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function );
		}
	}

	/**
	 * Add settings link to plugin page.
	 *
	 * @param array  $links Array of plugin option links.
	 * @param string $file Handle to plugin filename.
	 * @return array Modified list of plugin option links.
	 */
	public static function register_plugin_settings_link( $links, $file ) {
		$this_plugin = self::get_base_name();

		if ( $file === $this_plugin ) {
			$settings_link = '<a href="' . admin_url() . 'options-general.php?page=' . $this_plugin . '">' . __( 'Settings', 'uber-login-logo' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Generate the HTML to display the plugin settings page
	 *
	 * @TODO seperate presentation logic
	 */
	public static function show_options_page() {
		?>

		<div class="wrap uber-login-logo">
			<h2><?php esc_html_e( 'Uber Login Logo', 'uber-login-logo' ); ?></h2>

			<div class="updated fade update-status">
				<p><strong><?php esc_html_e( 'Settings Saved', 'uber-login-logo' ); ?></strong></p>
			</div>

			<h3><?php esc_html_e( 'How it Works', 'uber-login-logo' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Use the WordPress media uploader to upload an image, or select one from the media library.', 'uber-login-logo' ); ?></li>
				<li><?php esc_html_e( 'It is highly recommended that you select an image with a width less than 320px.', 'uber-login-logo' ); ?></li>
				<li><?php esc_html_e( 'Select your desired image size and click "insert into post".', 'uber-login-logo' ); ?></li>
				<li><?php esc_html_e( 'Finished!', 'uber-login-logo' ); ?></li>
			</ol>
			<form class="inputfields">
				<input id="upload-input" type="text" size="36" name="upload image" class="upload-image" value="" />
				<input id="upload-button" type="button" value="<?php esc_html_e( 'Upload Image', 'uber-login-logo' ); ?>" class="upload-image" />
				<?php wp_nonce_field( 'uber_login_logo_action', 'uber_login_logo_nonce' ); ?>
			</form>
			<div class="img-holder">
				<p><?php esc_html_e( 'Here is a preview of your selected image at actual size', 'uber-login-logo' ); ?></p>
				<div class="img-preview"></div>
			</div>
		</div>

		<?php
	}

	/**
	 * Replace the login logo on wp-admin.
	 */
	public static function replace_login_logo() {
		$img_data = get_option( 'uber_login_logo' );

		// Use https for background-image if on ssl.
		if ( is_ssl() ) {
			$img_data['src'] = preg_replace( '/^http:/i', 'https:', $img_data['src'] );
		}

		if ( $img_data ) {
			$style  = '<style type="text/css">';
			$style .= sprintf( '.login h1 a { background: transparent url("%s") no-repeat center top; background-size:%spx %spx; height: %spx; width:auto; }', $img_data['src'], $img_data['width'], $img_data['height'], $img_data['height'] );
			$style .= '</style>';
			$style .= "\r\n" . '<!-- Uber Login Logo ' . self::VERSION . ' ' . self::UBERURL . ' -->' . "\r\n";
			echo $style; //phpcs:ignore
		}
	}

	/**
	 * Retrieve the img data via AJAX and save as WordPress option
	 */
	public static function get_image_data() {
		if ( ! empty( $_POST ) && check_admin_referer( 'uber_login_logo_action', 'uber_login_logo_nonce' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				// sanitize inputs.
				$img_id   = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
				$img_size = filter_input( INPUT_POST, 'size', FILTER_SANITIZE_STRING );

				// get the img at the correct size.
				$img = wp_get_attachment_image_src( $img_id, $img_size );

				// save src + attribs in the DB.
				$img_data['id']     = $img_id;
				$img_data['src']    = $img[0];
				$img_data['width']  = $img[1];
				$img_data['height'] = $img[2];

				update_option( 'uber_login_logo', $img_data );

				$returnval = array(
					'src' => $img_data['src'],
					'id'  => $img_data['id'],
				);
				wp_send_json( $returnval );
			}
		}
	}

	/**
	 * Display the currently set login logo img
	 */
	public static function display_preview_img() {
		if ( ! empty( $_POST ) && check_admin_referer( 'uber_login_logo_action', 'uber_login_logo_nonce' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$img_data = get_option( 'uber_login_logo' );
				if ( $img_data ) {
					$returnval = array(
						'src' => $img_data['src'],
						'id'  => $img_data['id'],
					);
					wp_send_json( $returnval );
				} else {
					wp_die( false );
				}
			}
		}
	}

	/**
	 * Remove saved options on uninstall
	 */
	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( "I\'m afraid I can\' do that." );
		}

		check_admin_referer( 'bulk-plugins' );

		delete_option( 'uber_login_logo' );
	}

	/**
	 * Retrieve the Home URL
	 *
	 * @return string Home URL
	 */
	public static function replace_login_url() {
		return home_url();
	}

	/**
	 * Retrieve the Site Description
	 *
	 * @return string Site Description
	 */
	public static function replace_login_title() {
		return get_bloginfo( 'description' );
	}

	/**
	 * Retrieve the unique plugin basename
	 *
	 * @return string Plugin basename
	 */
	public static function get_base_name() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Retrieve the URL to the plugin basename
	 *
	 * @return string Plugin basename URL
	 */
	public static function get_plugin_dir() {
		return WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) );
	}
}

?>
