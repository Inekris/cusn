<?php
/*  Copyright 2014  Jasper Kips  (email : jasper@planetkips.nl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 Plugin Name: Content Unwanted Scraper Notice
 Plugin URI: http://tech.planetkips.nl/content-unwanted-scraper-notice/
 Description: Inserts content at the top of the page to advise of content theft
 Author: Inekris
 Version: 1.1
 Author URI: http://inekris.xs4all.nl/jasper-kips/
 */


//register_uninstall_hook( __FILE__, 'cusn_uninstall' );
/*register_deactivation_hook( __FILE__, 'cusn_deactivate');
*/
//register_activation_hook( __FILE__, 'cusn_install' );
add_action( 'cusn-alert', 'cusn_insert_line' );
function cusn_insert_line( $query ) {
	$cusn_functional_options = get_option( 'cusn-functional-settings' );
	if ( ! cusn_check_condition () ) return;
	if (  is_array( $cusn_functional_options ) && array_key_exists( 'cusn-content-to-add', $cusn_functional_options ) ) {
		$cusn_notice = stripslashes( $cusn_functional_options['cusn-content-to-add'] );
		$e_string = "<h2 style=\"margin:18px 36px\"><pre style=\"white-space:pre-wrap; font-family: inherit; font-size:1em\">" . $cusn_notice . "</pre></h2>";
		echo $e_string;	
	}
	if ( array_key_exists('cusn-show-article', $cusn_functional_options ) && $cusn_functional_options['cusn-show-article'] == "Y" ) return;
	get_footer();
	wp_footer();
	?>
	</body>
	</html>
	<?php exit;
}

function cusn_check_condition () {
	if ( is_multisite() ) {
		$cusn_htaccess_options = get_site_option( 'cusn_site_htaccess_options' );
	}
	else {
		$cusn_htaccess_options = get_option( 'cusn-htaccess-settings' );
	}
	if ( is_array( $cusn_htaccess_options ) && array_key_exists( 'cusn-stop-word', $cusn_htaccess_options ) ) {
		$cusn_env_variable = $cusn_htaccess_options['cusn-stop-word'];
		}
	else {
		return false;
	}
	$p=array_key_exists( $cusn_env_variable, $_SERVER );
	return array_key_exists( $cusn_env_variable, $_SERVER );
}

function cusn_install() {
	$cusn_functional_options = get_option( 'cusn-functional-settings' );
	if ( is_multisite() ) {
		$cusn_htaccess_options = get_site_option( 'cusn_site_htaccess_options' );
		}
	else {
		$cusn_htaccess_options = get_option( 'cusn-htaccess-settings' );
	}
	if ( ! $cusn_functional_options ) $cusn_functional_options = array();
	if ( ! $cusn_htaccess_options ) $cusn_htaccess_options = array();
	// Set sensible defaults
	if ( ! is_multisite() ) $cusn_functional_options['cusn-remove-backups'] = "Y";
	$cusn_htaccess_options['cusn-stop-word'] = "unwanted";
	add_option( 'cusn-functional-settings', $cusn_functional_options );
	if ( ! is_multisite() ) {
		add_option( 'cusn-htaccess-settings', $cusn_htaccess_options );
	}
	else {
		add_site_option( 'cusn_site_htaccess_options', $cusn_htaccess_options );
		
	}
}

function cusn_uninstall() {
	if ( is_multisite() ) return; //Needs to written, sometime
	$cusn_options = get_option( 'cusn-functional-settings' );
	$cusn_uninstall_type = 'remove';
	if ( array_key_exists( 'cusn-remove-backups', $cusn_options ) && $cusn_options['cusn-remove-backups'] == "Y" ) $cusn_uninstall_type = 'uninstall';
	cusn_cleanup( $cusn_uninstall_type );
}

function cusn_deactivate() {
	if ( ! is_multisite() ) {
		$cusn_options = get_option( 'cusn-functional-settings' );
		$cusn_deactivate_type = 'none';
		if ( is_array( $cusn_options ) && array_key_exists( 'cusn-remove-settings-on-deactivate' , $cusn_options ) && $cusn_options['cusn-remove-settings-on-deactivate'] == "Y" ) {
			$cusn_deactivate_type = 'deactivate';
			if ( array_key_exists( 'cusn-remove-backups', $cusn_options ) && $cusn_options['cusn-remove-backups'] == "Y" ) $cusn_deactivate_type = 'uninstall';
		}
		cusn_cleanup( $cusn_deactivate_type );
	}
}

