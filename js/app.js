$(function() {
   if (typeof xivo_config != "object"
       || !xivo_config.enable_xuc) {
      return false;
   }

   require(["xivo_plugin/store.modern.min"], function(store) {
      window.xivo_store = store;
   });

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

      if (xivo_config.enable_presence) {

      }

      if (xivo_config.enable_auto_open) {

      }
   });
});
