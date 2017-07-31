# Advanced WPPerformance

## Description
This plugin add several performance improvements to your WordPress site.
### Moves all scripts to footer
It moves all scripts to the footer and adds a `defer` attribute. This makes sure the scripts woun't block the page render process but will stil be executed in the right order. 

**Caution:** Could break some inline JS.
### minify assets
This plugin minifies all CSS and JS Files and caches them. It will **not** concenate them. This way you are still able to use conditional Assets and if you are using HTTP/2, which I highly recommend, it's not necessary to do so.

**Filter** `awpp_cache_dir`:
```php
add_filter( 'awpp_cache_dir', 'prefix_my_cache_dir' );
function prefix_my_cache_dir( $path ) {
    return ABSPATH . 'assets/';
}
```
### Critical CSS / LoadCSS
All CSS Files will be removed from the head and loaded asynchronously. This makes sure your CSS Files woun't delay the page rendering. To reduce the flash of unstyled content (FOUT) I recommend adding a Critical CSS.
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
| | archvie-taxonomy-{$taxonomy}.css
| | archvie-taxonomy-{$term_id}.css
| front-page.css
| 404.css
| search.css
```
The idea behind this option is that you could just create a bunch of critical CSS Files and put them into your Theme. The Plugin will automaticly look for the most explict file and sets this as your critical CSS.

There are several ways to generate Critical CSS. If you are a theme developer. I use an [NPM module](https://github.com/addyosmani/critical) to extract critical CSS while I'm developing the theme. But we're already working on a way better solution. **More soon..**

## Changelog

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