var users_cache = JSON.parse(sessionStorage.getItem('users_cache'));
if (users_cache === null) {
   users_cache = {};
}

require(["xivo_plugin/store.modern.min"], function(store) {
   window.xivo_store = store;
});

$(function() {
   if (typeof xivo_config != "object"
       || !xivo_config.enable_xuc) {
      return false;
   }

   require(xuc_libs, function() {
      // call xuc integration
      var xuc_obj = new Xuc();
      xuc_obj.init();

      // append 'callto:' links to domready events and also after tabs change
      if (xivo_config.enable_click2call) {
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


var Xuc = function() {
   var username        = '';
   var password        = '';
   var phoneNumber     = '';
   var bearerToken     = '';

   var logged          = false;
   var plugin_ajax_url = "";

   var my_xuc = this;

   my_xuc.init = function() {
      my_xuc.setAjaxUrl();
      my_xuc.retrieveXivoSession();

      $("#c_preference ul #preferences_link")
         .after("<li id='xivo_agent'>\
                  <a class='fa fa-phone' id='xivo_agent_button'></a>\
                  <div id='xivo_agent_form'>empty</div>\
                </li>");

      $(document)
         .on("click", "#xivo_agent_button", function() {
            $("#xivo_agent_form").toggle();
            if (!logged) {
               my_xuc.loadLoginForm();
            }
         })
         .on("submit", "#xuc_login_form", function(e) {
            e.preventDefault();
            my_xuc.xucSignIn();
         })
         .on("click", "#xuc_sign_in", function(e) {
            e.preventDefault();
            my_xuc.xucSignIn();
         })
         .on("click", "#xuc_sign_out", function(e) {
            e.preventDefault();
            my_xuc.xucSignOut();
         });

      if (my_xuc.retrieveXivoSession() !== false) {
         $.when(my_xuc.checkTokenValidity())
            .then(function() {
               my_xuc.initConnection();
            })
            .fail(function(jqXHR, textStatus) {
               if (jqXHR.responseJSON.error == "TokenExpired") {
                  my_xuc.destroyXivoSession();
               }
            });
      }
   };

   my_xuc.setAjaxUrl = function() {
      plugin_ajax_url = "../plugins/xivo/ajax/xuc.php";
   };

   my_xuc.checkTokenValidity = function() {
      return $.ajax({
         type: "GET",
         url: xivo_config.xuc_url + "/xuc/api/2.0/auth/check",
         dataType: 'json',
         beforeSend : function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + bearerToken);
         }
      });
   };

   my_xuc.initConnection = function() {
      $.when(my_xuc.loadLoggedForm()).then(function() {
         var wsurl = xivo_config.xuc_url.replace(/https*:\/\//, 'ws://')
                        + "/xuc/api/2.0/cti?token="+bearerToken;
         Cti.WebSocket.init(wsurl, username, phoneNumber);
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
   };

   my_xuc.retrieveXivoSession = function() {
      var xivo_data = xivo_store.get('xivo');

      if (typeof xivo_data == "object") {
         username    = xivo_data.username;
         password    = xivo_data.password;
         phoneNumber = xivo_data.phoneNumber;
         bearerToken = xivo_data.bearerToken;

         return true;
      }

      return false;
   };

   my_xuc.destroyXivoSession = function() {
      xivo_store.remove('xivo');
   };

   my_xuc.saveXivoSession = function() {
      var xivo_data = {
         'username':    username,
         'password':    password,
         'phoneNumber': phoneNumber,
         'bearerToken': bearerToken
      }
      xivo_store.set('xivo', xivo_data);
   };

   my_xuc.loadLoginForm = function() {
      $("#xivo_agent_form").load(plugin_ajax_url, {
         'action': 'get_login_form'
      });
   };

   my_xuc.loadLoggedForm = function() {
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
   };

   my_xuc.xucSignIn = function() {
      username    = $("#xuc_username").val();
      password    = $("#xuc_password").val();
      phoneNumber = $("#xuc_phoneNumber").val();

      $.when(my_xuc.loginOnXuc()).then(function(data) {
         bearerToken = data.token;
         my_xuc.saveXivoSession();
         my_xuc.initConnection();
      });
   };

   my_xuc.xucSignOut = function() {
      Cti.webSocket.close();
      my_xuc.loadLoginForm();
      my_xuc.destroyXivoSession();
      $("#xivo_agent_form").hide();
      $("#xivo_agent_button")
         .removeClass()
         .addClass('fa fa-phone');
      logged = false;
   };

   my_xuc.loginOnXuc = function() {
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
