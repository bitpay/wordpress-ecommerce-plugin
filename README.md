<strong>©2012-2014 BITPAY, INC.</strong>

The MIT License (MIT)

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


BitPay with WordPress
=====================

WordPress e-Commerce plugin

1. Install the WordPress e-Commerce plugin from www.getshopped.org. This payment method requires WP e-Commerce plugin to function because this BitPay file is extension to e-Commerce plugin.
2. Don't attempt to install this BitPay file through the WordPress plugins control panel.  Extract two contents of this zip file (bitpay folder and bitpay.merchant.php) into your WordPress folder (wp-content/plugins/wp-e-commerce/wpsc-merchants).  You can also extract the zip file on another computer and upload the files.
3. Now log into the WordPress admin panel, click Settings > Store > Payments (assuming you've already installed WP e-Commerce plugin).
4. Check the BitPay payment option to activate it and click Update below.
5. Click Settings below the BitPay payment option.
    * Edit Display Name if desired.
    * Create an API key at bitpay.com if you haven't already and set API Key to this value.
    * Change the Transaction Speed if desired (see information about this in the API documentation at bitpay.com).
    * Input a URL to redirect customers to after they have paid the invoice (Transaction Results page, Your Account page, etc.)
    * Click Update below.


Version
-------
-Tested against WP e-Commerce plugin version 3.8.13.3
-Tested against Wordpress version 3.9.1 and WP e-Commerce plugin version 3.8.14.1
