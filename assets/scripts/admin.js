/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(1);
__webpack_require__(4);
module.exports = __webpack_require__(5);


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(2);

(function ($, vars) {

	var $elements = $('.criticalapi-generate');
	if (!$elements.length) {
		return;
	}

	$(function () {

		var $wpbody = $('body .awpp-wrap__content');
		$wpbody.append('<div class="criticalapi-loader"></div>');
		var $loader = $wpbody.find('.criticalapi-loader');

		$elements.each(function () {
			var $e = $(this);
			var $url_input = $e.find('.criticalapi-generate__input');
			var $trigger_generate = $e.find('#regenerate-criticalcss');
			var $trigger_delete = $e.find('#delete-criticalcss');

			$trigger_generate.on('click', function () {

				$url_input.removeClass('-error');
				var url = $url_input.val();

				if (!valid_url(url)) {
					$url_input.addClass('-error');
					$url_input.addClass('-pop');
					setTimeout(function () {
						$url_input.removeClass('-pop');
					}, settings_easing_speed);
					return false;
				}

				var vals = [];
				$e.find('input, textarea, select').each(function () {
					vals.push($(this).attr('name') + '=' + $(this).val());
				});

				var val = vals.join('&');
				$loader.fadeIn();

				$.ajax({
					url: vars['AjaxURL'],
					type: 'POST',
					dataType: 'json',
					data: val
				}).done(function (data) {

					$loader.fadeOut();

					if (data['type'] === null || data['type'] !== 'success') {

						/**
       * error
       */

						var msg_content = data['message'];
						if (msg_content === '' || msg_content === undefined) {
							msg_content = 'error';
						}

						alert(msg_content);
					} else {

						/**
       * success
       */

						$e.find('.is_generated').text(data['add']['datetime']);
						$e.removeClass('criticalapi-generate--nofile');
					}
				});
			});

			$trigger_delete.on('click', function () {

				var vals = [];
				vals.push('action=' + $e.find('input[name=action_delete]').val());
				vals.push('critical_key=' + $e.find('input[name=critical_key]').val());

				var val = vals.join('&');

				$e.removeClass('criticalapi-generate--file');
				$e.addClass('criticalapi-generate--nofile');

				$.ajax({
					url: vars['AjaxURL'],
					type: 'POST',
					dataType: 'json',
					data: val
				}).done(function (data) {

					if (data['type'] === null || data['type'] !== 'success') {

						var msg_content = data['message'];
						if (msg_content === '' || msg_content === undefined) {
							msg_content = 'error';
						}

						alert(msg_content);
					}
				});
			});
		});
	});

	function valid_url(url) {
		var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
		'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|' + // domain name
		'((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
		'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
		'(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
		'(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
		return pattern.test(url);
	}
})(jQuery, AwppJsVars);

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _settings = __webpack_require__(3);

var _settings2 = _interopRequireDefault(_settings);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

for (var key in _settings2.default) {
	window[key] = _settings2.default[key];
}

/***/ }),
/* 3 */
/***/ (function(module, exports) {

module.exports = {"settings_easing_speed":200}

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


(function ($) {

	$(function () {

		var $container = $('.awpp-server-wrap ');
		var $select = $container.find('select#serverpush');
		var $htaccess_info = $container.find('#serverpush-htaccess-info');

		var $trigger = $container.find('a#scan-page');
		var $loader = $container.find('.loader');
		var action = $trigger.attr('data-action');
		var ajaxUrl = $trigger.attr('data-ajaxurl');

		showhide_htacess_info();
		$select.on('change', showhide_htacess_info);

		function showhide_htacess_info() {
			if ($select.val() === 'htaccess') {
				$htaccess_info.slideDown();
			} else {
				$htaccess_info.slideUp();
			}
		}

		$trigger.on('click', function () {
			$loader.fadeIn();

			jQuery.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: 'action=' + action
			}).done(function (data) {

				if (data['type'] === null || data['type'] !== 'success') {

					/**
      * error
      */

					var msg_content = data['message'];
					if (msg_content === '' || msg_content === undefined) {
						msg_content = 'Error';
					}

					alert(msg_content);
				} else {

					//console.log(data['add']);
					$.each(data['add'], function (key, data) {
						if (key !== 'styles' && key !== 'scripts') {
							return true;
						}
						var $list = $('ul.files-list#' + key);
						var $last = $list.find('li.no-items');
						$list.find('li').addClass('remove');
						$last.removeClass('remove');
						$.each(data, function (id, url) {
							if ($list.find('li#' + id).length) {
								$list.find('li#' + id).removeClass('remove');
								return true;
							}
							$('<li id="' + id + '"><label title="' + url + '"><input type="checkbox" name="awpp-settings[serverpush_files][' + key + '][' + id + ']"/> ' + id + '</label></li>').insertBefore($last);
						});
						$list.find('li.remove').remove();
					});
				}

				$loader.fadeOut();
			});
		});
	});
})(jQuery);

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


(function ($) {

    $(function () {

        var $container = $('.awpp-settings-wrap');
        var $loadCSS = $container.find('select#loadcss');
        var $ServerPush = $container.find('select#serverpush');
        var $trigger = $container.find('select#serverpush, select#loadcss');

        $trigger.each(function () {

            var $e = $(this);
            var event = 'click';
            var $sub_container = $e.parents('td').find('.settings-sub');

            if ($e.prop('nodeName') === 'SELECT') {
                event = 'change';
            }

            $e.on(event, function () {
                var val = $(this).val();
                var id = $(this).attr('id');
                $sub_container.each(function () {
                    if ($(this).hasClass('settings-sub-' + id + '-' + val)) {
                        $(this).slideDown();
                    } else {
                        $(this).slideUp();
                    }
                });
            });
        });

        /*
        $loadCSS.on('click', function () {
            const $ccss_container = $(this).next('.settings-critical-css-container');
            if ($(this).prop('checked')) {
                $ccss_container.slideDown();
            } else {
                $ccss_container.slideUp();
            }
        });
          $ServerPush.on('change', function () {
            const $htaccess_container = $(this).next('.settings-htaccess-push-container');
            if ($(this).val() === 'htaccess') {
                $htaccess_container.slideDown();
            } else {
                $htaccess_container.slideUp();
            }
        });
        */
    });
})(jQuery);

/***/ })
/******/ ]);