<?php

include_once __DIR__ . '/../wordpress/wp-content/plugins/wp-e-commerce/wpsc-merchants/bitpay.merchant.php';

class bitpaymerchantTest extends WP_UnitTestCase {

  function testbitpay_js_init() {

    bitpay_js_init();

  }

  function testdebuglog() {

    $contents = '';

    debuglog($contents);

  }

  function testcreate_table() {
    
    create_table();
    #Check if Table is there
    # assert you don't change an existing table (functional test) Mock dbDelta

  }

	function testgenerate_keys() {

    generate_keys();
    #Expect that methods were called


  }

  function testcreate_client() {

    $network = "Testnet";
    $private = $this->getMock('Bitpay\\PrivateKey');
    $private->expects($this->any())->method('__toString')
            ->will($this->returnValue('3a1cb093db55fc9cc6f2e1efc3938e4e498d8b2557a975249a49e2aec70ad471'));

    $public = $this->getMock('Bitpay\\PublicKey');
    $public->expects($this->any())->method('__toString')
            ->will($this->returnValue('03bb80b4391db1a7ba344fbe5421d87952a4b8934ca0865ae70591d1614e0f6fc8'));

    $client = create_client($network, $public, $private);

    $expected_client = new \Bitpay\Client\Client();
    $expected_client->setNetwork(new Bitpay\Network\Testnet);
    $expected_client->setPublicKey($public);
    $expected_client->setPrivateKey($private);
    $expected_client->setAdapter(new Bitpay\Client\Adapter\CurlAdapter());

    $this->assertTrue($expected_client == $client);

  }

  function testpairing() {

    $sin = $this->getMock('Bitpay\\SinKey');
    $sin->expects($this->any())->method('__toString')
            ->will($this->returnValue('TfLG7rjKVMa9AFBaBKrS6ti8XM62yJA4D4c'));

    $client = $this->getMock('Bitpay\\Client\\Client');
    $token = $this->getMock('Bitpay\\TokenInterface');
    $client->expects($this->any())->method('createToken')->will($this->returnValue($token));
    $pairing_code = 'pairing';

    $expected_token = pairing($pairing_code, $client, $sin);

    $this->assertTrue($token == $expected_token);
  }


  function testsave_keys() {

    $network = "Testnet";

    // mock the client library to return key array copy numbers from tool
    $private = $this->getMock('Bitpay\\PrivateKey');
    $private->expects($this->any())->method('__toString')
            ->will($this->returnValue('3a1cb093db55fc9cc6f2e1efc3938e4e498d8b2557a975249a49e2aec70ad471'));

    $public = $this->getMock('Bitpay\\PublicKey');
    $public->expects($this->any())->method('__toString')
            ->will($this->returnValue('03bb80b4391db1a7ba344fbe5421d87952a4b8934ca0865ae70591d1614e0f6fc8'));

    $sin = $this->getMock('Bitpay\\SinKey');
    $sin->expects($this->any())->method('__toString')
            ->will($this->returnValue('TfLG7rjKVMa9AFBaBKrS6ti8XM62yJA4D4c'));

    $token = $this->getMock('Bitpay\\TokenInterface');
    $token->expects($this->any())->method('getFacade')->will($this->returnValue('pos'));
    
    save_keys($token, $network, $private, $public, $sin);

  }

  function testpair_and_get_token() {

    #Should not be tested

  }

  function testform_bitpay() {

    #Should be a functionallity test. Jasmine

  }

  function testsubmit_bitpay() {

    # test cases for different button states
    submit_bitpay();

  }

  function testgateway_bitpay() {

    $separator = '?';
    $sessionid = '0';

    //gateway_bitpay($separator, $sessionid);

  }

  function testbitpay_callback() {

    require_once "phpmockstream.php";
    $_GET['bitpay_callback'] = true;
    stream_wrapper_unregister("php");
    stream_wrapper_register("php", "MockPhpStream");
    file_put_contents('php://input', '[
    {"code":1000,
    "amount":0.0195,
    "timestamp":"2013­12­02T16:16:29.612Z",
    "description":"2",
    "txType":"sale",
    "exRates":{"USD":1025},
    "buyerFields":{
      "buyerName":"BitPay Customer",
      "buyerAddress1":"3423 Piedmont Rd NE",
      "buyerAddress2":"Suite 516",
      "buyerCity":"Atlanta",
      "buyerState":"GA",
      "buyerZip":"30305",
      "buyerEmail":"customer@bitpay.com",
      "buyerPhone":"1­855­4­BITPAY"},
    "invoiceId":"C8a5bQeRTPDineVSDCw6KJ",
    "sourceType":"invoice",
    "orderId":"2"},
    {"code":1001,
    "amount":-0.0002,
    "timestamp":"2013­12­02T16:16:29.612Z",
    "txType":"fee",
    "exRates":{"USD":1025},
    "buyerFields":{
      "buyerName":"BitPay Customer",
      "buyerAddress1":"3423 Piedmont Rd NE",
      "buyerAddress2":"Suite 516","buyerCity":"Atlanta",
      "buyerState":"GA",
      "buyerZip":"30305",
      "buyerEmail":"customer@bitpay.com",
      "buyerPhone":"1­855­4­BITPAY"},
    "invoiceId":"C8a5bQeRTPDineVSDCw6KJ",
    "sourceType":"invoice",
    "orderId":"2"},
    {"code":1000,
    "amount":0.1093,
    "timestamp":"2014­01­06T19:01:09.522Z",
    "description":"Bill 1",
    "txType":"sale",
    "exRates":{"USD":915.0704604254529},
    "buyerFields":{},
    "invoiceId":"JHfkEPc212HThzB25ngz31",
    "sourceType":"invoice",
    "orderId":""},
    {"code":1001,
    "amount":-0.0011,
    "timestamp":"2014­01­06T19:01:09.522Z",
    "txType":"fee",
    "exRates":{"USD":915.0704604254529},
    "buyerFields":{},
    "invoiceId":"JHfkEPc212HThzB25ngz31",
    "sourceType":"invoice"}
    ]');
    $data = file_get_contents('php://input');
    bitpay_callback();
    stream_wrapper_restore("php");

  }

}

