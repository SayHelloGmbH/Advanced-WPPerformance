# Advanced WPPerformance

## Description
This plugin add several performance improvements to your WordPress site.
### Moves all scripts to footer
### defer scripts
### minify scripts
### Critical CSS / LoadCSS
#### conditonal Critical CSS
By default this plugin provides a textarea where you can put your critical CSS.
**But there's more!** You can use a filter `awpp_critical_dir` where you can define your own critical CSS Folder:
```php
add_filter('awpp_critical_dir', function(){
    return get_template_directory() . '/assets/critical/';
});
```
Inside this directory you ca define your own conditional critical CSS files which are loaded from right (the least specific) to left (the most specific). Like in the WordPress Hierarchy, the `index.css` is set as the basic file.
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

## Changelog

### 0.0.1
* Initial version from 2017
    * Moves all scripts to footer
    * defer scripts
    * minify scripts
    * Critical CSS / LoadCSS

## Contributors
* Nico Martin | [nicomartin.ch](https://www.nicomartin.ch)

## License
