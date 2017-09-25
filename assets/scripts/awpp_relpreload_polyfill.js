/*! loadCSS rel=preload polyfill (adjustments for Advanced WPPerformance). [c]2017 Filament Group, Inc. MIT License */
(function (w) {
    "use strict";

    var support = function () {
        try {
            return document.createElement("link").relList.supports("preload");
        } catch (e) {
            return false;
        }
    };

    // check if the browser supports rel="preload" or if it's PSI (A bit hacky but there is a bug in PSI)
    if (support() || navigator.userAgent.indexOf('Page Speed') !== -1) {
        return;
    }

    var rp = loadCSS.relpreload = {};
    rp.poly = function () {
        var links = w.document.getElementsByTagName("link");
        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            if (link.rel === "preload" && link.getAttribute("as") === "style") {
                w.loadCSS(link.href, link, link.getAttribute("media"));
                link.rel = null;
            }
        }
    };
    rp.poly();
    var run = w.setInterval(rp.poly, 300);
    if (w.addEventListener) {
        w.addEventListener("load", function () {
            rp.poly();
            w.clearInterval(run);
        });
    }
    if (w.attachEvent) {
        w.attachEvent("onload", function () {
            w.clearInterval(run);
        });
    }
}(this));