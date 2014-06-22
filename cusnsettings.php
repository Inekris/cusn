<?php
defined( 'ABSPATH' ) OR exit;
class ContentUnwantedScraper
{
	
    private $functional_options;
    private $htaccess_options;
    
    

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        $this->functional_options = get_option( 'cusn-functional-settings' );
        $this->htaccess_options = get_option( 'cusn-htaccess-settings' );
        load_plugin_textdomain( 'cusn', false, dirname( plugin_basename( __FILE__ ) ) .'/languages/' );

    }
   
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        $page = add_options_page(
	            'Settings Screen', 
	            'CUSN Settings', 
	            'manage_options', 
	            'cusn-settings', 
	            array( $this, 'create_admin_page' )
	        );
	    add_action( 'admin_print_styles-' . $page, array( $this, 'cusn_add_admin_style' ) );
    }
    

    public function cusn_add_admin_style() {
	    wp_enqueue_style( 'cusn_style_sheet' );
    }

    public function create_admin_page()
    {

        ?>
        
        <div class="wrap">
            <h2><?php _e( "Content Unwanted Scraper Options", 'cusn' ); ?></h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'cusn-functional-settings' );   
                do_settings_sections( 'cusn-functional-settings-page' );
                submit_button(); 
            ?>
            </form>
            <hr class="cusn-section-hr" />
            <form method="post" action="options.php">
            	<?php
            	settings_fields( 'cusn-htaccess-settings');
            	do_settings_sections( 'cusn-htaccess-settings-page' );
            	//submit_button();
            	?>
            </form>
        </div>
        <?php
    }

    public function page_init()
    {        
    	wp_register_style( 'cusn_style_sheet', plugins_url( 'css/cusn.css', __FILE__ ) );
        register_setting(
            'cusn-functional-settings', // Option group
            'cusn-functional-settings', // Option name
            array( $this, 'cusn_functional_sanitize' ) // Sanitize
        );
        
        register_setting(
        	'cusn-htaccess-settings',
        	'cusn-htaccess-settings',
        	array( $this, 'cusn_htaccess_sanitize')
        );
        
        add_settings_section(
            'cusn-functional-settings', // ID
            __( 'General and Frontend settings', 'cusn' ), // Title
            array( $this, 'cusn_print_section_info' ), // Callback
            'cusn-functional-settings-page' // Page
        );  
        add_settings_section(
        	'cusn-htaccess-settings',
        	__( 'Settings for .htaccess', 'cusn' ),
        	array( $this, 'cusn_htaccess_info' ),
        	'cusn-htaccess-settings-page'
        );
        
        add_settings_field(
        	'cusn-dummy',
        	__( 'Help', 'cusn' ),
        	array( $this, 'cusn_help_cb' ),
        	'cusn-functional-settings-page',
        	'cusn-functional-settings'
        );
        
		add_settings_field(
			'cusn-remove-settings-on-deactivate',
			__( 'Remove settings on deactivate', 'cusn' ),
			array( $this, 'cusn_checkbox_cb' ),
			'cusn-functional-settings-page',
			'cusn-functional-settings',
			array( 'setting' => 'cusn-remove-settings-on-deactivate', 'value' => "Y" )
		);

		add_settings_field(
			'cusn-remove-backups',
			__( 'Remove backups of .htaccess when plugin is removed', 'cusn' ),
			array( $this, 'cusn_checkbox_cb' ),
			'cusn-functional-settings-page',
			'cusn-functional-settings',
			array( 'setting' => 'cusn-remove-backups', 'value' => "Y" )
		);
		
        add_settings_field(
        	'cusn-content-to-add', // ID of setting
        	__( 'Text to show when unwanted content scraper UA is detected', 'cusn' ), // Text for setting
        	array( $this, 'cusn_text_cb' ), // callback
        	'cusn-functional-settings-page', // page
        	'cusn-functional-settings', // setting section
        	array( 'setting' => 'cusn-content-to-add' ) // arguments
       	);
        	
        add_settings_field(
        	'cusn-show-article',
        	__( 'Show the article after the notice', 'cusn' ),
        	array( $this, 'cusn_checkbox_cb' ),
        	'cusn-functional-settings-page',
        	'cusn-functional-settings',
        	array( 'setting' => 'cusn-show-article', 'value' => "Y" )
        );
        	
        add_settings_field(
        	'cusn-stop-word',
        	__( 'Name of the httpd environment variable for showing the content you set above', 'cusn' ),
        	array( $this, 'cusn_textfield_cb' ),
        	'cusn-htaccess-settings-page',
        	'cusn-htaccess-settings',
			array( 'setting' => 'cusn-stop-word' )
        );
        
        add_settings_field(
        	'cusn-condition-field',
        	__( 'Apache condition when to add environment variable', 'cusn' ),
        	array( $this, 'cusn_textfield_cb' ),
        	'cusn-htaccess-settings-page',
        	'cusn-htaccess-settings',
        	array( 'setting' => 'cusn-condition-field' )
        );
        
        add_settings_field(
        	'cusn-htaccess-content',
        	__( 'Currently known scrapers', 'cusn' ),
        	array( $this, 'cusn_htaccess_cb' ),
        	'cusn-htaccess-settings-page',
        	'cusn-htaccess-settings',
        	array ( 'setting' => 'cusn-htaccess-extra' )
        );
        	      
    }

