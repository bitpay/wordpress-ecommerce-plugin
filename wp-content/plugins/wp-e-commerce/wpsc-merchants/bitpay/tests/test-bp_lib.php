<?php

include_once dirname(__FILE__).'/../bp_lib.php';

class bp_libTest extends WP_UnitTestCase {

	function testbpCreateInvoice() {
		echo "\n ***************** \n Testing bpCreateInvoice \n ***************** \n";

		$orderId = '';
		$price = '';
		$posData = '';
		$options = array();

		bpCreateInvoice($orderId, $price, $posData, $options = array());
	}
}

