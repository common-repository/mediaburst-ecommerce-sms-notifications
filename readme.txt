=== WP e-Commerce - Clockwork SMS ===
Author: Clockwork
Website: http://www.clockworksms.com/platforms/wordpress/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wpecommerce
Contributors: mediaburst, martinsteel, mediaburstjohnc
Tags: SMS, Clockwork, Clockwork SMS, Mediaburst, WP e-Commerce, WP eCommerce, eCommerce, e-Commerce, GetShopped, WPSC, Text Message
Text Domain: wpecommerce_sms
Requires at least: 3.0.0
Tested up to: 3.8.0
Stable tag: 2.4.3

SMS notifications to your WP e-Commerce customers when order statuses change, send yourself an SMS when you get a new order.

== Description ==

Send SMS notifications to your WP e-Commerce customers when order statuses change, and send store administrators an SMS when you get a new order.

You can also send a quick SMS update to your customer from the order status screen.

The plugin works with Clockwork which means you can send SMS notifications to anyone, anywhere in the world. Once you've installed the plugin you'll need a username and password for a [Clockwork API account](http://www.clockworksms.com/platforms/wordpress/?utm_source=wordpress&utm_medium=plugin&utm_campaign=woocommerce), you can signup online and text messages cost just 5p.

== Installation ==

1. Upload `wp9ecommerce-sms-notifications` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter your Clockwork API key in the 'Clockwork Options' page under 'Clockwork SMS'
4. Setup your WP e-Commerce options on the 'WP e-Commerce' page under 'Clockwork SMS'

== Upgrade Notice ==
= 2.4.3 =
* Remove old branding

= 2.4.2 =
* Security Hardening

= 2.4.0 =
* Fix XSS Vulnerability

= 2.0.5 =

* WordPress 3.8 compatibility.

= 2.0.4 =

* Added Clockwork "test" options

= 2.0.3 =

* Uses global Clockwork option for 'From' address.

== Changelog ==

= 2.0.3 =

* Uses global Clockwork option for 'From' address.

= 2.0.1 / 2.0.2 =

* Fixes an issue with store names that are too long causing an API error.

= 2.0 =

* Update to work with the new Clockwork API.

= 1.1 =

* BUGFIX: Some characters were causing the SMS functionality to break quite seriously, this is now fixed ([more information](https://github.com/mediaburst/php-mediaburst-sms/commit/575637d091ba13349f2b6569ac78de217ed18fc3))
* NEW: Allow custom messages for each order status, i.e. you can now have different messages for on-hold, processing, cancelled, etc, statuses.
* NEW: You can send your customer an SMS message from the order page, simply select "Send as SMS" when adding an Order Note.
* Various other "under the hood" tweaks

= 1.0 =

* Initial release
