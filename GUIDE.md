# Using the BitPay plugin for WordPress (WP) eCommerce

## Prerequisites

* Last Cart Version Tested: Wordpress 4.8.1 WP e-commerce 3.12.2

You must have a BitPay merchant account to use this plugin.  It's free to [sign-up for a BitPay merchant account](https://bitpay.com/start).


## Installation of Wordpress eCommerce Plugin

- Download WP eCommerce plugin from the WordPress Plugin Directory: https://wordpress.org/plugins/wp-e-commerce/. 
- Extract the contents of the zip file to the [wordpress main directory]/wp-content/plugins/ directory.
- Log in to your Wordpress and navigate to the Admin dashboard -> Plugins -> Installed Plugins
- Activate WP eCommerce plugin

## Installation of the BitPay plugin
- Download bitpay.zip from https://github.com/bitpay/wordpress-ecommerce-plugin/releases/latest
- Copy the wpsc-merchants folder into your Wordpress folder

## Installation by cloning the repo
If you want to clone and compile your own plugin, please use the follow commands:
- Clone the repo:

```bash
$ git clone https://github.com/bitpay/wordpress-ecommerce-plugin
$ cd wordpress-ecommerce-plugin
```
Install the dependencies:
```bash
$ curl -sS https://getcomposer.org/installer | php
$ ./composer.phar install
```
Build BitPay Plugin Directory:
```bash
$ ./setup
```
Copy plugin to the final Directory In Wordpress (change path to your Wordpress instance):
```bash
$ cd wpsc-merchants
$ cp -r * /var/www/wordpress/wp-content/plugins/wp-e-commerce/wpsc-merchants
```

## Configuration

* Log into the WordPress admin panel, click Settings > Store > Payments (assuming you've already installed WP eCommerce plugin).

* Check the BitPay payment option to activate it and click Save Changes below.

* Click Settings below the BitPay payment option.

* Edit Display Name if desired.

### Pairing code
* Create a Pairing Code at bitpay.com if you haven't already and set Pairing Code to this value.
  * If you are using a code from **bitpay.com** set the dropdown next to Pairing Code input to **Live**.
  * If you are using a pairing code from **test.bitpay.com** set the dropdown next to the Pairing Code input to **Test**.
* Click Generate to pair with BitPay and create a token for doing transactions.

* You should now see a paired token in your settings. 

### Transaction speed

* Change the Transaction Speed if desired.  Can be `high`, `medium`, or `low`.  HIGH speed confirmations typically take 5-10 seconds, and can be used for digital goods or low-risk items. LOW speed confirmations take about 1 hour, and should be used for high-value items.

### Redirect URL

* Input a URL to redirect customers after they have paid the invoice (Transaction Results page, Your Account page, etc.)

### Save your settings

* Click Update below.

## Usage

- Once the configuration is done, whenever a buyer selects Bitcoins as their payment method an invoice is generated at bitpay.com
- The Enable for All button allows other admins on your site to see and edit the token you paired.
- The Disable for All button makes the token pair invisible to all users except for the one who paired the token.
