var users_cache = JSON.parse(sessionStorage.getItem('users_cache'));
if (users_cache === null) {
   users_cache = {};
}

var logged = false;
var plugin_ajax_url = "../plugins/xivo/ajax/xuc.php";

require(["xivo_plugin/store.modern.min"], function(store) {
   window.xivo_store = store;
});

$(function() {
   if (typeof xivo_config != "object"
       || !xivo_config.enable_xuc) {
      return false;
   }

   require(xuc_libs, function() {
      retrieveXivoSession();
      initUiGLPI();

      if (xivo_config.enable_click2call) {
         // append 'callto:' links to domready events and also after tabs change
         click2Call();
         $(".glpi_tabs").on("tabsload", function(event, ui) {
            click2Call();
         });
      }

      if (xivo_config.enable_presence) {

      }

      if (xivo_config.enable_auto_open) {

      }
   });
});

var retrieveXivoSession = function() {
   var xivo_data = xivo_store.get('xivo');
   if (typeof xivo_data == "object") {
      return xivo_data;
   }

   return false;
};

var destroyXivoSession = function() {
   xivo_store.remove('xivo');
}

var saveXivoSession = function(xivo_data) {
   xivo_store.set('xivo', xivo_data);
};

var initUiGLPI = function() {
   $("#c_preference ul #preferences_link")
      .after("<li id='xivo_agent'>\
               <a class='fa fa-phone' id='xivo_agent_button'></a>\
               <div id='xivo_agent_form'>empty</div>\
             </li>");
   $(document).on("click", "#xivo_agent_button", function() {
      $("#xivo_agent_form").toggle();
      if (!logged) {
         loadLoginForm();
      }
   });

   var xivo_data = retrieveXivoSession();
   if (xivo_data !== false) {
      initConnection(xivo_data);
   }

   $(document).on("click", "#xuc_sign_in", function() {
      xucSignIn();
   });

   $(document).on("click", "#xuc_sign_out", function() {
      xucSignOut();
   });
};

var loadLoginForm = function() {
   $("#xivo_agent_form").load(plugin_ajax_url, {
      'action': 'get_login_form'
   });
}

var loadLoggedForm = function() {
   return $.ajax({
      'type': 'POST',
      'url': plugin_ajax_url,
      'data': {
         'action': 'get_logged_form'
      },
      'success': function(html) {
         $("#xivo_agent_form").html(html)
      }
   });
}

var xucSignIn = function() {
   var xuc_username    = $("#xuc_username").val();
   var xuc_password    = $("#xuc_password").val();
   var xuc_phoneNumber = $("#xuc_phoneNumber").val();

   $.when(loginOnXuc(xuc_username, xuc_password)).then(function(data) {
      var xivo_data = {
         'username': xuc_username,
         'token': data.token,
         'phoneNumber': xuc_phoneNumber,
      };
      saveXivoSession(xivo_data);
      initConnection(xivo_data);
   });
};

var xucSignOut = function() {
   Cti.webSocket.close();
   loadLoginForm();
   destroyXivoSession();
   $("#xivo_agent_form").hide();
   $("#xivo_agent_button")
      .removeClass()
      .addClass('fa fa-phone');
};

var initConnection = function(xivo_data) {
   if (typeof xivo_data !==  "object") {
      return false;
   }

   $.when(loadLoggedForm()).then(function() {
      var wsurl = getXucWsUrl() + "/xuc/api/2.0/cti?token="+xivo_data.token;
      Cti.WebSocket.init(wsurl, xivo_data.username, xivo_data.phoneNumber);
      logged = true;

      Cti.setHandler(Cti.MessageType.LOGGEDON, function() {
         $("#xivo_agent_form").hide();
      });

      Cti.setHandler(Cti.MessageType.USERSTATUSES, function(statuses) {
         //console.log(statuses);
      });

      Cti.setHandler(Cti.MessageType.USERSTATUSUPDATE, function(event) {
         $("#xivo_agent_button")
            .removeClass()
            .addClass('logged fa fa-phone')
            .addClass('status_' + event.status);

         if (event.status !== null) {
            $("#xuc_user_status").text(event.status);
         }
      });

      Cti.setHandler(Cti.MessageType.USERCONFIGUPDATE, function(event) {
         if (event.fullName !== null) {
            $("#xuc_fullname").text(event.fullName);
         }
      });
   });
}

var getXucWsUrl = function() {
   return xivo_config.xuc_url.replace(/https*:\/\//, 'ws://')
};

var loginOnXuc = function(username, password) {
   return $.ajax({
      type: "POST",
      url: xivo_config.xuc_url + "/xuc/api/2.0/auth/login",
      contentType: "application/json",
      data: JSON.stringify({
         'login': username,
         'password': password
      }),
      dataType: 'json'
   });
};

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
