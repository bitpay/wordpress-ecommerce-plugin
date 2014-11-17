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

// Load BitPay Client
// Load up the BitPay library
require_once __DIR__.'/bitpay/lib/Bitpay/Autoloader.php';
\Bitpay\Autoloader::register();

// Load upgrade file
require_once ABSPATH.'wp-admin/includes/upgrade.php';

// Load Javascript from bitpay.js and jquery
function bitpay_js_init()
{
    wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js");
    wp_register_script('jquery-ui', "//code.jquery.com/ui/1.11.1/jquery-ui.js");
    wp_register_style('jquery-ui-css', "//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css");
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('jquery-ui-css');
}

add_action('admin_enqueue_scripts', 'bitpay_js_init');

$nzshpcrt_gateways[$num] = array(
        'name'                                    => __('Bitcoin Payments by BitPay', 'wpsc'),
        'api_version'                             => 1.0,
        'image'                                   => WPSC_URL.'/wpsc-merchants/bitpay/assets/img/logo.png',
        'has_recurring_billing'                   => false,
        'wp_admin_cannot_cancel'                  => true,
        'display_name'                            => __('Bitcoin', 'wpsc'),
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
            error_log(var_export($contents, true));
        }
    }
}

function create_table()
{
    // Access to Wordpress Database
    global $wpdb;

    // Query for creating Keys Table
    $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bitpay_keys` (
       `id` int(11) not null auto_increment,
       `private_key` varchar(1000) not null,
       `public_key` varchar(1000) not null,
       `sin` varchar(250) not null,
       `token` varchar(2000) not null,
       `network` varchar(250) not null,
       `facade` varchar(250) not null,
       `user_id` varchar(250) not null,
       `enable_all` varchar(250) not null,
       `in_use` varchar(250) not null,
       `created_at` datetime not null,
       PRIMARY KEY (`id`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

    // execute SQL statement
    dbDelta($sql);
}

function generate_keys()
{
    /**
     * GENERATING THE KEYS
     */
    $private = new \Bitpay\PrivateKey('/tmp/private.key');
    $public  = new \Bitpay\PublicKey('/tmp/public.key');
    $sin     = new \Bitpay\SinKey('/tmp/sin.key');

    // Generate Private Key values
    $private->generate();

    // Generate Public Key values
    $public->setPrivateKey($private);
    $public->generate();

    // Generate Sin Key values
    $sin->setPublicKey($public);
    $sin->generate();

    return array($private, $public, $sin);
}

function create_client($network, $public, $private)
{
    // @var \Bitpay\Client\Client
    $client = new \Bitpay\Client\Client();

    //Set the network being paired with.
    $networkClass = 'Bitpay\\Network\\'.$network;

    if (!class_exists($networkClass)) {
        throw new Exception('Cannot find network.');
    }

    $client->setNetwork(new $networkClass());

    //Set Keys
    $client->setPublicKey($public);
    $client->setPrivateKey($private);
    $client->setAdapter(new Bitpay\Client\Adapter\CurlAdapter());

    return $client;
}

function pairing($pairing_code, $client, $sin)
{
    /**
     *PAIRING
     */

    //Create Token
    try {
        // @var \Bitpay\TokenInterface
        $token = $client->createToken(
            array(
                'id'          => (string) $sin,
                'pairingCode' => $pairing_code,
                'label'       => 'WP Ecommerce - '.get_option('siteurl'),
            )
        );

        return $token;
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log($error);
        update_option('bitpay_error', $error);
        unset($_SESSION['WpscGatewayErrorMessage']);
        header('Location: '.get_site_url().'/wp-admin/options-general.php?page=wpsc-settings&tab=gateway&payment_gateway_id=wpsc_merchant_bitpay');
        exit();
    }
}

function save_keys($token, $network, $private, $public, $sin)
{
    /**
     *SAVING KEYS TO DATABASE
     */

    // Access to Wordpress Database
    global $wpdb;
    $table_name = $wpdb->prefix.'bitpay_keys';

    //Get Current user's ids
    $user_ID = get_current_user_id();

    //Get Token's facade
    $facade = $token->getFacade();

    //Token's with POS facade are seen by all admin by default
    $enable_all = ($facade === 'pos') ? 'true' : 'false';

    //Protect your data!
    $mcrypt_ext = new \Bitpay\Crypto\McryptExtension();
    $fingerprint = \Bitpay\Util\Fingerprint::generate();
    $fingerprint = substr($fingerprint, 0, 24);

    //Make sure a token is set to in_use=='true' if one is not already in use
    $tokens_in_use = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE `facade` = '{$facade}' AND `in_use` = 'true'");
    $in_use = (($tokens_in_use) > 0) ? 'false' : 'true';

    //Setting values for database
    $data = array(
        'private_key' => $mcrypt_ext->encrypt(serialize($private), $fingerprint, '00000000'),
        'public_key'  => $mcrypt_ext->encrypt(serialize($public), $fingerprint, '00000000'),
        'sin'         => $sin,
        'token'       => $mcrypt_ext->encrypt(serialize($token), $fingerprint, '00000000'),
        'network'      => $network,
        'facade'      => $facade,
        'user_id'      => $user_ID,
        'enable_all'  => $enable_all,
        'in_use'      => $in_use,
        'created_at'  => current_time('mysql'),
    );

    //Check for update or post
    $wpdb->insert($table_name, $data);
}

function pair_and_get_token($pairing_code, $network)
{
    list($private, $public, $sin) = generate_keys();
    $client = create_client($network, $public, $private);
    $token = pairing($pairing_code, $client, $sin);
    save_keys($token, $network, $private, $public, $sin);
}

function form_bitpay()
{
    if (get_option('bitpay_error') != null) {
        $output = '<div style="color:#A94442;background-color:#F2DEDE;background-color:#EBCCD1;text-align:center;padding:15px;border:1px solid transparent;border-radius:4px">'.get_option('bitpay_error').'</div>';
        update_option('bitpay_error', null);
    }
    // Create table for BitPay Storage
    create_table();

    $mcrypt_ext = new \Bitpay\Crypto\McryptExtension();
    $fingerprint = \Bitpay\Util\Fingerprint::generate();
    $fingerprint = substr($fingerprint, 0, 24);

    //Load Script onto settings page
    $load_script = file_get_contents(plugins_url('/bitpay/assets/js/bitpay.js', __FILE__));
    $script = '<script type="text/javascript">'.$load_script.'</script>';
    echo $script;
    // Access to Wordpress Database
    global $wpdb;

    //Get Current user's ids
    $user_id = get_current_user_id();
    /**
     * Load the tokens
     **/
    //Load table storing the tokens
    $table_name = $wpdb->prefix.'bitpay_keys';
    //Load the tokens paired by the current user.
    $tablerows1 = $wpdb->get_results("SELECT * FROM {$table_name} WHERE `user_id` = {$user_id}");
    //Load the tokens paired by other users that are enabled for all.
    $tablerows2 = $wpdb->get_results("SELECT * FROM {$table_name} WHERE `enable_all` = 'true' AND `user_id` != {$user_id}");
    $tablerows = array_merge($tablerows1, $tablerows2);

    /**
     * Display the tokens on the settings page
     **/
    //Wrap the tokens in an accordion
    $row = '<div id="accordion">';
    //For each row on the bitpay_keys table
    foreach ($tablerows as $tablerow) {
        //Get the facade
        $facade = $tablerow->facade;
        //Get the network
        $network = $tablerow->network;
        //Get the token Object
        $token = unserialize($mcrypt_ext->decrypt($tablerow->token, $fingerprint, '00000000'));
        //Get the token id
        $token_id = $token->getToken();
        //Get visibility status to other users
        $enable_all = $tablerow->enable_all;
        //Get visibility status to other users
        $creator_id = $tablerow->user_id;

        //Enable_all status button
        $enable_for_all = $just_me = '';
        switch ($enable_all) {
            case 'true':
                $disable_for_all = '<button type="submit" name="just_me" value="'.$tablerow->id.'">Disable for All</button>';
                break;
            case 'false':
                $enable_for_all = '<button type="submit" name="enable_all" value="'.$tablerow->id.'">Enable for All</button>';
                break;
        }
        //in_use status button. If in_use the header is green.
        $in_use = $tablerow->in_use;
        switch ($in_use) {
            case 'true':
                $in_use = 'This token is being used.';
                $use_color = '<font color="#00FF00">●</font>';
                break;
            case 'false':
                $in_use = '<button type="submit" name="in_use" value="'.$tablerow->id.'">Use me</button>';
                $use_color = '<font color="#FF0000">●</font>';
                break;
        }

        //Revoke token button
        $revoke_token = '<button type="submit" id="revoke_key" name="revoke_key" onClick="var result = confirm('."'Are you sure you wish to revoke the key pair?'".'); return result;" value="'.$tablerow->id.'"><font color="red">Ø</font></button>';

        //People who did not set the token do not have control over the token
        if ($creator_id != $user_id) {
            //Can't Press Disable all button
            $enable_all = '';
            $revoke_token = '';
        }

        //Display the token info on the settings page
        $row .=
                '<h3>'.$facade.' - '.$network.' '.$use_color.'</h3>
                <div>
                <p>
                Facade: '.$facade.'<br />
                Network: '.$network.'<br />
                Label: WP Ecommerce - '.get_site_url().'<br />
                ID: '.$token_id.'<br />
                '.$enable_for_all.$disable_for_all.'
                <div align="right">'.$revoke_token.'</div>
                <br />'.$in_use.'
                </p>
                </div>';
    }
    $row .= '</div><br/>';

    $rows = array();

    if (count($tablerows) > 0) {
        $rows[] = array(
            'API Tokens<br /><img src="'.WPSC_URL.'/wpsc-merchants/bitpay/assets/img/logo.png" />',
            $row,
            '</div>
            <br /><input name="pairing_code" type="text" placeholder="Pairing Code" /><select name="network"><option value="Livenet">Live</option><option value="Testnet">Test</option></select><input id="generate_keys" type="submit" name="generate_keys" value="Generate" />',
        );
    } else {
        $rows[] = array(
            'Pairing Code',
            '<input name="pairing_code" type="text" placeholder="Pairing Code" /><select name="network"><option value="Livenet">Live</option><option value="Testnet">Test</option></select><input id="generate_keys" type="submit" name="generate_keys" onClick="var result = confirm('."'Are you sure you wish to pair keys?'".'); return result; "value="Generate" />',
            '<p class="description">Generate Keys for pairing. This will overwrite your current keys and you will have to pair again.</p>',
        );
    }

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
            '<select name="bitpay_transaction_speed"><option value="high" '.$sHigh.'>High</option><option value="medium" '.$sMedium.'>Medium</option><option value="low" '.$sLow.'>Low</option></select>',
            '<p class="description">Speed at which the Bitcoin payment registers as "confirmed" to the store: High = Instant, Medium = ~10m, Low = ~1hr (safest).<p>',
            );

    //Allows the merchant to specify a URL to redirect to upon the customer completing payment on the bitpay.com
    //invoice page. This is typcially the "Transaction Results" page.
    $rows[] = array(
            'Redirect URL',
            '<input name="bitpay_redirect" type="text" value="'.get_option('bitpay_redirect').'" />',
            '<p class="description"><strong>Important!</strong> Put the URL that you want the buyer to be redirected to after payment. This is usually a "Thanks for your order!" page.</p>',
            );

    $output .= '
    <tr>
        <td colspan="2">
            <p class="description">
                <img src="'.WPSC_URL.'/wpsc-merchants/bitpay/assets/img/bitcoin.png" /><br /><strong>Have more questions? Need assistance? Please visit our website <a href="https://bitpay.com" target="_blank">https://bitpay.com</a> or send an email to <a href="mailto:support@bitpay.com" target="_blank">support@bitpay.com</a> for prompt attention. Thank you for choosing BItPay!</strong>
            </p>
        </td>
    </tr>'."\n";

    foreach ($rows as $r) {
        $output .= '<tr> <td>'.$r[0].'</td> <td>'.$r[1];

        if (isset($r[2])) {
            $output .= $r[2];
        }

        $output .= '</td></tr>';
    }

    return $output;
}

function submit_bitpay()
{
    global $wpdb;

    //When Generate_Keys buttons is pressed
    if (isset($_POST["generate_keys"])) {
        //Validate the pairing code is a 7 character string with only letters and numbers
        if (preg_match('/^[a-zA-Z0-9]{7}$/', $_POST['pairing_code'])) {
            //Generate the Keys, Pair, and save to database
            pair_and_get_token($_POST['pairing_code'], $_POST['network']);
        } else {
            error_log("Invalid pairing code");
            update_option('bitpay_error', "Invalid Pairing Code");
        }
    }

    //When Revoke_key button is pressed
    if (isset($_POST["revoke_key"])) {
        //Delete the row that with id $_POST["revoke_key"]}
        $id = $_POST["revoke_key"];
        $facade = $wpdb->get_var("SELECT `facade` FROM {$wpdb->prefix}bitpay_keys WHERE `id` = {$id} LIMIT 1");
        $is_revoked_in_use = $wpdb->get_var("SELECT `in_use` FROM {$wpdb->prefix}bitpay_keys WHERE `id` = {$id} LIMIT 1");

        $wpdb->query("DELETE FROM {$wpdb->prefix}bitpay_keys WHERE `id` = {$id}");

        $tokens_in_use = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bitpay_keys WHERE `facade` = '{$facade}' AND `in_use` = 'true'");
        if ($is_revoked_in_use === 'false') {
            $in_use = 'false';
        } else {
            $in_use = (($tokens_in_use) > 0) ? 'false' : 'true';
        }
        $wpdb->query("UPDATE {$wpdb->prefix}bitpay_keys SET `in_use` = '{$in_use}' WHERE `facade` = '{$facade}' LIMIT 1");
    }

    //When the Disable for all button is pressed
    if (isset($_POST["just_me"])) {
        //Change enable_all to false where id is $_POST["just_me"]
        $id = $_POST["just_me"];
        $wpdb->query("UPDATE {$wpdb->prefix}bitpay_keys SET `enable_all` = 'false' WHERE `id` = {$id}");
    }
    //When the Enable for all button is pressed
    if (isset($_POST["enable_all"])) {
        //Change enable_all to true where id is $_POST["enable_all"]
        $id = $_POST["enable_all"];
        $wpdb->query("UPDATE {$wpdb->prefix}bitpay_keys SET `enable_all` = 'true' WHERE `id` = {$id}");
    }

    //When Use me button is pressed
    if (isset($_POST["in_use"])) {
        $id = $_POST["in_use"];
        //Get Facade from row with id $_POST["in_use"]
        $facade = $wpdb->get_var("SELECT `facade` FROM {$wpdb->prefix}bitpay_keys WHERE `id` = {$id}");
        //Set in_use to false where facade is $facade
        $wpdb->query("UPDATE {$wpdb->prefix}bitpay_keys SET `in_use` = 'false' WHERE `facade` = '{$facade}'");
        //Set in_use to true where id is $_POST["in_use"]
        $wpdb->query("UPDATE {$wpdb->prefix}bitpay_keys SET `in_use` = 'true' WHERE `id` = {$id}");
    }

    if (isset($_POST['submit']) && stristr($_POST['submit'], 'Update') !== false) {
        $params = array(
                'bitpay_transaction_speed',
                'bitpay_redirect',
                );

        foreach ($params as $p) {
            if ($_POST[$p] != null) {
                update_option($p, $_POST[$p]);
            } else {
                add_settings_error($p, 'error', __('The setting '.$p.' cannot be blank! Please enter a value for this field', 'wpse'), 'error');
            }
        }
    }

    return true;
}

function gateway_bitpay($seperator, $sessionid)
{
    global $wpdb, $wpsc_cart;
    $mcrypt_ext = new \Bitpay\Crypto\McryptExtension();
    $fingerprint = \Bitpay\Util\Fingerprint::generate();
    $fingerprint = substr($fingerprint, 0, 24);
    //Use token that is in_use and with facade = pos for generating invoices
    $is_a_token_paired = $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->prefix}bitpay_keys WHERE `in_use` = 'true' AND `facade` = 'pos' LIMIT 1");
    if ($is_a_token_paired < 1) {
        var_dump("Error Processing Transaction. Please try again later. If the problem persists, please contact us at ".get_option('admin_email'));
    } else {
        $row = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bitpay_keys WHERE `in_use` = 'true' AND `facade` = 'pos' LIMIT 1");
        $token = unserialize($mcrypt_ext->decrypt($row[0]->token, $fingerprint, '00000000'));
        $public_key = unserialize($mcrypt_ext->decrypt($row[0]->public_key, $fingerprint, '00000000'));
        $private_key = unserialize($mcrypt_ext->decrypt($row[0]->private_key, $fingerprint, '00000000'));
        $network = ($row[0]->network === 'Livenet') ? new \Bitpay\Network\Livenet() : new \Bitpay\Network\Testnet();

        $adapter = new \Bitpay\Client\Adapter\CurlAdapter();

        //This grabs the purchase log id from the database
        //that refers to the $sessionid
        $purchase_log = $wpdb->get_row(
            "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS.
            "` WHERE `sessionid`= ".$sessionid." LIMIT 1",
            ARRAY_A);

        //This grabs the users info using the $purchase_log
        // from the previous SQL quer
        $usersql =
            "SELECT `".WPSC_TABLE_SUBMITED_FORM_DATA."`.value,
            `".WPSC_TABLE_CHECKOUT_FORMS."`.`name`,
            `".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM
            `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN
            `".WPSC_TABLE_SUBMITED_FORM_DATA."` ON
            `".WPSC_TABLE_CHECKOUT_FORMS."`.id =
            `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE
            `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`='".$purchase_log['id']."'";

        $userinfo = $wpdb->get_results($usersql, ARRAY_A);

        // convert from awkward format
        $ui = array();
        foreach ((array) $userinfo as $value) {
            if (strlen($value['value'])) {
                $ui[$value['unique_name']] = $value['value'];
            }
        }

        $userinfo = $ui;

        /**
         * Create Buyer object that will be used later.
         */
        $buyer = new \Bitpay\Buyer();
        // name
        if (isset($userinfo['billingfirstname'])) {
            $buyer->setFirstName($userinfo['billingfirstname']);

            if (isset($userinfo['billinglastname'])) {
                $buyer->setLastName($userinfo['billinglastname']);
            }
        }

        // address -- remove newlines
        if (isset($userinfo['billingaddress'])) {
            $newline = strpos($userinfo['billingaddress'], "\n");
            $address2 = '';
            if ($newline !== FALSE) {
                $address_line1 = substr($userinfo['billingaddress'], 0, $newline);
                $address_line2 = substr($userinfo['billingaddress'], $newline + 1);
                $address_line2 = preg_replace('/\r\n/', ' ', $address_line2, -1, $count);
            } else {
                $address_line1 = $userinfo['billingaddress'];
            }
            $buyer->setAddress(
                array(
                    $address_line1,
                    $address_line2,
                )
            );
        }
        // state
        if (isset($userinfo['billingstate'])) {
            // check if State is a number code used when Selecting country as US
            if (ctype_digit($userinfo['billingstate'])) {
                $buyer->setState(wpsc_get_state_by_id($userinfo['billingstate'], 'code'));
            } else {
                $buyer->setState($userinfo['billingstate']);
            }
        }
        // country
        if (isset($userinfo['billingcountry'])) {
            $buyer->setCountry($userinfo['billingcountry']);
        }
        // city
        if (isset($userinfo['billingcity'])) {
            $buyer->setCity($userinfo['billingcity']);
        }
        // postal code
        if (isset($userinfo['billingpostcode'])) {
            $buyer->setZip($userinfo['billingpostcode']);
        }
        // email
        if (isset($userinfo['billingemail'])) {
            $buyer->setEmail($userinfo['billingemail']);
        }
        // phone
        if (isset($userinfo['billingphone'])) {
            $buyer->setPhone($userinfo['billingphone']);
        }

        // more user info
        foreach (array('billingphone' => 'buyerPhone', 'billingemail' => 'buyerEmail', 'billingcity' => 'buyerCity',  'billingcountry' => 'buyerCountry', 'billingpostcode' => 'buyerZip') as $f => $t) {
            if ($userinfo[$f]) {
                $options[$t] = $userinfo[$f];
            }
        }

        /**
         * Create an Item object that will be used later
         */
        $item = new \Bitpay\Item();

        // itemDesc, Sku, and Quantity
        if (count($wpsc_cart->cart_items) == 1) {
            $item_incart = $wpsc_cart->cart_items[0];
            $item_id = $item_incart->product_id;
            $item_sku = wpsc_product_sku($item_id);
            $item_description = $item_incart->product_name;
            if ($item_incart->quantity > 1) {
                $item_description = $item_incart->quantity.'x '.$item_description;
            }
        } else {
            foreach ($wpsc_cart->cart_items as $item_incart) {
                $quantity += $item_incart->quantity;
                $item_id = $item_incart->product_id;
                $item_sku_individual = wpsc_product_sku($item_id);
                $item_sku .= $item_incart->quantity.'x '.$item_sku_individual.' ';
            }
            $item_description = $quantity.' items';
        }

        // price
        $price = number_format($wpsc_cart->total_price, 2);
        $item
            ->setDescription($item_description)
            ->setCode($item_sku)
            ->setPrice($price);

        /**
         * Create the invoice
         */
        $invoice = new \Bitpay\Invoice();
        // Add the item to the invoice
        $invoice->setItem($item);
        // Add the buyers info to invoice
        $invoice->setBuyer($buyer);
        // Configure the rest of the invoice
        $purchase_log = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1", ARRAY_A);
        $invoice
            ->setOrderId($purchase_log['id'])
            // You will receive IPN's at this URL, Must be HTTPS
            ->setNotificationUrl(get_option('siteurl').'/?bitpay_callback=true');

        /**
         * BitPay offers services for many different currencies. You will need to
         * configure the currency in which you are selling products with.
         */
        //currency
        $currency = new \Bitpay\Currency();
        $currencyId = get_option('currency_type');
        $currency_code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id` = %d LIMIT 1", $currencyId));
        $currency->setCode($currency_code);

        // Set the invoice currency
        $invoice->setCurrency($currency);

        // Transaction Speed
        $invoice->setTransactionSpeed(get_option('bitpay_transaction_speed'));

        // Redirect URL
        if (get_option('permalink_structure') != '') {
            $separator = "?";
        } else {
            $separator = "&";
        }
        if (!is_null(get_option('bitpay_redirect'))) {
            $redirect_url = update_option('bitpay_redirect', get_site_url());
        } else {
            $redirect_url = get_option('bitpay_redirect').$separator.'sessionid='.$sessionid;
        }
        $invoice->setRedirectUrl($redirect_url);

        // PosData
        $invoice->setPosData($sessionid);

        // Full Notifications
        $invoice->setFullNotifications(true);

        /**
         * Create the client that will be used to send requests to BitPay's API
         */
        $client = new \Bitpay\Client\Client();
        $client->setAdapter($adapter);
        $client->setNetwork($network);
        $client->setPrivateKey($private_key);
        $client->setPublicKey($public_key);

        /**
         * You will need to set the token that was returned when you paired your
         * keys.
         */
        $client->setToken($token);

        // Send invoice
        try {
            $client->createInvoice($invoice);
        } catch (Exception $e) {
            debuglog($e->getMessage());
        }

        $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `notes`= 'The payment has not been received yet.' WHERE `sessionid`=".$sessionid;
        $wpdb->query($sql);
        $wpsc_cart->empty_cart();
        unset($_SESSION['WpscGatewayErrorMessage']);
        header('Location: '.$invoice->getUrl());

        exit();
    }
}

