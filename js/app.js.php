<?php

include ("../../../inc/includes.php");

//change mimetype
header("Content-type: application/javascript");

if (!$plugin->isInstalled("xivo")
    || !$plugin->isActivated("xivo")) {
   exit;
}

$xivoconfig = json_encode(PluginXivoConfig::getConfig(), JSON_NUMERIC_CHECK);

$JS = <<<JAVASCRIPT

// pass php xivo config to javascript
var xivo_config = $xivoconfig;

$(function() {
   // config requirejs for xivo xuc libs (some have dependencies)
   require.config({
      paths: {
         "xivo_plugin": '../plugins/xivo/js',
         "store2": '../plugins/xivo/js/store2.min',
         "xuc_lib": xivo_config.xuc_url + '/xucassets/javascripts',
         "jstree": '../lib/jqueryplugins/jstree/jstree.min'
      },
      shim: {
         'xuc_lib/xc_webrtc': {
            deps: ['xuc_lib/cti', 'xuc_lib/SIPml-api'],
         }
      }
   });

   // register the current jQuery to avoid trying loading it with requirejs
   // without it, jstree (entity selector) fails (see https://github.com/pluginsGLPI/xivo/issues/10)
   define('jquery', [], function () {
      return jQuery;
   });
   // finally call jstree directly (on all page :/)
   require(['jstree']);

   // load session storage
   require(['store2'], function(store) {
      if (xivo_config.xuc_local_store) {
         console.log("xivo plugin use local storage");
         window.xivo_store = store.local;
      } else {
         console.log("xivo plugin use session storage");
         window.xivo_store = store.session;

         // load cross tab sessionStorage script
         require(["xivo_plugin/sessionStorageTabs"]);
      }
   });

   // init xuc features
   require([
      'xuc_lib/shotgun',
      'xuc_lib/cti',
      'xuc_lib/callback',
      'xuc_lib/membership',
      'xuc_lib/SIPml-api',
      'xuc_lib/xc_webrtc',
      'xivo_plugin/xuc',
   ], function() {
      // call xuc integration
      var xuc_obj = new Xuc();
      xuc_obj.init();

      // append 'callto:' links to domready events and also after tabs change
      if (xivo_config.enable_click2call) {
         users_cache = xivo_store.get('users_cache');
         xuc_obj.click2Call();
         $(".glpi_tabs").on("tabsload", function(event, ui) {
            xuc_obj.click2Call();
         });
      }
   });
});


JAVASCRIPT;
echo $JS;
