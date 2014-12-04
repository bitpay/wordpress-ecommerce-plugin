bitpay/wordpress-ecommerce-plugin
========================

![Build Status](https://travis-ci.org/aleitner/wordpress-ecommerce-plugin-v2.svg?branch=master)

# Installation of .zip file downloaded from bitpay.com

WordPress e-Commerce plugin

- Install the WordPress e-Commerce plugin from www.getshopped.org. This payment method requires the WP e-Commerce plugin to function because this BitPay file is an extension to the e-Commerce plugin.
- Don't attempt to install this BitPay file through the WordPress plugins control panel. 
- Extract the contents of the zip file.
- Copy the wpsc-merchants folder and paste it into your active WP eCommerce instance (ie `/var/www/html/wordpress/wp-content/plugins/wp-e-commerce`).

# Installation From GitHub

WordPress e-Commerce plugin

- Install the WordPress e-Commerce plugin from www.getshopped.org. This payment method requires the WP e-Commerce plugin to function because this BitPay file is an extension to the e-Commerce plugin.
- Don't attempt to install this BitPay file through the WordPress plugins control panel. 
- Clone this repository anywhere onto your server.
- Open up your Terminal
- Change directory into the repository folder that was cloned.
- Once you are inside the repository folder input ./setup username password
- Replace username with your mysql database username that has access to all tables and replace password with the database password.
- Once you run this a folder should show up called wpsc-merchants in the repository directory.
- Copy and Paste wpsc-merchants into your active WP eCommerce instance (ie `/var/www/html/wordpress/wp-content/plugins/wp-e-commerce`).

# Configuration

* Log into the WordPress admin panel, click Settings > Store > Payments (assuming you've already installed WP e-Commerce plugin).

* Check the BitPay payment option to activate it and click Save Changes below.

![BTC Invoice](https://raw.githubusercontent.com/aleitner/aleitner.github.io/master/wordpress-ecommerce-plugin-v2/images/Screen%20Shot%202014-11-17%20at%201.03.18%20PM.png)

* Click Settings below the BitPay payment option.

* Edit Display Name if desired.

![BTC Invoice](https://raw.githubusercontent.com/aleitner/aleitner.github.io/master/wordpress-ecommerce-plugin-v2/images/Screen%20Shot%202014-11-17%20at%201.07.14%20PM.png)

* Create a Pairing Code at bitpay.com if you haven't already and set Pairing Code to this value.
  * If you are using a code from **bitpay.com** set the dropdown next to Pairing Code input to **Live**.
  * If you are using a pairing code from **test.bitpay.com** set the dropdown next to the Pairing Code input to **Test**.
* Click Generate to pair with BitPay and create a token for doing transactions.

![BTC Invoice](https://raw.githubusercontent.com/aleitner/aleitner.github.io/master/wordpress-ecommerce-plugin-v2/images/Screen%20Shot%202014-11-17%20at%201.05.54%20PM.png)

* You should now see a paired token in your settings. 

![BTC Invoice](https://raw.githubusercontent.com/aleitner/aleitner.github.io/master/wordpress-ecommerce-plugin-v2/images/Screen%20Shot%202014-11-17%20at%201.06.23%20PM.png)

* Change the Transaction Speed if desired (see information about this in the API documentation at https://bitpay.com/downloads/bitpayApi.pdf).

![BTC Invoice](https://raw.githubusercontent.com/aleitner/aleitner.github.io/master/wordpress-ecommerce-plugin-v2/images/Screen%20Shot%202014-11-17%20at%201.06.38%20PM.png)

* Input a URL to redirect customers after they have paid the invoice (Transaction Results page, Your Account page, etc.)

![BTC Invoice](https://raw.githubusercontent.com/aleitner/aleitner.github.io/master/wordpress-ecommerce-plugin-v2/images/Screen%20Shot%202014-11-17%20at%201.07.07%20PM.png)

* Click Update below.

# Usage

- Once the configuration is done, whenever a buyer selects Bitcoins as their payment method an invoice is generated at bitpay.com
- The Enable for All button allows other admins on your site to see and edit the token you paired.
- The Disable for All button makes the token pair invisible to all users except for the one who paired the token.

# Support

## BitPay Support

* [GitHub Issues](https://github.com/bitpay/wordpress-ecommerce-plugin/issues)
  * Open an issue if you are having issues with this plugin.
* [Support](https://support.bitpay.com)
  * BitPay merchant support documentation

## WP e-Commerce Support

* [Homepage](http://getshopped.org/)
* [Documentation](http://docs.getshopped.org/)
* [Support Forums](https://wordpress.org/support/plugin/wp-e-commerce)
)
# Troubleshooting

The latest version of this plugin can always be downloaded from the official BitPay repository located here: https://github.com/bitpay/wordpress-ecommerce-plugin

Ensure a valid SSL certificate is installed on your server. Also ensure your root CA cert is updated. If your CA cert is not current, you will see curl SSL verification errors.
Verify that your web server is not blocking POSTs from servers it may not recognize. Double check this on your firewall as well, if one is being used.
Check the system error log file (usually the web server error log) for any errors during BitPay payment attempts. If you contact BitPay support, they will ask to see the log file to help diagnose the problem.
Check the version of this plugin against the official plugin repository to ensure you are using the latest version. Your issue might have been addressed in a newer version!
If all else fails, send an email describing your issue in detail to support@bitpay.com
NOTE: When contacting support it will help us if you provide:

Wordpress Version
WP e-Commerce Version
Other plugins you have installed

# Contribute

To contribute to this project, please fork and submit a pull request.

# License

The MIT License (MIT)

Copyright (c) 2011-2014 BitPay

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
