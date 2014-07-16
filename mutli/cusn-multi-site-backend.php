<?php
defined( 'ABSPATH' ) OR exit;
class ContentUnwantedScraper
{
	
    private $functional_options;    
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        $this->functional_options = get_option( 'cusn-functional-settings' );
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
        </div>
        <?php
    }

    public function page_init()
    {        
    	wp_register_style( 'cusn_style_sheet', plugins_url( 'css/cusn.css',dirname( __FILE__ ) ) );
        register_setting(
            'cusn-functional-settings', // Option group
            'cusn-functional-settings', // Option name
            array( $this, 'cusn_functional_sanitize' ) // Sanitize
        );
                
        add_settings_section(
            'cusn-functional-settings', // ID
            __( 'General and Frontend settings', 'cusn' ), // Title
            array( $this, 'cusn_print_section_info' ), // Callback
            'cusn-functional-settings-page' // Page
        );  
        
        add_settings_field(
        	'cusn-dummy',
        	__( 'Help', 'cusn' ),
        	array( $this, 'cusn_help_cb' ),
        	'cusn-functional-settings-page',
        	'cusn-functional-settings'
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
 
    }

/*
*
* Sanitation functions
*
*/

    public function cusn_functional_sanitize( $input )
    {
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
	

/*
*
* Section info functions
*
*/

    public function cusn_print_section_info()
    {
        ?><hr /><?php
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
	

// We need to use ctype function, in en_US locale.	
	private function cusn_ctype( $checkstring ) {
		$curr_locale_ctype = setlocale(LC_CTYPE, 0 );
		setlocale( LC_CTYPE, 'en_US' );
		$retval = ctype_alpha( $checkstring );
		setlocale( LC_CTYPE, $curr_locale_ctype );
		return $retval;
	}
	
	private function cusn_set_checkboxes( &$cusn_options ) {
		$cusn_options['cusn-show-article'] = "N";
	}
		
	
}