/*
*
* Sanitation functions
*
*/

    public function cusn_functional_sanitize( $input )
    {
/*
    	error_log( "In " . __FILE__ . " " . __FUNCTION__ );
    	$p=print_r($_POST,true);error_log($p);
*/

        $new_input = array();
        $new_input['cusn-content-to-add'] = '';
        if( is_array( $this->functional_options ) && array_key_exists( 'cusn-content-to-add', $this->functional_options ) ) $new_input['cusn-content-to-add'] = $this->functional_options['cusn-content-to-add'];
        $this->cusn_set_checkboxes( $new_input );
        foreach ( $input as $option_key => $option_value ) {
	        switch ( $option_key ) {
		        case 'cusn-content-to-add' :
		        	if ( strlen( $option_value ) == 0 ) {
			        	$new_input[$option_key] = $option_value;
			        	}
			        else {
			        	$tmp_string = wp_kses_data( $option_value );
			        	$tmp_string = wp_rel_nofollow( $tmp_string );
			        	if ( substr_count( $tmp_string, 'target="_blank"' ) == 0 ) $tmp_string = str_ireplace('<a ', '<a target="_blank" ', $tmp_string );
			        	$new_input[$option_key] = $tmp_string;
		        	}
		        	break;
		        case 'cusn-remove-settings-on-deactivate' :
		        case 'cusn-show-article' :
		        case 'cusn-remove-backups' :
		        	$new_input[$option_key] = "N";
		        	if ( $option_value == "Y" ) $new_input[$option_key] = $option_value;
		        	break;
		        default:
		        	break;
		        	//$new_input[$option_key] = $option_value;   	
	        }
        }
        return $new_input;
    }
    
    public function cusn_htaccess_sanitize( $input ) {
/*
    	error_log( "In " . __FILE__ . " " . __FUNCTION__ );
    	$p=print_r($_POST,true);error_log($p);
*/
	    $new_input = array();
		if ( is_array( $this->htaccess_options ) ) $new_input = $this->htaccess_options; //save current settings
		foreach ( $input as $option_key => $option_value ) {
			switch ( $option_key ) {
				case 'cusn-stop-word' :
					if ( array_key_exists( 'cusn-stop-word-button', $_POST ) ) {
						$tmp_string = strtolower( $option_value );
						$cusn_stop_word = "unwanted";
						if ( $this->cusn_ctype( $tmp_string ) )  {
							$new_input[$option_key] = $tmp_string;
							}
						else {
							$new_input[$option_key] = "unwanted";
							add_settings_error( 'cusn-htaccess-settings', 'invalid-variable', __( 'The value given was invalid. Reverting to default', 'cusn' ), 'error' );
							}
						if ( is_array( $this->htaccess_options ) && array_key_exists( $option_key, $this->htaccess_options ) && $this->htaccess_options[$option_key] <> $new_input[$option_key] ) {
							$cusn_out = false;
							if ( is_array( $this->htaccess_options ) && array_key_exists( 'cusn-to-add', $this->htaccess_options ) ) $cusn_out = $this->htaccess_options['cusn-to-add'];
							if ( $cusn_out )  {
								if ( ! $this->cusn_manipulate_htaccess( $cusn_out, $new_input[$option_key] ) ) add_settings_error( 'cusn-htaccess-error', 'htaccess-file-error', __( 'Couldn\'t read or write to the neccesary files or directories. You .htaccess SHOULD be untouched', 'cusn' ) );
							}
						}
					}
					break;
				case 'cusn-to-add' :
					if ( $option_value == '' ) break; // we do not enter empty values
					if ( ! array_key_exists( 'cusn-add-condition-button', $_POST ) ) break;
						$cusn_suspects = array();
						if ( is_array( $this->htaccess_options ) && array_key_exists( 'cusn-to-add' , $this->htaccess_options ) ) $cusn_suspects = $this->htaccess_options['cusn-to-add'];
						if ( ! array_key_exists( 'cusn-condition-type' , $input ) ) continue;
						$cusn_condition_type = "agent";
						if ( "address" == strtolower( $input['cusn-condition-type'] ) ) $cusn_condition_type = "address";
						$new_val = NULL;
						switch ( $cusn_condition_type ) {
							case "agent" :
								$new_option_value = urlencode( $option_value ); // Use urlencode to avoid sql escapes is string. UA can contain about every character.
								$new_val = "agent&\"" . $new_option_value . "\"";
								break;
							case "address" :
								if ( filter_var( $option_value, FILTER_VALIDATE_IP ) ) { // Valid ip, either ipv6 or ipv4
									$new_val = "address&" . $option_value;
								} else {
									add_settings_error( 'cusn-htaccess-settings', 'invalid-ip', __( 'The address given is not a valid ip adress', 'cusn' ), 'error' );
								}
								break;		
						} // switch
					// cusn-to-add-condition-button
					$cusn_new_val = true;
					if ( count( $cusn_suspects ) > 0 ) {
						foreach ( $cusn_suspects as $a_suspect ) {
							if ( $a_suspect == $new_val ) $cusn_new_val = false;
						}
					}
					if ( $new_val && $cusn_new_val ) $cusn_suspects[] = $new_val;
					if ( $this->cusn_manipulate_htaccess( $cusn_suspects ) )  {
						$new_input[$option_key] = $cusn_suspects;
						}
					else {
						add_settings_error( 'cusn-htaccess-error', 'htaccess-file-error', __( 'Couldn\'t read or write to the neccesary files or directories. You .htaccess SHOULD be untouched', 'cusn' ) );
					}
					break;
				case 'cusn-htaccess-extra' :
					if ( ! ( is_array( $this->htaccess_options ) || ! array_key_exists( 'cusn-to-add', $this->htaccess_options ) ) ) continue;
					$cusn_suspects = $this->htaccess_options['cusn-to-add'];
					array_splice( $cusn_suspects, $option_value, 1 );
					if ( $this->cusn_manipulate_htaccess( $cusn_suspects ) ) $new_input['cusn-to-add'] = $cusn_suspects;
					break;
			}
		}
		return $new_input;	
    }

