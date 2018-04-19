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
var xivo_config   = $xivoconfig;

// prepare an url to test before loading all xuc libraries
var test_xivo_url = xivo_config.xuc_url + "/xucassets/javascripts/cti.js";

// config requirejs for xivo xuc libs (some have dependencies)
require.config({
   paths: {
      "xivo_plugin": '../plugins/xivo/js',
      "xuc_lib": xivo_config.xuc_url + '/xucassets/javascripts',
   },
   shim: {
      'xuc_lib/xc_webrtc': {
         deps: ['xuc_lib/cti', 'xuc_lib/SIPml-api'],
      }
   }
});

// define an array of xuc libraries for future usage
var xuc_libs = [
   'xuc_lib/shotgun',
   'xuc_lib/cti',
   'xuc_lib/callback',
   'xuc_lib/membership',
   'xuc_lib/SIPml-api',
   'xuc_lib/xc_webrtc',
   'xivo_plugin/xuc',
];


$(function() {
   if (typeof xivo_config != "object"
       || !xivo_config.enable_xuc) {
      return false;
   }

   require(["xivo_plugin/store.modern.min"], function(store) {
      window.xivo_store = store;
   });

   urlExists(test_xivo_url, function(exists) {
      if (exists) {
         require(xuc_libs, function() {
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
      } else {
         console.log('Connection to xivo XUC failed');
      }
   });
});

urlExists = function(url, callback){
   $.ajax({
      type: 'HEAD',
      url: url,
      success: function(){
         callback(true);
      },
      error: function() {
         callback(false);
      }
   });
};

JAVASCRIPT;
echo $JS;
