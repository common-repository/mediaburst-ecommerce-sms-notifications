<?php
class Clockwork_WPeCommerce_Plugin extends Clockwork_Plugin {

  protected $plugin_name = 'WP e-Commerce';
  protected $language_string = 'clockwork_wpecommerce';
  protected $prefix = 'clockwork_wpecommerce';
  protected $folder = '';
  
  protected $statuses = array();
  
  /**
   * Constructor: setup callbacks and plugin-specific options
   *
   * @author James Inman
   */
  public function __construct() {
    parent::__construct();
    
    // Set the plugin's Clockwork SMS menu to load the contact forms
    $this->plugin_callback = array( $this, 'clockwork_wpecommerce' );
    $this->plugin_dir = basename( dirname( __FILE__ ) );
    
		global $wpsc_purchlog_statuses;
		$this->statuses = $wpsc_purchlog_statuses;
    
    // WP e-Commerce needs this in the constructor
		add_action( 'wpsc_submit_checkout', array( $this, 'admin_new_order_notification' ), 10 );
    add_action( 'wpsc_edit_order_status', array( $this, 'order_status_change_notification' ), 10 );
  }
  
  /**
   * Setup the admin navigation
   *
   * @return void
   * @author James Inman
   */
  public function setup_admin_navigation() {
    parent::setup_admin_navigation();
  }
  
  /**
  * Register settings and callbacks for this plugin
  *
  * @return void
  * @author James Inman
   */
  public function setup_admin_init() {
    parent::setup_admin_init();
    
    $this->convert_old_settings();
    
    // Register admin SMS settings
    register_setting( 'clockwork_wpecommerce_admin_sms', 'clockwork_wpecommerce_admin_sms', array( $this, 'admin_sms_options_validate' ) );
    add_settings_section( 'clockwork_wpecommerce_admin_sms', 'Admin SMS Notifications', array( $this, 'admin_settings_text' ), 'clockwork_wpecommerce_admin_sms' );
    add_settings_field( 'enabled', 'Enabled', array( $this, 'admin_enabled_input' ), 'clockwork_wpecommerce_admin_sms', 'clockwork_wpecommerce_admin_sms' );
    add_settings_field( 'mobile', 'Mobile Number', array( $this, 'admin_mobile_number_input' ), 'clockwork_wpecommerce_admin_sms', 'clockwork_wpecommerce_admin_sms' );
    add_settings_field( 'message', 'Message', array( $this, 'admin_message_input' ), 'clockwork_wpecommerce_admin_sms', 'clockwork_wpecommerce_admin_sms' );
    
    // Register customer SMS settings
    register_setting( 'clockwork_wpecommerce_customer_sms', 'clockwork_wpecommerce_customer_sms', array( $this, 'admin_sms_options_validate' ) );
    add_settings_section( 'clockwork_wpecommerce_customer_sms', 'Customer SMS Notifications', array( $this, 'customer_settings_text' ), 'clockwork_wpecommerce_customer_sms' );
    add_settings_field( 'enabled', 'Send notifications on these statuses', array( $this, 'customer_enabled_input' ), 'clockwork_wpecommerce_customer_sms', 'clockwork_wpecommerce_customer_sms' );

    foreach( $this->statuses as $key => $status ) {
      add_settings_field( str_replace( '-', '', $status['internalname'] ) . "_message", $status['label'] . ' Message', array( $this, 'customer_' . str_replace( '-', '', $status['internalname'] ) . '_message' ), 'clockwork_wpecommerce_customer_sms', 'clockwork_wpecommerce_customer_sms' );
    }
  }
  
