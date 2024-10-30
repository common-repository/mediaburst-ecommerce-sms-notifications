<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
  <div class="left-content">

    <div class="icon32"><img src="<?php echo plugins_url( 'images/logo_32px_32px.png', dirname( __FILE__ ) ); ?>" /></div>
    <h2>WP e-Commerce SMS Options</h2>

    <?php $errors = get_settings_errors(); ?>
    <?php if( isset( $errors ) ) { ?>
      <?php foreach( $errors as $e ) { ?>
      <div id="message" class="<?php print $e['type']; ?>">
        <p><?php _e( $e['message'] ) ?></p>
      </div>
      <?php } ?>
    <?php } ?>

    <form method="post" action="options.php">
		<?php settings_fields('clockwork_wpecommerce_admin_sms'); ?>
		<?php do_settings_sections('clockwork_wpecommerce_admin_sms'); ?>
    <?php settings_errors('clockwork_wpecommerce_admin_sms'); ?>
    <?php submit_button(); ?>
    </form>

    <form method="post" action="options.php">
		<?php settings_fields('clockwork_wpecommerce_customer_sms'); ?>
		<?php do_settings_sections('clockwork_wpecommerce_customer_sms'); ?>
    <?php settings_errors('clockwork_wpecommerce_customer_sms'); ?>
    <?php submit_button(); ?>
    </form>

  </div>
</div>