/*
*
* Section info functions
*
*/

    public function cusn_print_section_info()
    {
        ?><hr /><?php
    }

	public function cusn_htaccess_info() {
		$cusn_home_path = get_home_path();
		$cusn_htaccess_file_path = $cusn_home_path . "/.htaccess";
		$cusn_bu_dir = $cusn_home_path . "/wp-content/cusn-backup";
		$cusn_can_write = true;
		if ( ! is_writable( $cusn_home_path ) OR ! is_writable( $cusn_home_path . "/wp-content/" ) ) $cusn_can_write = false; // We can't write all neccesary files
        $cusn_htaccess_warning = "<hr /><div class=\"cusn-warning\">";
		if ( $cusn_can_write ) {
	        $cusn_htaccess_warning .= __('This will change your .htaccess file. The author has taken every precaution to make sure the plugin doesn\'t interfere with your normal WordPress operation.', 'cusn' );
	        $cusn_htaccess_warning .= "<br />";
	        $cusn_htaccess_warning .= __('However, no guarantee is given. To be safe, the plugin creates a backup in the wp-content/cusn-backup directory in your WordPress root directory', 'cusn' );
		}
		if ( ! $cusn_can_write ) {
			$cusn_htaccess_warning .= __( 'You can\'t write the .htaccess file, and/or the backup files. Saving the options doesn\'t make sense', 'cusn' );
		}
        $cusn_htaccess_warning .= "</div>";
		echo $cusn_htaccess_warning;
	}

