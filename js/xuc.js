var users_cache = JSON.parse(sessionStorage.getItem('users_cache'));
if (users_cache === null) {
   users_cache = {};
}

$(function() {
   if (typeof xivo_config != "object"
       || !xivo_config.enable_xuc) {
      return false;
   }

   if (xivo_config.enable_click2call) {
      // append 'callto:' links to domready events and also after tabs change
      click2Call();
      $(".glpi_tabs").on("tabsload", function(event, ui) {
         click2Call();
      });
   }

   if (xivo_config.enable_presence) {
      require(xuc_libs, function () {
         console.log("xuc libs loaded")
      });
   }

   if (xivo_config.enable_auto_open) {
      require(xuc_libs, function () {
         console.log("xuc libs loaded")
      });
   }
});

/**
 * Find all link to user form and append they 'callto' links
 * @return nothing
 */
var click2Call = function() {
   var elements = [],
       users_id = [];

   // found all dropdowns tooltips icons
   $("#page a[id^=comment_link_users_id]:not(.callto_link_added)").each(function(index) {
      var that    = $(this);
      var user_id = that.parent().children('input[type=hidden]').val();

      if (user_id > 0) {
         that.user_id = user_id;
         users_id.indexOf(user_id) === -1 ? users_id.push(user_id) :false;
         elements.push(that);
      }
   });

   // found all user links (like in ticket form page)
   $("#page a[id^=tooltiplink]:not(.callto_link_added)").each(function(index) {
      var that    = $(this);
      var matches = that.attr('href').match(/user.form.php\?id=(\d+)/);
      if (matches !== null && matches.length > 1) {
         var user_id = matches[1];
         if (user_id > 0) {
            that.user_id = user_id;
            users_id.indexOf(user_id) === -1 ? users_id.push(user_id) :false;
            elements.push(that);
         }
      }
   });

   // deferred ajax calls to retrieve users informations (phone, title, etc)
   // and when done, append 'callto:' links
   var deferreds = storeUsersInSessionStorage(users_id);
   $.when.apply($, deferreds).then(function() {
      sessionStorage.setItem('users_cache', JSON.stringify(users_cache))
      appendCalltoLinks(elements);
   });
};

/**
 * For all user's id passed, store in session storage the user information by calling ajax requests
 * @param  Array users_id list of integer user's id
 * @return Array deferreds ajax request
 */
var storeUsersInSessionStorage = function(users_id) {
   var deferreds = [];

   $.each(users_id, function(index, user_id) {
      if (user_id in users_cache) {
         return true;
      }
      deferreds.push($.ajax({
         'url': '../plugins/xivo/ajax/user.php',
         'data': {
            'id': user_id,
            'action': 'get_call_link'
         },
         'dataType': 'json',
         'success': function(data) {
            users_cache[user_id] = data;
         }
      }));
   });

   return deferreds;
};

/**
 * For each elements passed, add 'callto_link_added' cl and append 'callto:'' link after
 * @param  Array elements list of dom elements
 *                        (each should have a user_id key to match users_cache list)
 * @return nothing
 */
var appendCalltoLinks = function(elements) {
   $.each(elements, function(index, element) {
      var user_id = element.user_id;
      var data = users_cache[user_id];
      if ('phone' in data
          && data.phone != null) {
         element
            .addClass("callto_link_added")
            .after("<a href='callto:" + data.phone
               + "' class='xivo_callto_link" + data.append_classes
               + "' title='" + data.title+ "'></a>");
      }
   });
};
