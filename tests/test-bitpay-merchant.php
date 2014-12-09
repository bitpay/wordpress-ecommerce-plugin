<?php

include_once __DIR__ . '/../wordpress/wp-content/plugins/wp-e-commerce/wpsc-merchants/bitpay.merchant.php';

class bitpaymerchantTest extends WP_UnitTestCase
{

    public function testbitpay_js_init()
    {
        bitpay_js_init();
    }

    public function testdebuglog()
    {
        $contents = '';

        debuglog($contents);
    }

    public function testcreate_table()
    {
        create_table();

        // Check if Table is there
        // assert you don't change an existing table (functional test) Mock dbDelta
    }

    public function testgenerate_keys()
    {
        generate_keys();

        // Expect that methods were called
    }

    public function testcreate_client()
    {

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

    public function testpairing()
    {

        $sin = $this->getMock('Bitpay\\SinKey');

        $sin->expects($this->any())->method('__toString')
                ->will($this->returnValue('TfLG7rjKVMa9AFBaBKrS6ti8XM62yJA4D4c'));

        $client = $this->getMock('Bitpay\\Client\\Client');
        $token  = $this->getMock('Bitpay\\TokenInterface');

        $client->expects($this->any())->method('createToken')->will($this->returnValue($token));

        $pairing_code = 'pairing';

        $expected_token = pairing($pairing_code, $client, $sin);

        $this->assertTrue($token == $expected_token);
    }

    public function testsave_keys()
    {

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

    public function testpair_and_get_token()
    {
        // Should not be tested
    }

    public function testform_bitpay()
    {
        // Should be a functionallity test. Jasmine
    }

    public function testsubmit_bitpay()
    {
        // test cases for different button states
        submit_bitpay();
    }

    public function testgateway_bitpay()
    {
        $separator = '?';
        $sessionid = '0';

        // gateway_bitpay($separator, $sessionid);
    }

    public function testbitpay_callback()
    {
        require_once "phpmockstream.php";

        $_GET['bitpay_callback'] = true;

        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "MockPhpStream");

        file_put_contents('php://input', '[' .
            '{'.
            '"id":"QXVd4WRtsPbax9jLe7uHBh",'.
            '"url":"https://test.bitpay.com/invoice?id=QXVd4WRtsPbax9jLe7uHBh",'.
            '"posData":"8261418159074",'.
            '"status":"paid",'.
            '"btcPrice":"0.0014",'.
            '"price":0.5,'.
            '"currency":"USD",'.
            '"invoiceTime":1418158912927,'.
            '"expirationTime":1418159812927,'.
            '"currentTime":1418158957984,'.
            '"btcPaid":"0.0014","rate":353.29,'.
            '"exceptionStatus":false,'.
            '"buyerFields":{'.
            '"buyerCity":"Bob Dole",'.
            '"buyerEmail":"alex@bitpay.com",'.
            '"buyerPhone":null,'.
            '"buyerAddress2":"",'.
            '"buyerZip":"41015",'.
            '"buyerState":"KS",'.
            '"buyerCountry":"US",'.
            '"buyerName":"Bobert Dole",'.
            '"buyerAddress1":"123 Bob Dole Way"}'.
            '}' .
            ']'
        );

        $data = file_get_contents('php://input');

        bitpay_callback();

        stream_wrapper_restore("php");
    }

}
