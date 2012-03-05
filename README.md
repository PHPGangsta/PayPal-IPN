Paypal Instant Payment Notification PHP class
=====================

* Copyright (c) 2012, [http://www.phpgangsta.de](http://www.phpgangsta.de)
* Author: Michael Kliewe, [@PHPGangsta](http://twitter.com/PHPGangsta)
* Licensed under the BSD License.


This PHP class acts as an endpoint to PayPals Instant Payment Notification (IPN). This is a HTTP request PayPal can send
to your webserver whenever money arrives on your PayPal account. You can then automatically set database entries in your
shop, send emails to your customer ("thanks for your money") and process the receiving of money.

Usage:
------

You should use this class in the PayPal Sandbox Environment first: https://developer.paypal.com
Register, create seller and buyer accounts, set IPN in seller account, and send some money from buyer to seller.

See example files

ToDo:
-----
- ??? What do you need?

Notes:
------
If you like this script or have some features to add: contact me, visit my blog, fork this project, send pull requests, you know how it works.