  /**
   * Convert settings from v1.x of the plugin
   *
   * @return void
   * @author James Inman
   */
  public function convert_old_settings() {
    // Fix for store names that are too long
    $main_data = get_option( 'clockwork_wpecommerce' );
    $main_data['from'] = substr( $main_data['from'], 0, 11 );
    update_option( 'clockwork_wpecommerce', $main_data );
    
    $options_to_delete = array( 'mbesms-version', 'mbesms-wpsc-version' );
    
    $old_options = get_option( 'mbesms-wpsc' );
    if( !is_array( $old_options ) ) {
      return;
    }
    
    // Admin settings
    if( $old_options['send_admin_message'] == '1' ) {
      $admin_data['enabled'] = true;
    } else {
      $admin_data['enabled'] = false;
    }
    
    $admin_data['mobile'] = $old_options['admin_number'];
    $admin_data['message'] = $old_options['admin_message'];
    
    if( empty( $admin_data['message'] ) ) {
      $admin_data['message'] = 'You have a new order at %shop_name% for %total_price%!';
    }
    
    update_option( 'clockwork_wpecommerce_admin_sms', $admin_data );
    
    // Customer settings
    $customer_data = array();
    foreach( $this->statuses as $status ) {
      $customer_data[ $status['internalname'] . '_message'] = str_replace( '%order_status%', $status['label'], $old_options['status_change'] );
    };
    
    update_option( 'clockwork_wpecommerce_customer_sms', $customer_data );
    
    $options_to_delete[] = 'mbesms-wpsc';
    
    foreach( $options_to_delete as $option ) {
      delete_option( $option );
    }    
  }
  
  /**
   * Setup HTML for the admin <head>
   *
   * @return void
   * @author James Inman
   */
  public function setup_admin_head() {
    echo '<link rel="stylesheet" type="text/css" href="' . plugins_url( 'css/clockwork.css', __FILE__ ) . '">';
  }
  
  /**
   * Function to provide a callback for the main plugin action page
   *
   * @return void
   * @author James Inman
   */
  public function clockwork_wpecommerce() {
    $this->render_template( 'form-options' );    
  }

  /**
   * Check if username and password have been entered
   *
   * @return void
   * @author James Inman
   */
  public function get_existing_username_and_password() {
    $options = get_settings( 'mbesms' );
    
    if( is_array( $options ) && isset( $options['username'] ) && isset( $options['password'] ) ) {
      return array( 'username' => $options['username'], 'password' => $options['password'] );      
    }
    
    return false;
  }
  
  /**
   * Main text for the admin settings
   *
   * @return void
   * @author James Inman
   */
  public function admin_settings_text() {
    echo '<p>You can choose to send a nominated mobile phone an SMS notification whenever a new order is placed on your store.</p>';
  }
  
  /**
   * Main text for the customer settings
   *
   * @return void
   * @author James Inman
   */
  public function customer_settings_text() {
    echo __( 'The following tags can be used in your SMS messages, though please bear in mind that they may take you over your character limits (for example if your shop name is very long): <kbd>%purchase_id%</kbd>, <kbd>%shop_name%</kbd>, <kbd>%total_price%</kbd>', 'woocommercesms' );
  }
  
  /**
   * Input box for the mobile number
   *
   * @return void
   * @author James Inman
   */
  public function admin_mobile_number_input() {
    $options = get_option( 'clockwork_wpecommerce_admin_sms' );
    if( isset( $options['mobile'] ) ) {
      echo '<input id="clockwork_wpecommerce_admin_sms_admin_mobile" name="clockwork_wpecommerce_admin_sms[mobile]" size="40" type="text" value="' . $options['mobile'] . '" />';
    } else {
      echo '<input id="clockwork_wpecommerce_admin_sms_admin_mobile" name="clockwork_wpecommerce_admin_sms[mobile]" size="40" type="text" value="" />';    
    }
		echo ' <p class="description">' . __('International format, starting with a country code e.g. 447123456789. You can enter multiple mobile numbers seperated by a comma.', 'clockwork_wpecommerce') . '</p>';
  }
  