/*
*
*	Callbacks Settings
*
*/

	public function cusn_help_cb() {
		$cusn_help_link = "<a href=\"http://tech.planetkips.nl/\">" . __( 'Click for the manual', 'cusn' ) . "</a>";
		echo $cusn_help_link;
	}
	
	
    public function cusn_text_cb( $args ) {
 	    ?>
	    <div class="cusn-textarea">
	    	<!-- cusn text starts here -->
	    	<?php
	    	$text_to_add = "";
	    	$cusn_stop_word = "unwanted";
	    	if ( is_array( $this->functional_options ) && array_key_exists('cusn-content-to-add', $this->functional_options ) ) $text_to_add = stripslashes( $this->functional_options['cusn-content-to-add'] );
	    	if ( is_array( $this->htaccess_options ) && array_key_exists( 'cusn-stop-word', $this->htaccess_options ) ) $cusn_stop_word = $this->htaccess_options['cusn-stop-word'];
	    	?>
	    	<textarea name="cusn-functional-settings[<?php echo $args['setting']; ?>]" class="cusn-textarea"><?php echo $text_to_add; ?></textarea>
	    	<?php if ( 'cusn-content-to-add' == $args['setting'] ) {
		    	?>
		    	<p class="description">
		    	<?php
			    	$cusn_desc = sprintf( __( 'If the server environment variable %s is set for a match (in) with the User-Agent, or an ip address of the remote client, then the notice will be displayed.', 'cusn' ), $cusn_stop_word );
			    	$cusn_desc .= "<br />";
			    	$cusn_desc .= __( 'You can use the following html tags:', 'cusn' ) ."&nbsp;" . allowed_tags() . '<br />';
			    	$cusn_desc .= __( "Defaults to empty string", 'cusn' );
			    	echo $cusn_desc; 
			    ?>		    	
			    </p>
		    	<?php } 
	    	?>
	    	<!-- cusn text stops here -->
	    </div>
	    	<?php
    }
    
    public function cusn_textfield_cb( $args ) {
	    if ( is_array( $args ) && array_key_exists( 'setting', $args ) ) {
		    $cusn_setting = $args['setting'];
	    } else {
		    return;
	    }
	    if ( is_array( $this->htaccess_options ) && array_key_exists('cusn-stop-word', $this->htaccess_options ) ) {
		    $cusn_stop_word = $this->htaccess_options['cusn-stop-word'];   
	    } else {
		    $cusn_stop_word = "unwanted";
	    }
		$cusn_home_path = get_home_path();
		$cusn_htaccess_file_path = $cusn_home_path . "/.htaccess";
		$cusn_bu_dir = $cusn_home_path . "/wp-content/cusn-backup";
		$cusn_can_write = true;
		if ( ! is_writable( $cusn_home_path ) OR ! is_writable( $cusn_home_path . "/wp-content/" ) ) $cusn_can_write = false; // We can't write all neccesary files
	    ?>
	    <div class="cusn-textfield">
		<?php
	    switch ( $cusn_setting ) {
		    case 'cusn-stop-word' :
		    	?>
		    	<input type="text" value="<?php echo $cusn_stop_word; ?>" name="cusn-htaccess-settings[<?php echo $cusn_setting; ?>]" id="cusn-stop-word" />
		    	<input type="submit" class="button cusn-button" value="<?php _e ( 'Set variable name', 'cusn' ); ?>" name="cusn-stop-word-button" />
		    	<p class="description">
		    		<?php
		    		_e( 'Needs to be lower case, and only contain letter from the latin alphabet, ie. a-z. No spaces, no numbers and no other characters', 'cusn' );
		    		echo "<br />";
		    		_e( 'Uppercase is converted to lowercase. If the other constraints aren\'t met, the value will revert to the default ("unwanted")', 'cusn' );
		    		echo "<br />";
		    		_e( 'And I am too lazy to write a javascript check input script.', 'cusn' );
		    		echo "<br />";
		    		_e( "You need to click the 'Set variable name' button to actually change the value", 'cusn' );
		    		?>
		    	</p>
		    	<?php
		    	break;
			case 'cusn-condition-field' :
				?>
		    	<select id="cusn-condition-type" name="cusn-htaccess-settings[cusn-condition-type]">
		    		<option value="Agent"><?php _e( 'User Agent String', 'cusn' ); ?></option>
		    		<option value="Address"><?php _e( 'IP address', 'cusn' ); ?></option>
		    	</select>
		    	<input type="text" name="cusn-htaccess-settings[cusn-to-add]" <?php if ( ! $cusn_can_write ) echo "disabled"; ?> />
		    	<input type="submit" class="button cusn-button" value="<?php _e( 'Add condition', 'cusn' ); ?>" name="cusn-add-condition-button" <?php if ( ! $cusn_can_write ) echo "disabled"; ?> />
		    	<p class="description">
		    	<?php
		    		_e( 'Select a condition type, IP address or User Agent String, and fill in a value.', 'cusn');
		    		echo "<br />";
		    		_e( '- a valid IP address (for the IP address type)', 'cusn' );
		    		echo "<br />";
		    		_e( '- a (part of) the User Agent (browser name) string (for the User Agent String type) This is NOT case sensitive', 'cusn' );
		    		echo "<br />";
		    		_e( 'You need to use the button "Add Condition" to add the value.', 'cusn' );
		    		?>
		    	</p>
		    	<?php
		    	break;
		}
		?>
	    </div>
	    <?php
    }
    
    public function cusn_checkbox_cb( $args ) {
    	if ( ! is_array( $args ) ) return; // $args needs to be an array
    	if ( ! array_key_exists( 'setting' , $args ) ) return; // Need a setting
    	if ( ! array_key_exists( 'value', $args ) ) return; // Also need a value
    	?>
	    <div class="cusn-checkbox">
	    	<!-- cusn-checkbox starts here -->
	    	<input type="checkbox" name="cusn-functional-settings[<?php echo $args['setting']; ?>]" <?php if ( is_array( $this->functional_options ) && array_key_exists( $args['setting'], $this->functional_options ) ) checked( $args['value'], $this->functional_options[$args['setting']] ); ?> value="<?php echo $args['value']; ?>" />
	    	<p class="description cusn-description">
	    	<?php
	    	$cusn_description = '';
	    	$cusn_option = $args['setting'];
	    	switch ( $cusn_option ) {
		    	case 'cusn-remove-settings-on-deactivate' :
		    		$cusn_description .= __( "When deactivating the plugin, remove the plugin settings as well. Defaults to 'No'", 'cusn' );
		    		$cusn_description .= "<br />";
		    		$cusn_description .= __( "The settings will always be removed when uninstalling", 'cusn' );

		    		break;
		    	case 'cusn-remove-backups' :
		    		$cusn_description .= __( "When removing the setting (either through uninstalling, or when 'Remove settings on deactivate' is checked, remove the .htaccess backups as well", 'cusn' );
		    		$cusn_description .= "<br />";
					$cusn_description .= __( "Defaults to 'Yes'", 'cusn' );
		    		break;
		    	case 'cusn-show-article' :
		    		$cusn_description .= __( "Show the post, or page, after the notice. Defaults to 'No' ", 'cusn' );
		    		break;
		    		
	    	}
	    	echo $cusn_description;
	    	?>
	    	</p>
	    	<!-- cusn-checkbox ends here -->
	    </div>
	    <?php 
    }
    
    public function cusn_htaccess_cb( $args ) {
	    $cusn_suspects = array();
	    if ( is_array( $this->htaccess_options ) && array_key_exists( 'cusn-to-add', $this->htaccess_options ) ) $cusn_suspects = $this->htaccess_options['cusn-to-add'];
	    $field_val = '';
	    $add_class = '';
	    $option_count = 0;
		$cusn_home_path = get_home_path();
		$cusn_can_write = true;
		if ( ! is_writable( $cusn_home_path ) OR ! is_writable( $cusn_home_path . "/wp-content/" ) ) $cusn_can_write = false; // We can't write all neccesary files
		$cusn_suspects_exist = false;
		if ( count( $cusn_suspects ) > 0 ) {
			$cusn_suspects_exist = true;
		    foreach ( $cusn_suspects as $suspect ) {
		    	$s=strlen($suspect);
		    	if ( 1 <= $s ) {
				    $a_suspect = str_replace( "&", " ", $suspect );
				    $array_suspect = explode( "&", $suspect );
				    if ( count( $array_suspect ) <> 2 ) continue;
				    $b_suspect = urldecode( $array_suspect[1] );
				    $array_suspect[1] = trim( $b_suspect, "\"");
				    if ( $array_suspect[0] == "agent" ) $field_val .= "<option id=\"$option_count\" value=\"$option_count\">Agent String:&nbsp;&nbsp; $array_suspect[1]</option>";
				    if ( $array_suspect[0] == "address" ) $field_val .= "<option id=\"$option_count\" value=\"$option_count\">IP Address:&nbsp;&nbsp;$array_suspect[1]</option>";
				    $option_count += 1;
			    	}
			    }
			}
		else {
			$field_val = __( 'None', 'cusn' );
		}
			 
	    		 ?>
		 <div class="cusn-textarea">
		 	<?php if ( $cusn_suspects_exist ) {
		 		?>
		 		<select size="4" class="cusn-select-box" name="cusn-htaccess-settings[<?php echo $args['setting']; ?>]" class="cusn-textarea" ><?php echo $field_val; ?></select>
		 		<br /><input type="submit" class="button cusn-button" value="<?php _e( 'Remove selected', 'cusn' ); ?>" name="cusn-remove-entry-button" <?php if ( ! $cusn_can_write ) echo "disabled"; ?> />
		 		<p class="description cusn-descritption">
		 		<?php echo __( "You need to click the button 'Remove Selected' to actually remove the selected item", 'cusn' ); ?>
		 		</p>
			 <?php }
			 else echo $field_val; ?>
		 </div>
		 <?php
	    }
   // }


