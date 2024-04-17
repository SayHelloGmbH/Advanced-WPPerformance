=== Advanced WPPerformance ===
Contributors: nico_martin
Donate link: https://www.paypal.me/NicoMartin
Tags: Performance, Pagespeed, scriptloading, optimize, http2, server push, SPDY, preload, Critical CSS, Critical CSS API
Requires at least: 4.7
Tested up to: 6.5.2
Stable tag: 1.6.5
Requires PHP: 7.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

This plugin adds several performance improvements to your WordPress site. In contrst to other performance Plugins, this one sets focus on HTTP\2 Standards (like Server Push and SPDY).
= minify assets =

This plugin minifies all CSS and JS Files and caches them. It will **not** merge them into on file. This way you are still able to use conditional assets and if you are using HTTP/2, which I highly recommend, it's not necessary to do so.

= Optimizes JS Delivery =

It moves all scripts to the footer and adds a `defer` attribute. This makes sure the scripts won't block the page render process but will still be executed in the right order.
In some cases, this could break inline JavaScript

= Optimizes CSS Delivery =

All CSS Files will be removed from the head and loaded asynchronously. This makes sure your CSS Files won't delay the page rendering. To reduce the flash of unstyled content (FOUT) I recommend adding a Critical CSS.

**conditonal Critical CSS**

By default this plugin provides a textarea where you can put your critical CSS.
Read more about [Conditional critical CSS](https://github.com/nico-martin/Advanced-WPPerformance#conditonal-critical-css)

**Critical CSS API**

We implemented an awesome new feature!
Read more about the [Critical CSS API](https://github.com/nico-martin/Advanced-WPPerformance#critical-css-api).

= HTTP/2 Server Push =

Server push is a HTTP/2 feature that allows you to send site assets to the user before they’ve even asked for them.
There are two ways to achieve this. Both have their pros and cons. So this plugin supports both, the decision is up to you.

**PHP**

While WordPress builds your site, this plugin gets all enqueued scripts and styles and adds them as a link attribute to the response headers. That way you can be certain only files are being pushed, that are actually needed.

But: Since they are set while the server builds your site, this won't work if you're using a server caching (which I highly recommend).

**.htaccess**

The second option puts all files to push inside you .htaccess. This way they are being pushed also if you're using server caching.

But: If your assets change (new versions / depreciated scripts), don't forget to update the .htaccess. This can be done with one click while saving the settings.

== Screenshots ==

1. Test Critical CSS right from the Admin bar.
2. Super easy to configure
3. Powerfull to extend

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory or install it from the plugin directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Done. You can still deactivate options from Settings -> Advanced WPPerformance

== Frequently Asked Questions ==

= No questions yet =

There are no at the moment. Please use the support forum. I'll update this section as soon as there are actually some FAQs.

== Contribute ==

A development version of this plugin is hosted on github. If you have some ideas for improvements, feel free to dive into the code:
[https://github.com/nico-martin/Advanced-WPPerformance](https://github.com/nico-martin/Advanced-WPPerformance)

== Changelog ==

### 1.6.5
* PHP 8.x compatibility

### 1.6.2
* minor bugfix with WPML

### 1.6.1
* changed to standalone cssrelpreload.js

### 1.6.0
* Added multisite support

### 1.5.3
* Fixed Bug while save/post and CriticalAPI enabled

### 1.5.2
* quickfix

### 1.5.1
* http/2 check improvements
* fix: serverpush settings action

### 1.5
* updated DEFLATE compression
* updated chaching headers
* NEW - hidden beta Feature: Critical CSS API

### 1.4
* added DEFLATE compression
* added chaching headers
* added German
* little Bugfixes

### 1.3
* complete UI rework
* added one-click speed tests
* better documentation
* little Bugfixes

### 1.2.1
* little Bugfixes

### 1.2.0
* added support for Cachify, W3 Total Cache, WP Super Cache and WP Rocket
* `rel="preload"` for CSS Files, `loadCSS` as Fallback
* little improvements

### 1.1.1
* little Bugfixes

### 1.1.0
* Added HTTP/2 Sever Push
    * Server Push php: pushes all enqueued scripts and styles as php headers
    * Server Push .htaccess: it scans your front-page so you gan choose which assets should be pushed within your .htaccess
* added HTTP version check
* changed default directories to `wp-content/cache/awpp/`
* small improvements

= 1.0.0 =
* Stable version
* little Bugfixes

= 0.0.1 =
* Initial version.
    * Moves all scripts to footer
    * defer scripts
    * minify scripts
    * Critical CSS / LoadCSS
    * Conditional Critical CSS