  /**
   * Whether admin settings are enabled
   *
   * @return void
   * @author James Inman
   */
  public function admin_enabled_input() {
    $options = get_option( 'clockwork_wpecommerce_admin_sms' );
    if( isset( $options['enabled'] ) && ( $options['enabled'] == true ) ) {
      echo '<input id="clockwork_wpecommerce_admin_sms_enabled" name="clockwork_wpecommerce_admin_sms[enabled]" type="checkbox" checked="checked" value="1" />';
    } else {
      echo '<input id="clockwork_wpecommerce_admin_sms_enabled" name="clockwork_wpecommerce_admin_sms[enabled]" type="checkbox" value="1" />';    
    }
		echo ' <p class="description">' . __('If this option is checked, your nominated mobile number will be sent a new SMS when a new order is placed.', 'clockwork_wpecommerce') . '</p>';
  }
  
  /**
   * Input box for the mobile number
   *
   * @return void
   * @author James Inman
   */
  public function customer_enabled_input() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );
    foreach( $this->statuses as $key => $status ) {
      if( isset( $options[ $status['internalname'] . '_enabled' ] ) && $options[ $status['internalname'] . '_enabled' ] == '1' ) {
        echo '<label><input id="clockwork_wpecommerce_customer_sms_' . $status['internalname'] . '" name="clockwork_wpecommerce_customer_sms[' . $status['internalname'] . '_enabled]" type="checkbox" checked="checked" value="1" />&nbsp;&nbsp;&nbsp;' . $status['label'] . '</label><br />';        
      } else {
        echo '<label><input id="clockwork_wpecommerce_customer_sms_' . $status['internalname'] . '" name="clockwork_wpecommerce_customer_sms[' . $status['internalname'] . '_enabled]" type="checkbox" value="1" />&nbsp;&nbsp;&nbsp;' . $status['label'] . '</label><br />';              
      }
    }
  }
    
  /**
   * Validation for main SMS options
   *
   * @param string $val 
   * @return void
   * @author James Inman
   */
  public function main_options_validate( $val ) {
    // 11 characters for 'from'
    $val['from'] = substr( $val['from'], 0, 11 );    
    return $val;
  }
  
  /**
   * Validation for admin SMS options
   *
   * @param string $val 
   * @return void
   * @author James Inman
   */
  public function admin_sms_options_validate( $val ) {
    // First, switch enabled
    if( !isset( $val['enabled'] ) || !$val['enabled'] || empty( $val['enabled'] ) ) {
      $val['enabled'] = false;
    } else {
      $val['enabled'] = true;
    }
    
    // Then, check mobile number
    if( $val['enabled'] == true && !Clockwork::is_valid_msisdn( $val['mobile'] ) ) {
      add_settings_error( 'clockwork_options', 'clockwork_options', 'You must enter a valid mobile number in international MSISDN format, starting with a country code, e.g. 447123456789.', 'error' );
      $val['mobile'] = '';
    }
    
    return $val;
  }
  
  /**
   * Form field for the message to send to administrators
   *
   * @return void
   * @author James Inman
   */
  public function admin_message_input() {
    $options = get_option( 'clockwork_wpecommerce_admin_sms' );

    if( isset( $options['message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_admin_sms_message" name="clockwork_wpecommerce_admin_sms[message]" rows="3" cols="45">' . $options['message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_admin_sms_message" name="clockwork_wpecommerce_admin_sms[message]" rows="3" cols="45"></textarea>';  
    }
    echo ' <p class="description">' . __( 'The following tags can be used in your SMS messages, though please bear in mind that they may take you over your character limits (for example if your shop name is very long): <kbd>%purchase_id%</kbd>, <kbd>%shop_name%</kbd>, <kbd>%total_price%</kbd>', 'woocommercesms' ) . '</p>';
  }
  
  /**
   * Send an admin notification on new orders being placed
   *
   * @param string $log_id
   * @return void
   * @author James Inman (based on code by Simon Wheatley)
   */
  public function admin_new_order_notification( $log_id ) {
    $options = array_merge( get_option( 'clockwork_options' ), get_option( 'clockwork_wpecommerce' ), get_option( 'clockwork_wpecommerce_admin_sms' ) );
    
    if( $options['enabled'] == true ) {      
      // Get purchase details    
  		global $wpdb;
  		$sql = " SELECT * FROM {$wpdb->prefix}wpsc_purchase_logs WHERE id = %d ";
  		$purchase_data = array( 'purchlog_id' => $log_id );
  		$purchase_data[ 'purchlog_data' ] = $wpdb->get_row( $wpdb->prepare( $sql, $log_id ), ARRAY_A );
      
      // Setup the message
  		$currency_args = array( 'display_as_html' => false, 'display_currency_symbol' => true );
  		$search = array( '%purchase_id%', '%shop_name%', '%total_price%' );
  		$replace = array( $log_id['purchase_log_id'], $options['from'], wpsc_currency_display( $purchase_data[ 'purchlog_data' ][ 'totalprice' ], $currency_args ) );
      $message = str_replace( $search, $replace, $options['message'] );      
      $mobile = explode( ',', $options['mobile'] );
      
      // Send the message
      try {
        $clockwork = new WordPressClockwork( $options['api_key'] );
        $messages = array();
        foreach( $mobile as $to ) {
          $messages[] = array( 'from' => $options['from'], 'to' => $to, 'message' => $message );          
        }
        $result = $clockwork->send( $messages );
      } catch( ClockworkException $e ) {
        $result = "Error: " . $e->getMessage();
      } catch( Exception $e ) { 
        $result = "Error: " . $e->getMessage();
      }
    }
  }
  
	/**
	 * Return a checkout field, identified by a unique_name, from a particular purchase.
	 *
	 * @param int $log_id The ID for the purchase log we want the field for 
	 * @return string The field value, as entered by the customer
   * @author Simon Wheatley
	 **/
	protected function get_checkout_field( $log_id, $unique_name ) {
		global $wpdb;
		$checkout_forms = WPSC_TABLE_CHECKOUT_FORMS;
		$submitted_form_data = WPSC_TABLE_SUBMITED_FORM_DATA;
		$sql = " SELECT value FROM $submitted_form_data WHERE log_id = %d AND form_id IN ( SELECT id FROM $checkout_forms WHERE unique_name = %s ) ";
		return $wpdb->get_var( $wpdb->prepare( $sql, $log_id, $unique_name ) );
	}
  
  /**
   * Send the customer a notification when the order status changes
   *
   * @param string $purchase_data Purchase data
   * @return void
   * @author James Inman
   */
  public function order_status_change_notification( $purchase_data ) {
    $options = array_merge( get_option( 'clockwork_options' ), get_option( 'clockwork_wpecommerce' ), get_option( 'clockwork_wpecommerce_customer_sms' ) );
    
    $log_id = (int) $purchase_data[ 'purchlog_id' ];
		$currency_args = array( 'display_as_html' => false, 'display_currency_symbol' => true );
    
    $status_label = $this->get_purchase_status( $purchase_data[ 'new_status' ] );
    $status = $this->get_status_internalname( $status_label );
            
    // Don't send order status change notification if the option's not set
    if( !isset( $options[ str_replace( '-', '', $status ) . '_enabled' ] ) ) {
      return;
    }
    
    $message = $options[ str_replace( '-', '', $status ) . '_message' ];
    $message = str_replace( '%shop_name%', $options['from'], $message );
    $message = str_replace( '%purchase_id%', $log_id, $message );
    $message = str_replace( '%total_price%', wpsc_currency_display( $purchase_data[ 'purchlog_data' ][ 'totalprice' ], $currency_args ), $message );  
    $message = str_replace( '%order_status%', $this->get_purchase_status( $purchase_data[ 'new_status' ] ), $message );
    
    $mobile = $this->get_checkout_field( $log_id, 'billingphone' );
    $country = $this->get_checkout_field( $log_id, 'billingcountry' );
    $mobile = $this->format_mobile_number( $mobile, $country );
    
    try {
      $clockwork = new WordPressClockwork( $options['api_key'] );
      $messages = array();
      $messages[] = array( 'from' => $options['from'], 'to' => $mobile, 'message' => $message );
      $result = $clockwork->send( $messages );
    } catch( ClockworkException $e ) {
      $result = "Error: " . $e->getMessage();
    } catch( Exception $e ) { 
      $result = "Error: " . $e->getMessage();
    }
  }
  
	/**
	 * Return the Purchase Status name when passed an order number.
	 *
	 * @param int $order The order value for this status 
	 * @return void
   * @author Simon Wheatley
	 **/
	protected function get_purchase_status( $order ) {
		global $wpsc_purchlog_statuses;
		foreach ( $wpsc_purchlog_statuses as $status ) {
			if ( $order == $status[ 'order' ] ) {
				return $status[ 'label' ];
      }
		}
	}
  
  /**
   * Get the internalname of a status from the label
   *
   * @param string $label 
   * @return void
   * @author James Inman
   */
  protected function get_status_internalname( $label ) {
    foreach( $this->statuses as $status ) {
      if( $status['label'] == $label ) {
        return str_replace( '-', '', $status['internalname'] );
      }
    }
  }
  
  /**
   * Text for general settings
   *
   * @return void
   * @author James Inman
   */
  public function general_settings_text() {
    echo '<p>General settings that apply to all notifications sent from your store.</p>';
  }
  
  /**
   * Input for the store name
   *
   * @return void
   * @author James Inman
   */
  public function store_name_input() {
    $options = get_option( 'clockwork_wpecommerce' );

    if( isset( $options['from'] ) ) {
      echo '<input type="text" id="clockwork_wpecommerce_from" name="clockwork_wpecommerce[from]" size="40" value="' . $options['from'] . '" />';
    } else {
      echo '<input type="text" id="clockwork_wpecommerce_from" name="clockwork_wpecommerce[from]" size="40" value="" />';  
    }
    echo ' <p class="description">' . __( 'The name of your store, replaced from <kbd>%shop_name%</kbd> in messages.', 'woocommercesms' ) . '</p>';
  }
  
  /**
   * Form field for the message to send to customers on incomplete sale status
   *
   * @return void
   * @author James Inman
   */
  public function customer_incomplete_sale_message() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );

    if( isset( $options['incomplete_sale_message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_incomplete_sale_message" name="clockwork_wpecommerce_customer_sms[incomplete_sale_message]" rows="3" cols="45">' . $options['incomplete_sale_message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_incomplete_sale_message" name="clockwork_wpecommerce_customer_sms[incomplete_sale_message]" rows="3" cols="45"></textarea>';  
    }    
  }

  /**
   * Form field for the message to send to customers on order received status
   *
   * @return void
   * @author James Inman
   */
  public function customer_order_received_message() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );

    if( isset( $options['order_received_message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_order_received_message" name="clockwork_wpecommerce_customer_sms[order_received_message]" rows="3" cols="45">' . $options['order_received_message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_order_received_message" name="clockwork_wpecommerce_customer_sms[order_received_message]" rows="3" cols="45"></textarea>';  
    }    
  }

  /**
   * Form field for the message to send to customers on accepted payment status
   *
   * @return void
   * @author James Inman
   */
  public function customer_accepted_payment_message() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );

    if( isset( $options['accepted_payment_message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_customer_accepted_payment_message" name="clockwork_wpecommerce_customer_sms[accepted_payment_message]" rows="3" cols="45">' . $options['accepted_payment_message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_customer_accepted_payment_message" name="clockwork_wpecommerce_customer_sms[accepted_payment_message]" rows="3" cols="45"></textarea>'; 
    }        
  }

  /**
   * Form field for the message to send to customers on job dispatched status
   *
   * @return void
   * @author James Inman
   */
  public function customer_job_dispatched_message() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );

    if( isset( $options['job_dispatched_message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_job_dispatched_message" name="clockwork_wpecommerce_customer_sms[job_dispatched_message]" rows="3" cols="45">' . $options['job_dispatched_message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_job_dispatched_message" name="clockwork_wpecommerce_customer_sms[job_dispatched_message]" rows="3" cols="45"></textarea>'; 
    }            
  }

  /**
   * Form field for the message to send to customers on cancelled status
   *
   * @return void
   * @author James Inman
   */
  public function customer_closed_order_message() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );

    if( isset( $options['closed_order_message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_closed_order_message" name="clockwork_wpecommerce_customer_sms[closed_order_message]" rows="3" cols="45">' . $options['closed_order_message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_closed_order_message" name="clockwork_wpecommerce_customer_sms[closed_order_message]" rows="3" cols="45"></textarea>'; 
    }        
  }

  /**
   * Form field for the message to send to customers on declined payment status
   *
   * @return void
   * @author James Inman
   */
  public function customer_declined_payment_message() {
    $options = get_option( 'clockwork_wpecommerce_customer_sms' );

    if( isset( $options['declined_payment_message'] ) ) {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_declined_payment_message" name="clockwork_wpecommerce_customer_sms[declined_payment_message]" rows="3" cols="45">' . $options['declined_payment_message'] . '</textarea>';
    } else {
      echo '<textarea id="clockwork_wpecommerce_customer_sms_declined_payment_message" name="clockwork_wpecommerce_customer_sms[declined_payment_message]" rows="3" cols="45"></textarea>'; 
    }        
  }

  /**
   * Takes a mobile number and a country and makes it all nice
   * and ready for the API:
   * * Remove leading '0'
   * * Detect or add country code
   * * Strip any weird characters and spaces
   *
   * @param string $mobile_number The mobile number to fixup
   * @param string $country_isocode The two letter ISO country code to get the dialling prefix for
   * @return string A formatted mobile phone number
   * @author Simon Wheatley
   **/
  protected function format_mobile_number( $mobile_number, $country_isocode ) {
  	$seq = $mobile_number;

  	// First remove any whitespace
  	$start = $mobile_number;
  	$mobile_number = preg_replace( '/\s/', '', $mobile_number );

  	$country_dial = WordPressClockwork::$country_codes[$country_isocode];

  	// Attempt to detect a country prefix by looking for:
  	// * "+" at the start of the mobile number
  	// * Any digits preceding a parentheses, e.g. "44(0)797…"
  	if ( '+' != substr( $mobile_number, 0, 1 ) && ! preg_match( '/^\d+\(/', $mobile_number ) ) {
  		// Strip any leading zero
  		$mobile_number = preg_replace( '/^0/', '', $mobile_number );	

  		// No country code detected, so add one
  		if ( $country_dial != substr( $mobile_number, 0, strlen( $country_dial ) ) ) {
  			// The number doesn't start with the country code, so add it now
  			$mobile_number = $country_dial . $mobile_number;
  		}

  	}

  	// Remove parentheses and anything betwixt them
  	$mobile_number = preg_replace( '/\(\d+\)/', '', $mobile_number );

  	// Remove anything that isn't a number
  	$mobile_number = preg_replace( '/[^0-9]/', '', $mobile_number );

  	// The number starts with the expected country code, remove any zero which 
  	// immediately follows the country code.
  	if ( $country_dial == substr( $mobile_number, 0, strlen( $country_dial ) ) ) {
  		$mobile_number = preg_replace( "/^{$country_dial}(\s*)?0/", $country_dial, $mobile_number );
    }

  	return $mobile_number;
  }

  
}

$cp = new Clockwork_WPeCommerce_Plugin();