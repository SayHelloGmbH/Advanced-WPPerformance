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
__webpack_require__(2);
module.exports = __webpack_require__(3);


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


(function ($) {

    var $container = '';
    var $checkbox = '';

    $(function () {

        $container = $('#wp-admin-bar-awpp_adminbar-criticalcss');
        $checkbox = $container.find('input#awpp-check-criticalcss');
        if (!$container.length || !$checkbox.length) {
            return;
        }

        var styles = $('head').find('link[rel=stylesheet]');

        $checkbox.on('change', function () {

            $.each(styles, function (index, e) {

                var $e = $(e);
                var link = void 0;
                var id = $e.attr('id');

                if ($e.attr('data-href') === undefined) {
                    link = $e.attr('href');
                } else {
                    link = $e.attr('data-href');
                }

                if (id.includes('admin-bar') || id.includes('adminbar') || id.includes('dashicons')) {
                    return true;
                }

                if ($checkbox.prop('checked')) {
                    $e.attr('data-href', link);
                    $e.removeAttr('href');
                } else {
                    $e.attr('href', link);
                }
            });
        });
    });
})(jQuery);

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


(function ($) {

    $(function () {});
})(jQuery);

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


(function ($) {

    var $container = '';
    var $button = '';

    $(function () {

        $container = $('#wp-admin-bar-awpp_adminbar-minify');
        $button = $container.find('#awpp-clear-cache');
        if (!$container.length || !$button.length) {
            return;
        }

        var ajaxUrl = $button.attr('data-ajaxurl');
        var nonce = $button.attr('data-nonce');
        var action = 'awpp_do_clear_minify_cache';

        $button.on('click', function () {

            $container.addClass('loading');

            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: 'action=' + action + '&nonce=' + nonce
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
                    $container.find('.count').text('0');
                    $container.find('.size').text('0 B');
                }

                $container.removeClass('loading');
            });
        });
    });
})(jQuery);

/***/ })
/******/ ]);