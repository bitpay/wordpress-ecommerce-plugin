<?php

include_once dirname(__FILE__).'/../../bitpay.merchant.php';

class bitpaymerchantTest extends WP_UnitTestCase {

	function testdebuglog() {
		echo "\n ***************** \n Testing debuglog \n ***************** \n";
		$contents = '';
		debuglog($contents);
	}

	function testgateway_bitpay() {

		$seperator = '';
		$sessionid = '';
		gateway_bitpay($seperator, $sessionid);
	}
}

