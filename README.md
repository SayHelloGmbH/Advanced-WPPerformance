# Advanced WPPerformance

## Description
This plugin adds several performance improvements to your WordPress site. In contrst to other performance Plugins, this one sets focus on HTTP\2 Standards (like Server Push and SPDY).
### Moves all scripts to footer
It moves all scripts to the footer and adds a `defer` attribute. This makes sure the scripts won't block the page render process but will still be executed in the right order. 

**Caution:** Could break some inline JS.
### minify assets
This plugin minifies all CSS and JS Files and caches them. It will **not** merge them into on file. This way you are still able to use conditional assets and if you are using HTTP/2, which I highly recommend, it's not necessary to do so.

**Filter** `awpp_cache_dir`:
```php
add_filter( 'awpp_cache_dir', 'prefix_my_cache_dir' );
function prefix_my_cache_dir( $path ) {
    return ABSPATH . 'assets/';
}
```
### Critical CSS / LoadCSS
All CSS Files will be removed from the head and loaded asynchronously using `rel="preload"` (`loadCSS` as Fallback). This makes sure your CSS Files won't delay the page rendering. To reduce the flash of unstyled content (FOUT) I recommend adding a Critical CSS.
#### conditonal Critical CSS
By default this plugin provides a textarea where you can put your critical CSS.
**But there's more!** You can use a filter `awpp_critical_dir` where you can define your own critical CSS Folder:

**Filter** `awpp_critical_dir`:
```php
add_filter( 'awpp_critical_dir', 'prefix_my_critical_dir' );
function prefix_my_critical_dir( $path ) {
    return get_template_directory() . '/assets/critical/';
}
```
Inside this directory you can define your own conditional critical CSS files which are loaded from right (the least specific) to left (the most specific). Like in the WordPress Hierarchy, the `index.css` is set as the basic file.
```
index.css
| singular.css
| | singular-{$post_type}.css
| | | singular-{$post_id}.css
| archive.css
| | archive-{$post_type}.css
| | archive-author.css
| | | archive-author-{$author_name}.css
| | archive-date.css
| | | archive-date-year.css
| | | archive-date-month.css
| | | archive-date-day.css
| | archvie-taxonomy.css
| | | archvie-taxonomy-{$taxonomy}.css
| | | | archvie-taxonomy-{$term_id}.css
| front-page.css
| 404.css
| search.css
```
The idea behind this option is that you could just create a bunch of critical CSS Files and put them into your Theme. The Plugin will automaticly look for the most explict file and sets this as your critical CSS.

There are several ways to generate Critical CSS. I recommend creating it while developing your theme. For example using [NPM/Gulp](https://github.com/addyosmani/critical).

But we're already working on a way better solution. **More soon..**

## HTTP/2 Server Push
Server push is a HTTP/2 feature that allows you to send site assets to the user before they’ve even asked for them.
There are two ways to achieve this. Both have their pros and cons. So this plugin supports both, the decision is up to you.

### PHP
While WordPress builds your site, this plugin gets all enqueued scripts and styles and adds them as a link attribute to the response headers. That way you can be certain only files are being pushed, that are actually needed.

**But:** Since they are set while the server builds your site, this won't work if you're using a server caching (which I highly recommend).

### .htaccess
The second option puts all files to push inside you .htaccess. This way they are being pushed also if you're using server caching.

**But:** If your assets change (new versions / depreciated scripts), don't forget to update the .htaccess. This can be done with one click while saving the settings.

## Changelog

### 1.2.1
* little Bugfixes

### 1.2.0
* added support for Cachify, W3 Total Cache, WP Super Cache and WP Rocket
* `rel="preload"` for CSS Files, `loadCSS` as Fallback
* little improvements

### 1.1.1
* little Bugfixes

### 1.1.0
* **Added HTTP/2 Sever Push**
    * Server Push php: pushes all enqueued scripts and styles as php headers
    * Server Push .htaccess: it scans your front-page so you gan choose which assets should be pushed within your .htaccess
* added HTTP version check
* changed default directories to `wp-content/cache/awpp/`
* small improvements

### 1.0.0
* little Bugfixes

### 0.0.1
* Initial version from 2017
    * Moves all scripts to footer
    * defer scripts
    * minify scripts
    * Critical CSS / LoadCSS

## Contributors
* Nico Martin | [nicomartin.ch](https://www.nicomartin.ch) | [@nic_o_martin](https://twitter.com/nic_o_martin)

## License
Use this code freely, widely and for free. Provision of this code provides and implies no guarantee.

Please respect the GPL v3 licence, which is available via [http://www.gnu.org/licenses/gpl-3.0.html](http://www.gnu.org/licenses/gpl-3.0.html)