function cusn_cleanup( $type = 'uninstall' ) {
	if( 'none' == $type ) return;
//	$cusn_options = get_option( 'cusn-functional-settings' );
	// Remove .htaccess entries
	$cusn_root = get_home_path();
	if ( ! is_writable( $cusn_root ) ) {
		error_log( "Can't write to $cusn_root, so I can't remove my htaccess entries." );
		error_log( "You can remove the by hand. They are the entries between '# BEGIN cusn' and '# END cusn'. Remove those two lines as well." );
		exit();
		}
	$cusn_htaccess_handle = fopen( $cusn_root . ".htaccess", 'r' );
	if ( $cusn_htaccess_handle ) {
			$cusn_block_1 = ""; // Up to # BEGIN cusn
			$cusn_block_2 = ""; // All other stuff AFTER # END cusn
			$cusn_my_block = ""; // CUSN Block, all stuff from and includig # BEGIN cusn until and include #END cusn, if it is there
			$cusn_block_start = false;
			$cusn_block_end = false;
			while( false !== $buffer = fgets( $cusn_htaccess_handle ) ) { 
			if ( ! $cusn_block_start ) {
			  $cusn_block_start = ( strpos( $buffer, "BEGIN cusn" ) > 0);
			  }
			  if ( $cusn_block_start ) {
					if ( ! $cusn_block_end ) {
						$cusn_my_block .= $buffer;
						if ( strpos( $buffer, "END cusn" ) > 0 ) $cusn_block_end = true;
						} else $cusn_block_2 .= $buffer;
					} elseif ( ! $cusn_block_end ) $cusn_block_1 .= $buffer;
				}
			fclose( $cusn_htaccess_handle );
		unset( $cusn_htaccess_handle );
		}
	else {
		error_log( "Couln't read from $cusn_root/.htaccess, so I can't remove my htaccess entries." );
		error_log( "You can remove the by hand. They are the entries between '# BEGIN cusn' and '# END cusn'. Remove those two lines as well." );
		exit();
		}
	unset( $cusn_htaccess_handle );
	$cusn_htaccess_handle = fopen( $cusn_root . ".htaccess", 'w' );
	if ( $cusn_htaccess_handle ) {
		fwrite( $cusn_htaccess_handle, $cusn_block_1 );
		fwrite( $cusn_htaccess_handle, $cusn_block_2 );
		fclose( $cusn_htaccess_handle );
		}
	else {
		error_log( "Couln't write $cusn_root/.htaccess, so I can't remove my htaccess entries." );
		error_log( "Check NOW" );
		exit();
		}
	unset( $cusn_htaccess_handle );	

	// Remove backups
	if ( $type == 'uninstall' ) {
		$cusn_bu = $cusn_root ."wp-content/cusn-backup";
		if ( is_dir( $cusn_bu ) ) {
			$cusn_dir_handle = opendir( $cusn_bu );
			if ( $cusn_dir_handle ) {
				while ( false !== ( $file = readdir( $cusn_dir_handle ) ) ) {
					if ( $file != "." && $file != ".." ) unlink( $cusn_bu . "/" . $file );
				}
			closedir( $cusn_dir_handle );
			}
		else {
			error_log( "Can't remove $cusn_bu");
			exit;
		}
		rmdir( $cusn_bu );	
		}
	}

// Remove options
	delete_option( 'cusn-functional-settings' );
	if ( ! is_multisite() ) 
		delete_option( 'cusn-htaccess-settings' );

}


//if( is_admin() && current_user_can( 'manage_options' ) )
if ( is_multisite() ) {
	if ( is_admin() ) 
		include_once('mutli/cusn-multi-site-backend.php');
	if ( is_network_admin() )
		include_once('mutli/cusn-network-backend.php');
}
else {
	include_once( 'single/cusn-backend.php' );
}
if ( is_admin() )
    $my_settings_page = new ContentUnwantedScraper();

if( is_network_admin() )
	$my_settings_page = new ContentUnwantedScraperNetwork();
?>