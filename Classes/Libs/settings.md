This class adds an OOP settings pages wrapper.

## Set-up
**Init**:
```php
require_once 'Path/To/class-settings.php';
function my_settings() {
    return nicomartin\Settings::get_instance( 'awpp' );
}
```
**Setters**
```php
my_settings()->set_parent_page( 'settings_parent_slug' ); // default: options-general.php
my_settings()->set_capability( 'administrator' ); // default: administrator
```
**Register**
```php
add_action( '{key}_settings', 'my_register_settings' );
function my_register_settings() {
    $page       = my_settings()->add_page( 'test', 'Test' );
    $section    = my_settings()->add_section( $page, 'testgroup', 'Test Group' );
    my_settings()->add_input( $section, 'myinput', 'My Input' );
 }
```
**aviable field types**
```php
my_settings()->add_input( $section_key, $field_key, $name, $default_value = '', $args = [] );
my_settings()->add_textarea( $section_key, $field_key, $name, $default_value = '', $args = [] );
my_settings()->add_checkbox( $section_key, $field_key, $name, $default_value = false, $args = [] );
my_settings()->add_select( $section_key, $field_key, $name, (Array) $choices, $default_value = '', $args = [] );
my_settings()->add_message( $section_key, $field_key, $name, $message = '', $args = [] );
```
## Hooks
```php
add_action('{$this->sanitize_action}_{$field_key}', 'my_function');
function my_function( $input ){
    // will be called before the values are saved
    // $input: all fields from the current page
}
```
```php
add_action('{$this->sanitize_action}_{$field_key}', 'my_function');
function my_function( $val, $input ){
    // will be called before the values are saved
    // $val: value of the field with key {$field_key}
    // $input: all fields from the current page
}
```
```php
add_filter('{$this->sanitize_filter}_{$field_key}', 'my_function');
function my_function( $value ){
    // sanitize field before save
    // $val: value of the field with key {$field_key}
    return $value;
}
```
