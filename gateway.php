<?php
/*
 * Plugin Name: WooCommerce Eugen Dahone Payment Gateway
 * Plugin URI:https://github.com/lewatt23/my-dahone-woocommerce
 * Description: Simple plugin to add support for my-dahone payment solution to woocomerce
 * Author:Mfou'ou medjo stanly
 * Author URI: 
 * Version: 1.0.1
 *
 
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'eugen_add_gateway_class' );
function eugen_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Eugen_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'eugen_init_gateway_class' );
function eugen_init_gateway_class() {
 
	class WC_Eugen_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
             $this->domain = 'Payement Mtn ou orange';

            $this->id = 'eugen'; // payment gateway plugin ID
            $this->icon = 'https://b-eugen.com/wp-content/uploads/2019/06/mobile-money.jpg'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Eugen Gateway';
            $this->method_description = 'Description of eugen payment gateway'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->private_key = $this->get_option( 'private_key' );
            $this->shop_name= $this->get_option( 'shop_name' );
            $this->website_url= $this->get_option( 'website_url' );
     
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 

            $this->form_fields = array(
               'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Custom Payment', $this->domain ),
                    'default' => 'yes'
                ),   
                'title' => array(
                   'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'payez avec orange et mtn money', $this->domain ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Payer avec Orange ou Mtn.',
                ),
             
                'shop_name' => array(
                    'title'       => 'Shop name',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Dahone Api key',
                    'type'        => 'password'
                ),
                'website_url' => array(
                    'title'       => 'Website url',
                    'type'        => 'text'
                ),
            );
 
	 	}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
            
            global $woocommerce;

            $order_id = $woocommerce->session->order_awaiting_payment;

            $order = wc_get_order( $order_id );

 

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            ?>
            <form id="epg_payment_call" action="https://www.my-dohone.com/dohone/pay" method="post">
                <input type="hidden" name="cmd" value="start">
                <input type="hidden" name="rH" value="<?php echo $this->private_key ?>">
                <div class="form-group">
                    <input class="form-control"  type="text" name="rT" value="" placeholder="Numéro Téléphone">
                </div>
                <div class="form-group">
                  <select class="form-control" name="rOnly">
                      <option value="1">Mtn</option>
                      <option value="2">Orange</option>
                      <option value="3">Express Union</option>
                    </select>
                </div>


                <input type='hidden' name="rMt"  value='<?php echo esc_attr( $woocommerce->cart->total ); ?>' />
       
                <input type="hidden" name="rDvs" value="XAF">
                <input type="hidden" name="rLocale" value="fr">
                <input type="hidden" name="motif" value="paiement d’un article sur la plateforme eugen">
                <input type="hidden" name="endPage" value="<?php echo esc_url( home_url( '/order-received' ) ); ?>">
                <input type="hidden" name="notifyPage" value="<?php echo esc_url( home_url('/commande/')); ?>">
                <input type="hidden" name="cancelPage" value="<?php echo esc_url( home_url('/commande/')); ?>">
                <input type="hidden" name="logo" value="https://b-eugen.com/wp-content/themes/eugen/img/logo.png">
                
                <input class="btn button-eugen" type="submit" value="Payer votre commande">
            </form>
            <?php
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {
 
	
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
	
 
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
 
         global $woocommerce;
            $order = wc_get_order( $order_id );

            // Mark as on-hold (we're awaiting the payment)
            $order->update_status( 'on-hold', __( 'En attente de paiement hors ligne', 'wc-gateway-offline' ) );

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
 
	 	}
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
 
 
	 	}
 	}
}
