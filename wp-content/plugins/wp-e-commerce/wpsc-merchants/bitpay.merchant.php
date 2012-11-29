<?php

$nzshpcrt_gateways[$num]['name'] = 'Bitcoins';
$nzshpcrt_gateways[$num]['internalname'] = 'bitpay';
$nzshpcrt_gateways[$num]['function'] = 'gateway_bitpay';
$nzshpcrt_gateways[$num]['form'] = 'form_bitpay';
$nzshpcrt_gateways[$num]['submit_function'] = "submit_bitpay";

function debuglog($contents)
{
	$file = 'wp-content/plugins/wp-e-commerce/wpsc-merchants/bitpay/log.txt';
	file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
	if (is_array($contents))
		file_put_contents($file, var_export($contents, true)."\n", FILE_APPEND);		
	else if (is_object($contents))
		file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
	else
		file_put_contents($file, $contents."\n", FILE_APPEND);
}


function form_bitpay()
{	
	$rows = array();
	
	// API key
	$rows[] = array('API key', '<input name="bitpay_apikey" type="text" value="'.get_option('bitpay_apikey').'" />', 'Create this at bitpay.com.');

	// transaction speed
	$sHigh = $sMedium = $sLow = '';
	switch(get_option('bitpay_transaction_speed')){
		case 'high': $sHigh = 'selected="selected"'; break;
		case 'medium': $sMedium = 'selected="selected"'; break;
		case 'low': $sLow = 'selected="selected"'; break;
		}
	$rows[] = array('Transaction Speed', 
		'<select name="bitpay_transaction_speed">'
		.'<option value="high" '.$sHigh.'>High</option>'
		.'<option value="medium" '.$sMedium.'>Medium</option>'
		.'<option value="low" '.$sLow.'>Low</option>'
		.'</select>');
		
	foreach($rows as $r)
	{
		$output.= '<tr> <td>'.$r[0].'</td> <td>'.$r[1];
		if (isset($r[2]))
			$output .= '<BR/><small>'.$r[2].'</small></td> ';
		$output.= '</tr>';
	}
	
	return $output;
}

function submit_bitpay()
{
	$params = array('bitpay_apikey', 'bitpay_transaction_speed');
	foreach($params as $p)
		if ($_POST[$p] != null)
			update_option($p, $_POST[$p]);
	return true;
}