/*
* Private functions, for data sanitazion
*/
	/*
	*	Sanitize array of checkboxes
	*	Presumtion: all checkboxes have a numeric return value
	*
	*	@param: array with values of checked boxes
	*
	*/

	private function cusn_sanitize_checkbox_array( $cusn_array ) {
		$retval = array();
		if ( ! is_array( $cusn_array ) ) return $retval;
		foreach ( $cusn_array as $cusn_value ) {
			if ( is_numeric( $cusn_value ) ) $retval[] = $cusn_value;
		}
		return $retval;
	}
	
	private function cusn_sanitize_radio_buttons( $allowed_values, $value_to_test ) {
		if ( is_array($allowed_values ) ) {
			$convert_to_upper = false;
			if ( array_key_exists( 'upper', $allowed_values ) ) {
				$convert_to_upper = true;
			}
			if ( array_key_exists('values', $allowed_values ) ) {
				$test_values = $allowed_values['values'];
			}
			else
			{
				exit;
			}
		}
		else {
			$test_values = $allowed_values;
		}
		if ( $convert_to_upper ) $value_to_test = strtoupper( $value_to_test );
		for ( $char = 0; $char == strlen( $test_values ); $char++ ) {
			$test_val = $test_values[$char];
			if ( $convert_to_upper ) $test_val = strtoupper( $test_values[$char] );
			if ( $value_to_test === $test_val ) return $test_val;				
			}
		return false;
		}
	
	private function cusn_manipulate_htaccess( &$htaccess_entries, $endfix = "unwanted" ) {
		$cusn_wp_root = get_home_path();
		if ( ! is_writable( $cusn_wp_root ) OR ! is_writable( $cusn_wp_root . "/wp-content/" ) ) return false;
		//$endfix = "unwanted";
		if ( is_array( $this->htaccess_options ) && array_key_exists( 'cusn-stop-word', $this->htaccess_options ) ) $endfix = $this->htaccess_options['cusn-stop-word'];
		$endfix = " " . $endfix;
		$cusn_backup_dir = $cusn_wp_root . '/wp-content/cusn-backup';
		if ( ! is_dir( $cusn_backup_dir ) && ! is_file( $cusn_backup_dir ) ) {
			$dir_made = mkdir( $cusn_backup_dir );
			if ( ! $dir_made ) return false;
		}
		if ( ! is_writeable( $cusn_backup_dir ) ) return false;
		$max_bu = 50; // make this configurable
		$files = array();
		if ( $cusn_backup_dir_handle = opendir( $cusn_backup_dir ) ) {
			while ( false !== ( $entry = readdir( $cusn_backup_dir_handle ) ) ) {
				if ( 0 === strpos( $entry, 'htaccess' ) ) $files[$entry] = $entry;
			}
		}
		if ( count( $files ) > 0 ) {
			arsort( $files );
			$size = count( $files )  ;
			for ( $count = $size; $count > 0; $count-- ) {
				$suffix = $count;
				if ( $count < 10 ) $suffix = "0" . $suffix;
				if ( array_key_exists( "htaccess." . $suffix , $files ) ) {
					$new_suff = $count + 1;
					if ( $new_suff < 10 ) $new_suff = "0".$new_suff;
					rename( $cusn_backup_dir . "/" . "htaccess." . $suffix, $cusn_backup_dir . "/" . "htaccess." . $new_suff );
					}
				}
			}
		if ( ! copy( $cusn_wp_root . ".htaccess", $cusn_backup_dir . "/htaccess.01" ) ) return false;
		$cusn_htaccess_read = $cusn_wp_root . ".htaccess";
		$cusn_handle = fopen( $cusn_htaccess_read, 'r' );
		if ( $cusn_handle ) {
			$cusn_block_1 = ""; // Up to # BEGIN cusn
			$cusn_block_2 = ""; // All other stuff AFTER # END cusn
			$cusn_my_block = ""; // CUSN Block, all stufffrom and includig # BEGIN cusn until and include #END cusn, if it is there
			$cusn_block_start = false;
			$cusn_block_end = false;
			while( false !== $buffer = fgets( $cusn_handle ) ) { 
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
			fclose( $cusn_handle );
			}
		else return false;
		
		unset( $cusn_handle );
		if ( is_array( $htaccess_entries ) ) {
			$cusn_out_string = '# BEGIN cusn' . "\n";
			$cusn_add_block = false;
			foreach ( $htaccess_entries as $an_entry ) {
				$an_exploded_entry = explode("&", $an_entry );
				
				switch ( $an_exploded_entry[0] ) {
					case 'agent' :
						$prefix = "BrowserMatchNoCase ";
						$betweenfix = urldecode( $an_exploded_entry[1] );
						$cusn_add_block = true;
						break;
					case 'address' :
						$prefix = "SetEnvIf Remote_Addr ";
						$betweenfix = $an_exploded_entry[1];
						$cusn_add_block = true;
						break;
					case 'default' :
						continue;
						break;
					
						}
				$cusn_out_string .= $prefix . $betweenfix . $endfix ."\n";
				}
			$cusn_out_string .= "# END cusn \n";
			if ( $cusn_add_block ) {
				$cusn_out_string = $cusn_block_1 . $cusn_out_string . $cusn_block_2;
				}
			else {
				$cusn_out_string = $cusn_block_1 . $cusn_block_2;
			}
		}
		else {
			$cusn_out_string = $cusn_block_1 . $cusn_block_2;
		}
		$cusn_htaccess_write = $cusn_wp_root . ".tmpaccess";
		$cusn_handle = fopen( $cusn_htaccess_write, 'w' );
		if ( $cusn_handle ) {
			fwrite( $cusn_handle, $cusn_out_string );
			fclose( $cusn_handle );
			unset( $cusn_handle );
		}
		else return false;
		if ( rename( $cusn_htaccess_write, $cusn_htaccess_read ) ) return true;
		return false;
		}
	
// We need to use ctype function, in en_US locale.	
	private function cusn_ctype( $checkstring ) {
		$curr_locale_ctype = setlocale(LC_CTYPE, 0 );
		setlocale( LC_CTYPE, 'en_US' );
		$retval = ctype_alpha( $checkstring );
		setlocale( LC_CTYPE, $curr_locale_ctype );
		return $retval;
	}
	
	private function cusn_set_checkboxes( &$cusn_options ) {
		$cusn_options['cusn-remove-settings-on-deactivate'] = "N";
		$cusn_options['cusn-show-article'] = "N";
		$cusn_options['cusn-remove-backups'] = "N";
	}
		
	
}

require_once( ABSPATH . WPINC . '/pluggable.php' );
if( is_admin() && current_user_can( 'manage_options' ) )
    $my_settings_page = new ContentUnwantedScraper();