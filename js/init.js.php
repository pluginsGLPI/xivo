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

// config requirejs for xivo xuc libs (some have dependencies)
require.config({
   paths: {
      "xuc_lib": xivo_config.xuc_url + '/xucassets/javascripts'
   },
   shim: {
      'xuc_lib/xc_webrtc': {
         deps: ['xuc_lib/cti', 'xuc_lib/SIPml-api'],
      }
   }
});

// define an array xuc libraries for future usage
var xuc_libs = [
   'xuc_lib/shotgun',
   'xuc_lib/cti',
   'xuc_lib/callback',
   'xuc_lib/membership',
   'xuc_lib/SIPml-api',
   'xuc_lib/xc_webrtc',
];

JAVASCRIPT;
echo $JS;