function gateway_bitpay($seperator, $sessionid)
{
	require('wp-content/plugins/wp-e-commerce/wpsc-merchants/bitpay/bp_lib.php');
	
	//$wpdb is the database handle,
	//$wpsc_cart is the shopping cart object
	global $wpdb, $wpsc_cart;
	
	//This grabs the purchase log id from the database
	//that refers to the $sessionid
	$purchase_log = $wpdb->get_row(
		"SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS.
		"` WHERE `sessionid`= ".$sessionid." LIMIT 1"
		,ARRAY_A) ;

	//This grabs the users info using the $purchase_log
	// from the previous SQL query
	$usersql = "SELECT `".WPSC_TABLE_SUBMITED_FORM_DATA."`.value,
		`".WPSC_TABLE_CHECKOUT_FORMS."`.`name`,
		`".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM
		`".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN
		`".WPSC_TABLE_SUBMITED_FORM_DATA."` ON
		`".WPSC_TABLE_CHECKOUT_FORMS."`.id =
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`=".$purchase_log['id'];
	$userinfo = $wpdb->get_results($usersql, ARRAY_A);
	// convert from awkward format 
	foreach((array)$userinfo as $value) 
		if (strlen($value['value']))
			$ui[$value['unique_name']] = $value['value'];
	$userinfo = $ui;
		
	
	// name
	if (isset($userinfo['billingfirstname']))
	{
		$options['buyerName'] = $userinfo['billingfirstname'];
		if (isset($userinfo['billinglastname']))
			$options['buyerName'] .= ' '.$userinfo['billinglastname'];
	}
	
	//address -- remove newlines
	if (isset($userinfo['billingaddress']))
	{
		$newline = strpos($userinfo['billingaddress'],"\n");
		if ($newline !== FALSE)
		{
			$options['buyerAddress1'] = substr($userinfo['billingaddress'], 0, $newline);
			$options['buyerAddress2'] = substr($userinfo['billingaddress'], $newline+1);
			$options['buyerAddress2'] = preg_replace('/\r\n/', ' ', $options['buyerAddress2'], -1, $count);
		}
		else
			$options['buyerAddress1'] = $userinfo['billingaddress'];
	}
	// state
	if (isset($userinfo['billingstate']))
		$options['buyerState'] = wpsc_get_state_by_id($userinfo['billingstate'], 'code');

	// more user info
	foreach(array('billingphone' => 'buyerPhone', 'billingemail' => 'buyerEmail', 'billingcity' => 'buyerCity',  'billingcountry' => 'buyerCountry', 'billingpostcode' => 'buyerZip') as $f => $t)
		if ($userinfo[$f])
			$options[$t] = $userinfo[$f];

	// itemDesc
	if (count($wpsc_cart->cart_items) == 1)
	{
		$item = $wpsc_cart->cart_items[0];
		$options['itemDesc'] = $item->product_name;
		if ( $item->quantity > 1 )
			$options['itemDesc'] = $item->quantity.'x '.$options['itemDesc'];
	}
	else
	{
		foreach($wpsc_cart->cart_items as $item) 
			$quantity += $item->quantity;
		$options['itemDesc'] = $quantity.' items';
	}	
	
	//currency
	$currencyId = get_option( 'currency_type' );
	$options['currency'] = $wpdb->get_var( $wpdb->prepare( "SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id` = %d LIMIT 1", $currencyId ) );
	
	$options['notificationURL'] = get_option('siteurl')."/?bitpay_callback=true";
	$options['redirectURL'] = get_option('siteurl');
	$options['transactionSpeed'] = get_option('bitpay_transaction_speed');	
	$options['apiKey'] = get_option('bitpay_apikey');
	$options['posData'] = $sessionid;
	$options['fullNotifications'] = true;
	
	// truncate if longer than 100 chars
	foreach(array("buyerName", "buyerAddress1", "buyerAddress2", "buyerCity", "buyerState", "buyerZip", "buyerCountry", "buyerEmail", "buyerPhone") as $k)
		$options[$k] = substr($options[$k], 0, 100);
		
	$price = number_format($wpsc_cart->total_price,2);	
	$invoice = bpCreateInvoice($sessionid, $price, $sessionid, $options);
	
	if (isset($invoice['error'])) {
		debuglog($invoice);
		// close order
		$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '5' WHERE `sessionid`=".$sessionid;
		$wpdb->query($sql);
		//redirect back to checkout page with errors		
		$_SESSION['WpscGatewayErrorMessage'] = __('Sorry your transaction did not go through successfully, please try again.');
		header("Location: ".get_option('checkout_url'));
	}else{
		$wpsc_cart->empty_cart();
		unset($_SESSION['WpscGatewayErrorMessage']);
		header("Location: ".$invoice['url']);
		exit();
	}
}

function bitpay_callback()
{
	if(isset($_GET['bitpay_callback']))
	{
		global $wpdb;
		require('wp-content/plugins/wp-e-commerce/wpsc-merchants/bitpay/bp_lib.php');
		
		$response = bpVerifyNotification(get_option('bitpay_apikey'));
		
		if (isset($response['error']))
			debuglog($response);
		else
		{
			$sessionid = $response['posData'];

			switch($response['status'])
			{
				case 'paid':
					break;
				case 'confirmed':
				case 'complete':
					$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS.
						"` SET `processed`= '2' WHERE `sessionid`=".$sessionid;
					if (is_numeric($sessionid))
						$wpdb->query($sql);
					break;
			}
		}
	}
}

add_action('init', 'bitpay_callback');

?>