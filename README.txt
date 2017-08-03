=== Advanced WPPerformance ===
Contributors: nico_martin
Donate link: https://www.paypal.me/NicoMartin
Tags: Performance, Pagespeed, scriptloading, autoptimize
Requires at least: 4.7
Tested up to: 4.9
Stable tag: 1.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

This plugin adds several performance improvements to your WordPress site.

= Moves all scripts to footer =

It moves all scripts to the footer and adds a `defer` attribute. This makes sure the scripts won't block the page render process but will still be executed in the right order.
In some cases, this could break inline JavaScript

= minify assets =

This plugin minifies all CSS and JS Files and caches them. It will **not** merge them into on file. This way you are still able to use conditional assets and if you are using HTTP/2, which I highly recommend, it's not necessary to do so.

= Critical CSS / LoadCSS =

All CSS Files will be removed from the head and loaded asynchronously. This makes sure your CSS Files won't delay the page rendering. To reduce the flash of unstyled content (FOUT) I recommend adding a Critical CSS.

**conditonal Critical CSS**

By default this plugin provides a textarea where you can put your critical CSS.
Read more about [Conditional critical CSS](https://github.com/nico-martin/Advanced-WPPerformance#conditonal-critical-css)

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

= 1.0.0 =
* Stable version

= 0.0.1 =
* Initial version.
    * Moves all scripts to footer
    * defer scripts
    * minify scripts
    * Critical CSS / LoadCSS
    * Conditional Critical CSS
