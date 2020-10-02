<?php
function woo_get_api_url( $fetch_url = '', $query_string = true )
{
	if( empty($fetch_url) )
	{
		return false;
	}
	$api_url = site_url().'/wp-json/wc/v3/'.$fetch_url;
	if( $query_string === false )
	{
		return $api_url;
	}
	$url = add_query_arg( array('per_page' => 20, 'orderby' => 'date'), $api_url );
	return $url;
}
function get_product_using_api( $fetch_url = '' , $query_string = true )
{
	//echo get_api_url($fetch_url, $query_string);
	$fetch_url_u = woo_get_api_url($fetch_url, $query_string);	
	if( is_ssl() )
	{
		$api_response = wp_remote_get( $fetch_url_u, array(
		'sslverify' => FALSE,
		//'reject_unsafe_urls' => true,
		'headers' => array('Authorization' => 'Basic ' . base64_encode( WOO_KEY.':'.WOO_SECRET_KEY ))) );	 
		//$body = json_decode( $api_response['body'] );
		$responseBody = wp_remote_retrieve_body( $api_response );
		$result = json_decode( $responseBody );
		//echo '<pre>';
		//print_r($result);die;
		if ( (is_array( $result ) || is_object( $result )) && !is_wp_error( $result ) && !isset($result->data->status) ) {
			return $result;
		} else {
			return '';
		}
		return $result;
	}else
	{
		$result = get_data_for_non_httpos($fetch_url);
		return $result;
	}	
}
function get_data_for_non_httpos( $fetch_url = '' )
{
	if( empty($fetch_url) )
	{
		return false;
	}	
	$request_uri = woo_get_api_url($fetch_url, false);
	$non_ssl_header = get_non_ssl_header($request_uri);
	$api_response = wp_remote_get( $request_uri, array('headers' => $non_ssl_header) );
	$responseBody = wp_remote_retrieve_body( $api_response );
	$result = json_decode( $responseBody );
	//echo '<pre>';
	//echo $request_uri;
	//print_r($result);die;
	if ( (is_array( $result ) || is_object( $result )) && !is_wp_error( $result ) && !isset($result->data->status) ) {
		return $result;
	} else {
		return '';
	}
}
function get_non_ssl_header( $request_uri = '', $http_method = 'GET', $name = '' )
{
	if( empty($request_uri) )
	{
		return false;
	}
	$consumer_key = WOO_KEY;
	$consumer_secret = WOO_SECRET_KEY;
	// Request URI.
	//$request_uri = woo_get_api_url($fetch_url, false);
	//$request_uri_h = woo_get_api_url($fetch_url, true);
	//$request_uri = $fetch_url;
	// Unique once-off parameters.
	$nonce = uniqid();
	$timestamp = time();
	$oauth_signature_method = 'HMAC-SHA1';
	$hash_algorithm = strtolower( str_replace( 'HMAC-', '', $oauth_signature_method ) ); // sha1
	$secret = $consumer_secret . '&';
	//$http_method = 'GET';
	$base_request_uri = rawurlencode( $request_uri );
	if( $http_method == 'POST' )
	{
		$params = array( 'name' => rawurlencode($name),'oauth_consumer_key' => $consumer_key, 'oauth_nonce' => $nonce, 'oauth_signature_method' => 'HMAC-SHA1', 'oauth_timestamp' => $timestamp );
	}else
	{
		$params = array( 'oauth_consumer_key' => $consumer_key, 'oauth_nonce' => $nonce, 'oauth_signature_method' => 'HMAC-SHA1', 'oauth_timestamp' => $timestamp );
	}	
	$query_string = woo_join_params( $params );
	$string_to_sign = $http_method . '&' . $base_request_uri . '&' . $query_string;
	$oauth_signature = base64_encode( hash_hmac( $hash_algorithm, $string_to_sign, $secret, true ) );
	$non_ssl_header = array(    "Authorization" => "OAuth oauth_consumer_key=\"".$consumer_key."\",oauth_signature_method=\"".$oauth_signature_method."\",oauth_timestamp=\"".$timestamp."\",oauth_nonce=\"".$nonce."\",oauth_signature=\"".$oauth_signature."\"");
	return $non_ssl_header;
}
function woo_join_params( $params ) 
{
	$query_params = array();

	foreach ( $params as $param_key => $param_value ) {
		//if( $param_key == 'name' )
		//continue;
		$string = $param_key . '=' . $param_value;
		$query_params[] = str_replace( array( '+', '%7E' ), array( ' ', '~' ), rawurlencode( $string ) );
	}
	
	return implode( '%26', $query_params );
}
function update_product_using_api( $id = '', $name = '' )
{
	if( empty($id) || empty($name) )
	{
		return false;
	}
	if( is_ssl() )
	{
		$api_response = wp_remote_post( woo_get_api_url('products/'.$id,false), array(
	'sslverify' => FALSE,
 	'headers' => array('Authorization' => 'Basic ' . base64_encode( WOO_KEY.':'.WOO_SECRET_KEY )),
	'body' => array(
    		'name' => $name,
	)) );
	}else
	{
		$request_uri = woo_get_api_url('products/'.$id,false);
		$non_ssl_header = get_non_ssl_header($request_uri,'POST', $name);
		$api_response = wp_remote_post( $request_uri, array(
	'sslverify' => FALSE,
 	'headers' => $non_ssl_header,
	'body' => array(
    		'name' => $name,
	)) );
	}		 
	$response = wp_remote_retrieve_response_message( $api_response );
	//echo '<pre>';
	//print_r($api_response);die;
	if ( $response === 'OK' ) {
		return true;
	} else {
		return false;
	}
	return $result;
}