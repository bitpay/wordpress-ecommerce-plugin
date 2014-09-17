<?php

/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2011-2014 BitPay
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

$nzshpcrt_gateways[$num] = array(
		'name'                                    => __( 'Bitcoin Payments by BitPay', 'wpsc' ),
		'api_version'                             => 1.0,
		'image'                                   => WPSC_URL . '/images/bitcoin.png',
		'has_recurring_billing'                   => false,
		'wp_admin_cannot_cancel'                  => true,
		'display_name'                            => __( 'Bitcoin', 'wpsc' ),
		'user_defined_name[wpsc_merchant_bitpay]' => 'Bitcoin',
		'requirements'                            => array('php_version' => 5.3),
		'internalname'                            => 'wpsc_merchant_bitpay',
		'form'                                    => 'form_bitpay',
		'submit_function'                         => 'submit_bitpay',
		'function'                                => 'gateway_bitpay',
		);

function debuglog($contents)
{
	if (isset($contents)) {
		if (is_resource($contents)) {
			error_log(serialize($contents));
		} else {
			error_log(var_dump($contents, true));
		}
	}
}

function form_bitpay()
{	
	$rows = array();
	
	// API Key
	$rows[] = array(
			'API Key',
			'<input name="bitpay_apikey" type="text" value="' . get_option('bitpay_apikey') . '" />',
			'<p class="description">Log into your merchant account at <a href="https://bitpay.com" target="_blank">https://bitpay.com</a> if you do not have one already. Click on "My Account" -> "API Access Keys" -> "Add New API Key".  Copy the long string of letters and numbers into this box.</p>'
			);

	// API Mode
	$test = $live = '';

	switch (get_option('bitpay_testmode')) {
		case 'false':
			$live = 'selected="selected"';
			break;
		case 'true':
			$test = 'selected="selected"';
			break;
		default:
			$live = 'selected="selected"';
	}

	$rows[] = array(
			'API Mode',
			'<select name="bitpay_testmode"><option value="false" ' . $live . '>Live</option><option value="true" ' . $test . '>Test</option></select>',
			'<p class="description">Select whether you are using a Test API Key or a Live API Key.  You can sign up for a test key at <a href="https://test.bitpay.com" target="_blank">https://test.bitpay.com</a>.</p>'
			);

	// transaction speed
	$sHigh = $sMedium = $sLow = '';

	switch (get_option('bitpay_transaction_speed')) {
		case 'high':
			$sHigh   = 'selected="selected"';
			break;
		case 'medium':
			$sMedium = 'selected="selected"';
			break;
		case 'low':
			$sLow    = 'selected="selected"';
			break;
		default:
			$sLow    = 'selected="selected"';
	}

	$rows[] = array(
			'Transaction Speed',
			'<select name="bitpay_transaction_speed"><option value="high" ' . $sHigh . '>High</option><option value="medium" ' . $sMedium . '>Medium</option><option value="low" ' . $sLow . '>Low</option></select>',
			'<p class="description">Speed at which the Bitcoin payment registers as "confirmed" to the store: High = Instant, Medium = ~10m, Low = ~1hr (safest).<p>'
			);

	//Allows the merchant to specify a URL to redirect to upon the customer completing payment on the bitpay.com
	//invoice page. This is typcially the "Transaction Results" page.
	$rows[] = array(
			'Redirect URL',
			'<input name="bitpay_redirect" type="text" value="' . get_option('bitpay_redirect') . '" />',
			'<p class="description"><strong>Important!</strong> Put the URL that you want the buyer to be redirected to after payment. This is usually a "Thanks for your order!" page.</p>'
			);

	$output .= '
	<tr>
		<td colspan="2">
			<p class="description">
				<img src="' . WPSC_URL . '/images/bitcoin.png" /><br /><strong>For 24/7 support, please visit our website <a href="https://bitpay.com" target="_blank">https://bitpay.com</a> or send an email to <a href="mailto:support@bitpay.com" target="_blank">support@bitpay.com</a> for immediate attention. Thank you for choosing BitPay!</strong>
			</p>
		</td>
	</tr>' . "\n";

	foreach ($rows as $r) {
		$output .= '<tr> <td>' . $r[0] . '</td> <td>' . $r[1];

		if (isset($r[2])) {
			$output .= $r[2];
		}

		$output .= '</td></tr>';
	}
	
	return $output;
}

function submit_bitpay()
{
	if (isset($_POST['submit']) && stristr($_POST['submit'], 'Update') !== false) {
		$params = array(
				'bitpay_apikey',
				'bitpay_testmode',
				'bitpay_transaction_speed',
				'bitpay_redirect'
				);

		foreach ($params as $p) {
			if ($_POST[$p] != null) {
				update_option($p, $_POST[$p]);
			} else {
				add_settings_error($p, 'error', __('The setting ' . $p . ' cannot be blank! Please enter a value for this field', 'wpse'), 'error');
			}
		}
	}

	return true;
}

