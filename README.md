bitpay/wordpress-ecommerce-plugin
===================================

# Installation

WordPress e-Commerce plugin

1. Install the WordPress e-Commerce plugin from www.getshopped.org. This payment method requires the WP e-Commerce plugin to function because this BitPay file is an extension to the e-Commerce plugin.
2. Don't attempt to install this BitPay file through the WordPress plugins control panel.  Extract two contents of this zip file (bitpay folder and bitpay.merchant.php) into your WordPress folder (wp-content/plugins/wp-e-commerce/wpsc-merchants).  You can also extract the zip file on another computer and upload the files.

# Configuration

1. Log into the WordPress admin panel, click Settings > Store > Payments (assuming you've already installed WP e-Commerce plugin).
2. Check the BitPay payment option to activate it and click Update below.
3. Click Settings below the BitPay payment option.
    * Edit Display Name if desired.
    * Create an API key at bitpay.com if you haven't already and set API Key to this value.
    * Change the Transaction Speed if desired (see information about this in the API documentation at https://bitpay.com/downloads/bitpayApi.pdf).
    * Input a URL to redirect customers after they have paid the invoice (Transaction Results page, Your Account page, etc.)
    * Click Update below.

# Support

## BitPay Support
* [Github Issues](https://github.com/bitpay/wordpress-ecommerce-plugin/issues)
  * Open an Issue if you are having issues with this plugin
* [Support](https://support.bitpay.com/)
  * Checkout the BitPay support site

## WordPress E-Commerce Plugin Support
* [Homepage](https://wordpress.org/plugins/wp-e-commerce/)
* [Documentation](http://docs.getshopped.org)
* [Forums](http://wordpress.org/support/plugin/wp-e-commerce)


# Troubleshooting

The latest version of this plugin can always be downloaded from the official BitPay
repository located here:  https://github.com/bitpay/wordpress-ecommerce-plugin

1. Ensure a valid SSL certificate is installed on your server. Also ensure your root CA cert is updated. If your CA cert is not current, you will see curl SSL verification errors.
2. Verify that your web server is not blocking POSTs from servers it may not recognize. Double check this on your firewall as well, if one is being used.
3. Check the system error log file (usually the web server error log) for any errors during BitPay payment attempts. If you contact BitPay support, they will ask to see the log file to help diagnose the problem.
4. Check the version of this plugin against the official plugin repository to ensure you are using the latest version. Your issue might have been addressed in a newer version!
5. If all else fails, send an email describing your issue *in detail* to support@bitpay.com

NOTE: When contacting support it will help us if you provide:
* Wordpress Version
* WP e-Commerce Version
* Other plugins you have installed

# Contribute

To contribute to this project, please fork and submit a pull request.

# License

The MIT License (MIT)

Copyright (c) 2011-2014 BitPay

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
