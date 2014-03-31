<strong>©2012-2014 BITPAY, INC.</strong>

Permission is hereby granted to any person obtaining a copy of this software
and associated documentation for use and/or modification in association with
the bitpay.com service.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


bitpayWordpress
===============

WordPress e-commerce plugin

1. Install the Wordpress E-Commerce plugin from getshopped.org. This payment plugin requires the base E-Commerce plugin to function.
2. Don't attempt to install this BitPay plugin through the Wordpress plugins control panel.  Extract the contents of this zip file into your Wordpress folder.  You can also extract the zip file on another computer and upload the files.
3. Now log into the Wordpress admin panel, click Settings > Store > Payments
4. Check the Bitcoins payment option to activate it and click Update below.
5. Click edit next to the Bitcoins payment option.
    * Edit Display Name if desired.
    * Create an API key at bitpay.com if you haven't already and set API Key to this value.
    * Change the Transaction Speed if desired (see information about this in the API documentation at bitpay.com).
    * Input a URL to redirect customers to after they have paid the invoice (Transaction Results page, Your Account page, etc.)
    * Click Update below.


Version
-------
- Tested against plugin version 3.8.12.1
- Added new HTTP header for version tracking