function bitpay_callback()
{
    if (isset($_GET['bitpay_callback'])) {
        global $wpdb;

        $post = file_get_contents("php://input");

        if (!$post) {
            return array('error' => 'No post data');
        }

        $json = json_decode($post, true);

        if (is_string($json)) {
            return array('error' => $json);
        }

        if (!array_key_exists('posData', $json)) {
            return array('error' => 'no posData');
        }

        if (!array_key_exists('id', $json)) {
            return 'Cannot find invoice ID';
        }

    //Don't trust parameters from the scary internet.
    //Use invoice ID from the $json in  getInvoice($invoice_id) and get status from that.
        $client = new \Bitpay\Client\Client();
        $adapter = new \Bitpay\Client\Adapter\CurlAdapter();
        if (strpos($json['url'], 'test') === false) {
            $network = new \Bitpay\Network\Livenet();
        } else {
            $network = new \Bitpay\Network\Testnet();
        }
        $client->setAdapter($adapter);
        $client->setNetwork($network);
        try {
            $response = $client->getInvoice($json['id']);
        } catch (MyException $e) {
            debuglog($response->getBody());
            debuglog($e->getMessage());
        }
        $sessionid = $response->getPosData();

        //get buyer email
        $sql          = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`=".$sessionid;
        $purchase_log = $wpdb->get_results($sql, ARRAY_A);

        $email_form_field = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1");
        $email            = $wpdb->get_var($wpdb->prepare("SELECT `value` FROM `".WPSC_TABLE_SUBMITTED_FORM_DATA."` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase_log[0]['id'], $email_form_field));

        //get cart contents
        $sql           = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`=".$purchase_log[0]['id'];
        $cart_contents = $wpdb->get_results($sql, ARRAY_A);

        //get currency symbol
        $currency_id     = get_option('currency_type');
        $sql             = "SELECT * FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`=".$currency_id;
        $currency_data   = $wpdb->get_results($sql, ARRAY_A);
        $currency_symbol = $currency_data[0]['symbol'];

        //list products and individual prices in the email
        $message_product = "\r\n\r\nTransaction Details: \r\n\r\n";

        $pnp      = 0.0;
        $subtotal = 0.0;

        foreach ($cart_contents as $product) {
            $pnp += $product['pnp']; //shipping for each item
            $message_product .= 'x'.$product['quantity'].' '.$product['name'].' - '.$currency_symbol.($product['price'] * $product['quantity'])."\r\n";
            $subtotal += $product['price']*$product['quantity'];
        }

        //list subtotal
        $subtotal         = number_format($subtotal, 2, '.', ',');
        $message_product .= "\r\n".'Subtotal: '.$currency_symbol.$subtotal."\r\n";

        //list total taxes and total shipping costs in the email
        $message_product .= 'Taxes: '.$currency_symbol.$purchase_log[0]['wpec_taxes_total']."\r\n";
        $message_product .= 'Shipping: '.$currency_symbol.($purchase_log[0]['base_shipping'] + $pnp)."\r\n\r\n";

        //display total price in the email
        $message_product .= 'Total Price: '.$currency_symbol.$purchase_log[0]['totalprice'];

        switch ($response->getStatus()) {
            //For low and medium transaction speeds, the order status is set to "Order Received". The customer receives
            //an initial email stating that the transaction has been paid.
            case 'paid':
                if (is_numeric($sessionid)) {
                    $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '2' WHERE `sessionid`=".$sessionid;
                    $wpdb->query($sql);

                    $message  = 'Thank you! Your payment has been received, but the transaction has not been confirmed on the bitcoin network. You will receive another email when the transaction has been confirmed.';
                    $message .= $message_product;

                    $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `notes`= 'The payment has been received, but the transaction has not been confirmed on the bitcoin network. This will be updated when the transaction has been confirmed.' WHERE `sessionid`=".$sessionid;
                    $wpdb->query($sql);

                    if (wp_mail($email, 'Payment Received', $message)) {
                        $mail_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `email_sent`= '1' WHERE `sessionid`=".$sessionid;
                        $wpdb->query($mail_sql);
                    }

                    transaction_results($sessionid, false);    //false because this is just for email notification
                }

                break;

            //For low and medium transaction speeds, the order status will not change. For high transaction speed, the order
            //status is set to "Order Received" here. For all speeds, an email will be sent stating that the transaction has
            //been confirmed.
            case 'confirmed':
                if (is_numeric($sessionid)) {
                    $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '2' WHERE `sessionid`=".$sessionid;
                    $wpdb->query($sql);
                    $mail_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `email_sent`= '1' WHERE `sessionid`=".$sessionid;
                    //display initial "thank you" if transaction speed is high, as the 'paid' status is skipped on high speed
                    if (get_option('bitpay_transaction_speed') == 'high') {
                        $message  = 'Thank you! Your payment has been received, and the transaction has been confirmed on the bitcoin network. You will receive another email when the transaction is complete.';
                        $message .= $message_product;
                        $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `notes`= 'The payment has been received, and the transaction has been confirmed on the bitcoin network. This will be updated when the transaction has been completed.' WHERE `sessionid`=".$sessionid;
                        $wpdb->query($sql);
                        if (wp_mail($email, 'Payment Received', $message)) {
                            $wpdb->query($mail_sql);
                        }
                    } else {
                        $message = 'Your transaction has now been confirmed on the bitcoin network. You will receive another email when the transaction is complete.';
                        $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `notes`= 'The payment has been received, and the transaction has been confirmed on the bitcoin network. This will be updated when the transaction has been completed.' WHERE `sessionid`=".$sessionid;
                        $wpdb->query($sql);
                        if (wp_mail($email, 'Transaction Confirmed', $message)) {
                            $wpdb->query($mail_sql);
                        }
                    }

                    transaction_results($sessionid, false); //false because this is just for email notification
                }
                break;

            //The purchase receipt email is sent upon the invoice status changing to "complete", and the order
            //status is changed to Accepted Payment
            case 'complete':
                if (is_numeric($sessionid)) {
                    $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '3' WHERE `sessionid`=".$sessionid;
                    $wpdb->query($sql);

                    $message = 'Your transaction is now complete! Thank you for using BitPay!';
                    $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `notes`= 'The transaction is now complete.' WHERE `sessionid`=".$sessionid;
                    $wpdb->query($sql);

                    if (wp_mail($email, 'Transaction Complete', $message)) {
                        $mail_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `email_sent`= '1' WHERE `sessionid`=".$sessionid;
                        $wpdb->query($mail_sql);
                    }

                    transaction_results($sessionid, false); //false because this is just for email notification
                }
                break;
        }
    }
}

add_action('init', 'bitpay_callback');
