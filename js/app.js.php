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
   // load session storage
   if (xivo_config.xuc_local_store) {
      console.log("xivo plugin use local storage");
      window.xivo_store = store.local;
   } else {
      console.log("xivo plugin use session storage");
      window.xivo_store = store.session;
   }

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

JAVASCRIPT;
echo $JS;
