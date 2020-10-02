<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WooOrderListClass extends WP_List_Table {

	var $location;
	var $apiurl;
	/** Class constructor */
	public function __construct() {

		$this->location = add_query_arg( 'deleted', 1, $referer );
		parent::__construct( [
			'singular' => __( 'Order', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Order', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}
	public static function get_members( $per_page = 5, $page_number = 1 ) {

		$body = get_product_using_api('orders');
		//echo '<pre>';
		//print_r($body);die;
		//$sql .= " LIMIT $per_page";
		//$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		//$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $body;
	}
	public static function record_count() {
		global $wpdb;
		$body = get_product_using_api('orders');
		return count($body);
	}
	public function no_items() {
		_e( 'No order avaliable.', 'sp' );
	}
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'date_created':
			case 'status':
			case 'total':
				return $item->$column_name;
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id
		);
	}
	function column_id( $item ) {

		$title = '<strong><a href="#" class="order-view"><strong>#'.$item->id.' '.$item->billing->first_name.' '.$item->billing->last_name.'</strong></a></strong>';
		$delete_nonce = wp_create_nonce( 'sp_member_customer' );
		$actions = [
			'delete' => sprintf( '<a onclick=\'return confirm("Delete this record?")\' href="?page=%s&action=%s&member=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item->id ), $delete_nonce )
		];
		return $title;
	}
	function column_total( $item ) 
	{

		$price = wc_price( $item->total );
		return $price;
	}
	function column_sku( $item ) 
	{

		if( empty($item->sku) )
		{
			return '<span class="na">–</span>';
		}
		return $item->sku;
	}
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'id' => __( 'Order', 'sp' ),
			'date_created' => __( 'Date', 'sp' ),
			'status' => __( 'Stock', 'sp' ),
			'total' => __( 'Price', 'sp' ),
		];
		return $columns;
	}
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'Name', true ),
		);

		return $sortable_columns;
	}
	function extra_tablenav( $which ) {
    global $wpdb, $testiURL, $tablename, $tablet;
	
    if ( $which == "top" ){}
    if ( $which == "bottom" ){
        //The code that goes after the table is there

    }
}
	public function get_bulk_actions() 
	{
		$actions = [
			//'bulk-delete' => 'Delete'
		];
		return $actions;
	}
	public function process_bulk_action() 
	{
		$referer = wp_unslash( $_SERVER['REQUEST_URI'] );
		$location = add_query_arg( 'message', 3, $referer );
	  //Detect when a bulk action is being triggered...
	  if ( 'delete' === $this->current_action() ) {
	
		// In our file that handles the request, verify the nonce.
		$nonce = esc_attr( $_REQUEST['_wpnonce'] );
	
		if ( ! wp_verify_nonce( $nonce, 'sp_member_customer' ) ) {
		  die( 'Go get a life script kiddies' );
		}
		else {
		  self::delete_member( absint( $_GET['member'] ) );	
		 // wp_redirect( $this->location );exit;
		  //exit;
		   //wp_redirect( '/wp-admin/admin.php?page=manufacturer_listing' );exit;
		}
	
	  }

	  // If the delete bulk action is triggered
	  if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		   || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
	  ) {
	
		$delete_ids = esc_sql( $_POST['bulk-delete'] );
	
		// loop over the array of record IDs and delete them
		foreach ( $delete_ids as $id ) {
		  self::delete_member( $id );
	
		}
	
		wp_redirect( esc_url( add_query_arg() ) );
		exit;
	  }
	}
	public static function delete_member( $id ) {
		global $wpdb;
        $table = $wpdb->prefix.'member_details';
		if( isset($id) && !empty($id) )
		{
			$wpdb->delete( $table, array( 'id' => $id ) );
		}		
	}
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();
		/** Process bulk action */
		$this->process_bulk_action();
		$per_page     = $this->get_items_per_page( 'member_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
		$this->items = self::get_members( $per_page, $current_page );
	}

}