function gateway_bitpay($seperator, $sessionid)
{
	include_once dirname(__FILE__).'/bitpay/bp_lib.php';
	
	//$wpdb is the database handle,
	//$wpsc_cart is the shopping cart object
	global $wpdb, $wpsc_cart;
	
	//This grabs the purchase log id from the database
	//that refers to the $sessionid
	$purchase_log = $wpdb->get_row(
		"SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
		"` WHERE `sessionid`= " . $sessionid . " LIMIT 1",
		ARRAY_A);

	//This grabs the users info using the $purchase_log
	// from the previous SQL query
	$usersql = "SELECT `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.value,
		`" . WPSC_TABLE_CHECKOUT_FORMS . "`.`name`,
		`" . WPSC_TABLE_CHECKOUT_FORMS . "`.`unique_name` FROM
		`" . WPSC_TABLE_CHECKOUT_FORMS . "` LEFT JOIN
		`" . WPSC_TABLE_SUBMITED_FORM_DATA . "` ON
		`" . WPSC_TABLE_CHECKOUT_FORMS . "`.id =
		`" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.`form_id` WHERE
		`" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.`log_id`='" . $purchase_log['id'] . "'";

	$userinfo = $wpdb->get_results($usersql, ARRAY_A);

	// convert from awkward format 
	foreach ((array)$userinfo as $value) {
		if (strlen($value['value'])) {
			$ui[$value['unique_name']] = $value['value'];
		}
	}

	$userinfo = $ui;

	// name
	if (isset($userinfo['billingfirstname'])) {
		$options['buyerName'] = $userinfo['billingfirstname'];

		if (isset($userinfo['billinglastname'])) {
			$options['buyerName'] .= ' ' . $userinfo['billinglastname'];
		}
	}
	
	//address -- remove newlines
	if (isset($userinfo['billingaddress'])) {
		$newline = strpos($userinfo['billingaddress'],"\n");

		if ($newline !== FALSE) {
			$options['buyerAddress1'] = substr($userinfo['billingaddress'], 0, $newline);
			$options['buyerAddress2'] = substr($userinfo['billingaddress'], $newline + 1);
			$options['buyerAddress2'] = preg_replace('/\r\n/', ' ', $options['buyerAddress2'], -1, $count);
		} else {
			$options['buyerAddress1'] = $userinfo['billingaddress'];
		}
	}

	// state
	if (isset($userinfo['billingstate'])) {
		$options['buyerState'] = wpsc_get_state_by_id($userinfo['billingstate'], 'code');
	}

	// more user info
	foreach (array('billingphone' => 'buyerPhone', 'billingemail' => 'buyerEmail', 'billingcity' => 'buyerCity',  'billingcountry' => 'buyerCountry', 'billingpostcode' => 'buyerZip') as $f => $t) {
		if ($userinfo[$f]) {
			$options[$t] = $userinfo[$f];
		}
	}

	// itemDesc
	if (count($wpsc_cart->cart_items) == 1) {
		$item = $wpsc_cart->cart_items[0];
		$options['itemDesc'] = $item->product_name;

		if ( $item->quantity > 1 ) {
			$options['itemDesc'] = $item->quantity . 'x ' . $options['itemDesc'];
		}

	} else {

		foreach ($wpsc_cart->cart_items as $item) {
			$quantity += $item->quantity;
		}
		
		$options['itemDesc'] = $quantity . ' items';
	}

	if (get_option('permalink_structure') != '') {
		$separator = "?";
	} else {
		$separator = "&";
	}
	
	//currency
	$currencyId = get_option('currency_type');
	$options['currency']          = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` = %d LIMIT 1", $currencyId));
	$options['notificationURL']   = get_option('siteurl') . '/?bitpay_callback=true';
	error_log("URL is " . $options['notificationURL']);

	// Test or Live mode URL switch
	$options['testMode']          = get_option('test_mode');

	//pass sessionid along so that it can be used to populate the transaction results page
	$options['redirectURL']       = get_option('bitpay_redirect') . $separator . 'sessionid=' . $sessionid;

	$options['transactionSpeed']  = get_option('bitpay_transaction_speed');
	$options['apiKey']            = get_option('bitpay_apikey');
	$options['posData']           = $sessionid;
	$options['fullNotifications'] = true;
	
	// truncate if longer than 100 chars
	foreach (array("buyerName", "buyerAddress1", "buyerAddress2", "buyerCity", "buyerState", "buyerZip", "buyerCountry", "buyerEmail", "buyerPhone") as $k) {
		$options[$k] = substr($options[$k], 0, 100);
	}

	$price   = number_format($wpsc_cart->total_price, 2);	
	$invoice = bpCreateInvoice($sessionid, $price, $sessionid, $options);

	if (isset($invoice['error'])) {
		debuglog($invoice);

		// close order
		$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `sessionid`=" . $sessionid;
		$wpdb->query($sql);

		//redirect back to checkout page with errors		
		$_SESSION['WpscGatewayErrorMessage'] = __('Sorry your transaction did not go through successfully, please try again.');
		header('Location: ' . get_option('checkout_url'));
	} else {
		$wpsc_cart->empty_cart();

		unset($_SESSION['WpscGatewayErrorMessage']);
		header('Location: ' . $invoice['url']);

		exit();
	}

}

function bitpay_callback()
{
	if (isset($_GET['bitpay_callback'])) {
		global $wpdb;

		include_once dirname(__FILE__).'/bitpay/bp_lib.php';
		error_log("Notification URL hit");
		$response = bpVerifyNotification(get_option('bitpay_apikey'));
		
		if (isset($response['error'])) {
			debuglog($response);
		} else {
			$sessionid = $response['posData'];

			//get buyer email
			$sql          = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`=" . $sessionid;
			$purchase_log = $wpdb->get_results($sql, ARRAY_A);

			$email_form_field = $wpdb->get_var("SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1");
			$email            = $wpdb->get_var($wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase_log[0]['id'], $email_form_field));

			//get cart contents
			$sql           = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`=" . $purchase_log[0]['id'];
			$cart_contents = $wpdb->get_results($sql, ARRAY_A);
			
			//get currency symbol
			$currency_id     = get_option('currency_type');
			$sql             = "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`=" . $currency_id;
			$currency_data   = $wpdb->get_results($sql, ARRAY_A);
			$currency_symbol = $currency_data[0]['symbol'];

			//list products and individual prices in the email
			$message_product = "\r\n\r\nTransaction Details: \r\n\r\n";

			$pnp      = 0.0;
			$subtotal = 0.0;

			foreach ($cart_contents as $product) {
				$pnp += $product['pnp']; //shipping for each item
				$message_product .= 'x' . $product['quantity'] . ' ' . $product['name'] . ' - ' . $currency_symbol . ($product['price'] * $product['quantity']) . "\r\n";
				$subtotal += $product['price']*$product['quantity'];
			}

			//list subtotal
			$subtotal         = number_format($subtotal , 2 , '.', ',');
			$message_product .= "\r\n" . 'Subtotal: ' . $currency_symbol . $subtotal . "\r\n";

			//list total taxes and total shipping costs in the email
			$message_product .= 'Taxes: ' . $currency_symbol . $purchase_log[0]['wpec_taxes_total'] . "\r\n";
			$message_product .= 'Shipping: ' . $currency_symbol . ($purchase_log[0]['base_shipping'] + $pnp) . "\r\n\r\n";

			//display total price in the email
			$message_product .= 'Total Price: ' . $currency_symbol . $purchase_log[0]['totalprice'];

			switch ($response['status']) {
				//For low and medium transaction speeds, the order status is set to "Order Received". The customer receives
				//an initial email stating that the transaction has been paid.
				case 'paid':
					$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '2' WHERE `sessionid`=" . $sessionid;

					if (is_numeric($sessionid)) {
						$wpdb->query($sql);	
					}

					$message  = 'Thank you! Your payment has been received, but the transaction has not been confirmed on the bitcoin network. You will receive another email when the transaction has been confirmed.';
					$message .= $message_product;

					wp_mail($email, 'Payment Received', $message);

					$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `email_sent`= '1' WHERE `sessionid`=" . $sessionid;

					transaction_results($sessionid, false);	//false because this is just for email notification	
					break;

				//For low and medium transaction speeds, the order status will not change. For high transaction speed, the order
				//status is set to "Order Received" here. For all speeds, an email will be sent stating that the transaction has
				//been confirmed.
				case 'confirmed':
					$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '2' WHERE `sessionid`=" . $sessionid;

					if (is_numeric($sessionid)) {
						$wpdb->query($sql);	
					}

					//display initial "thank you" if transaction speed is high, as the 'paid' status is skipped on high speed
					if (get_option('bitpay_transaction_speed') == 'high') {
						$message  = 'Thank you! Your payment has been received, and the transaction has been confirmed on the bitcoin network. You will receive another email when the transaction is complete.';
						$message .= $message_product;

						wp_mail($email, 'Payment Received', $message);

					} else {

						$message = 'Your transaction has now been confirmed on the bitcoin network. You will receive another email when the transaction is complete.';

						wp_mail($email, 'Transaction Confirmed', $message);
					}

					$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `email_sent`= '1' WHERE `sessionid`=" . $sessionid;

					transaction_results($sessionid, false); //false because this is just for email notification
					break;

				//The purchase receipt email is sent upon the invoice status changing to "complete", and the order
				//status is changed to Accepted Payment
				case 'complete':
					$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '3' WHERE `sessionid`=" . $sessionid;

					if (is_numeric($sessionid)) {
						$wpdb->query($sql);
					}

					$message = 'Your transaction is now complete! Thank you for using BitPay!';

					wp_mail($email, 'Transaction Complete', $message);

					$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `email_sent`= '1' WHERE `sessionid`=" . $sessionid;

					transaction_results($sessionid, false); //false because this is just for email notification
					break;
			}
		}
	}
}

add_action('init', 'bitpay_callback');
