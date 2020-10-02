<?php
if ( !class_exists('WooApiList') )
{
	class WooApiList
	{
		static $instance;
		public $product_obj;
		public $order_obj;
		var $box_id;
		var $notices = false;
		var $notices_key = false;
		var $product_detail;
		var $woo_api_key;
		var $err_message = '<h3>Please enter Woo API key in the section Woo Key.</h3>';
		public function __construct()
		{
			global $wpdb;
			$this->wpdb = $wpdb;
			$this->woo_api_key = get_option('woo_api_key');
			add_filter( 'set-screen-option', array( $this, 'woo_set_screen' ), 10, 3 );
			add_action( 'admin_menu', array( $this, 'woo_add_plugin_page' ) );
			add_action( 'init',array( $this, 'admin_init_func') );
			add_action( 'admin_notices', array( $this, 'admin_notices_func') );
		}
		public function admin_notices_func()
		{
			if( $this->notices === true ){
			?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Product Updated Successfully!', 'sample-text-domain' ); ?></p>
            </div>
            <?php }else if( $this->notices_key === true ) { ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Key Updated Successfully!', 'sample-text-domain' ); ?></p>
            </div>
            <?php }
		}
		public function admin_init_func()
		{
			if( isset( $_POST['product_nonce_field']) || wp_verify_nonce( $_POST['product_nonce_field'], 'product_action' )) 
			{
			    if( isset($_GET['product_id']) && !empty($_GET['product_id']) )
				{
					$product_id = $_GET['product_id'];
					$product_name = $_POST['product_name'];
					$response = update_product_using_api( $product_id , $product_name );
					if( $response == 'OK' )
					{
						$this->notices = true;
					}
				}
			}
			if( isset( $_POST['woo_key_nonce_field']) || wp_verify_nonce( $_POST['woo_key_nonce_field'], 'woo_key_action' )) 
			{
			    if( isset($_POST['consumer_key']) && isset($_POST['consumer_secret']) )
				{
					$consumer_key = $_POST['consumer_key'];
					$consumer_secret = $_POST['consumer_secret'];
					$option_array = array('consumer_key' => $consumer_key,'consumer_secret' => $consumer_secret);
					$response = update_option( 'woo_api_key', $option_array );
					if( $response )
					{
						$this->notices_key = true;
					}
				}
			}
			if( isset($_GET['action']) && $_GET['action'] == 'product_edit' && isset($_GET['product_id']) && !empty($_GET['product_id']) )
			{
				$product_id = $_GET['product_id'];
				$get_product = get_product_using_api('products/'.$product_id,false);
				$this->product_detail = $get_product;
			}
		}
		public function woo_set_screen( $status, $option, $value )
		{
			return $value;
			$option = 'per_page';
			$args   = [
				'label'   => 'Box',
				'default' => 20,
				'option'  => 'box_per_page'
			];
			add_screen_option( $option, $args );
			$this->product_obj = new BoxListClass();
		}
		public function woo_add_plugin_page()
		{
			$hook = add_menu_page( 'Woo API', 'Woo API', 'manage_options', 'woo_api',[ $this,'woo_api_page_callback' ]);
			$hook_sub = add_submenu_page( 'woo_api', 'Woo Order', 'Woo Order','manage_options', 'woo_api_order',[ $this,'woo_order_page_callback' ]);
			$hook_setting = add_submenu_page( 'woo_api', 'Woo Key', 'Woo Key','manage_options', 'woo_api_key',[ $this,'woo_api_key_callback' ]);
			add_action( "load-$hook", [ $this, 'screen_option' ] );
			add_action( "load-$hook_sub", [ $this, 'screen_option_sub' ] );
		}
		public function screen_option() 
		{
			$option = 'per_page';
			$args   = [
				'label'   => 'Product',
				'default' => 20,
				'option'  => 'member_per_page'
			];
			//add_screen_option( $option, $args );
			$this->product_obj = new WooApiClass();
		}
		public function screen_option_sub() 
		{
			$option = 'per_page';
			$args   = [
				'label'   => 'Product',
				'default' => 20,
				'option'  => 'member_per_page'
			];
			//add_screen_option( $option, $args );
			$this->order_obj = new WooOrderListClass();
		}
		public function woo_api_page_callback()
		{
			if( isset($_GET['action']) && $_GET['action'] == 'product_edit' && isset($_GET['product_id']) && !empty($_GET['product_id']) )
			{
				if( empty($this->woo_api_key) )
				{
					echo $this->err_message;
				}else
				{
					$this->render_product_edit_view();
				}				
			}else
			{
				if( empty($this->woo_api_key) )
				{
					echo $this->err_message;
				}else
				{
					$this->render_product_listing_view();
				}				
			}
		}
		public function render_product_listing_view()
		{
			?>
            <div class="wrap">
			<h2>Product Listing</h2>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2" style="margin: 0;">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->product_obj->prepare_items();
								$this->product_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
            <?php
		}
		public function woo_api_key_callback()
		{
			$woo_api_key = get_option('woo_api_key');
			?>
            <div class="wrap">
                <h2>Woo Key Setting</h2>
                <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2" style="margin: 0;">
                    <form action="" method="post">
                    <table class="form-table">
                    <?php wp_nonce_field( 'woo_key_action', 'woo_key_nonce_field' ); ?>
                    <tr>
                        <th scope="row">Consumer key</th>
                        <td><input type="text" name="consumer_key" value="<?php echo $woo_api_key['consumer_key'];?>" /></td>
                    </tr><br />
                    <tr>
                        <th scope="row">Consumer secret</th>
                        <td><input type="text" name="consumer_secret" value="<?php echo $woo_api_key['consumer_secret'];?>" /></td>
                    </tr>
                    <tr><td><?php submit_button( __( 'Submit', 'textdomain' ), 'button-primary' ); ?></td></tr>
                    </table>
                    </form>
                    </div>
                    </div>
            </div>
            <?php
		}
		public function render_product_edit_view()
		{
			if( empty($this->woo_api_key) )
			{
				echo $this->err_message;
			}else{
			?>
             <div class="wrap">
                <h2>Edit Product</h2>
                <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2" style="margin: 0;">
                    <form action="" method="post">
                    <?php wp_nonce_field( 'product_action', 'product_nonce_field' ); ?>
                    <tr valign="top">
                        <th scope="row">Product Name</th>
                        <td><input type="text" name="product_name" value="<?php echo $this->product_detail->name;?>" /></td>
                    </tr>
                    <tr><td><?php submit_button( __( 'Submit', 'textdomain' ), 'button-primary' ); ?></td></tr>
                    </form>
                    </div>
                    </div>
            </div>
            <?php }
		}
		public function woo_order_page_callback()
		{
			if( empty($this->woo_api_key) )
			{
				echo $this->err_message;
			}else{
			?>
            <div class="wrap">
			<h2>Order Listing</h2>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2" style="margin: 0;">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->order_obj->prepare_items();
								$this->order_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
            <?php }
		}
		public static function get_instance()
		{
			if ( ! isset( self::$instance ) )
			{
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
}
add_action( 'plugins_loaded', function ()
{
	WooApiList::get_instance();
});