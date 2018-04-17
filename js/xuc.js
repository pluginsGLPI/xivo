var Xuc = function() {
   var username        = '';
   var password        = '';
   var phoneNumber     = '';
   var bearerToken     = '';

   var logged          = false;
   var plugin_ajax_url = "";

   var userStatuses    = {};

   var callerNum       = null;
   var callerName      = ''
   var callerGlpiInfos = {};
   var redirectTo      = false;

   // click2call cache to avoid redundant ajax requests
   var users_cache     = {};

   var my_xuc = this;

   /**
    * Init UI in GLPI
    */
   my_xuc.init = function() {
      my_xuc.setAjaxUrl();
      my_xuc.retrieveXivoSession();

      $("#c_preference ul #preferences_link")
         .after("<li id='xivo_agent'>\
                  <a class='fa fa-phone' id='xivo_agent_button'></a>\
                  <i class='fa fa-circle' id='xivo_agent_status'></i>\
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
         })
         .on("click", "#xuc_hangup", function(e) {
            e.preventDefault();
            my_xuc.hangup();
         })
         .on("click", "#xuc_answer", function(e) {
            e.preventDefault();
            my_xuc.answer();
         })
         .on("click", "#xuc_hold", function(e) {
            e.preventDefault();
            my_xuc.hold();
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

   /**
    * Check the current token store as object property is still valid on xuc
    * @return Ajax Promise
    */
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

   /**
    * Init connection to CTI with xuc libs.
    * Init XIVO events (phones, statuses events)
    */
   my_xuc.initConnection = function() {
      $.when(my_xuc.loadLoggedForm()).then(function() {
         //Cti.debugMsg = true;
         Cti.clearHandlers();

         var wsurl = xivo_config.xuc_url.replace(/https*:\/\//, 'ws://')
                        + "/xuc/api/2.0/cti?token="+bearerToken;
         Cti.WebSocket.init(wsurl, username, phoneNumber);

         Callback.init(Cti);
         Membership.init(Cti);
         logged = true;

         Cti.setHandler(Cti.MessageType.LOGGEDON, function() {
            $("#xivo_agent_form").hide();
         });

         Cti.setHandler(Cti.MessageType.USERSTATUSES, my_xuc.setUserStatuses);

         Cti.setHandler(Cti.MessageType.USERSTATUSUPDATE, my_xuc.userStatusUpdate);

         Cti.setHandler(Cti.MessageType.PHONESTATUSUPDATE, function(event) {
            if (event.status !== null) {
               $("#xuc_phone_status").val(event.status);
            }
         });

         Cti.setHandler(Cti.MessageType.USERCONFIGUPDATE, function(event) {
            if (event.fullName !== null) {
               $("#xuc_fullname").text(event.fullName);
            }
         });

         // intercept phones events and switch to adequate function
         Cti.setHandler(Cti.MessageType.PHONEEVENT, my_xuc.phoneEvents);
      });
   };

   /**
    * Populate select html tag for user status with data returned by CTI
    */
   my_xuc.setUserStatuses = function(statuses) {
      userStatuses = statuses;
      $("#xuc_user_status").empty();
      $.each(statuses, function(key, item) {
         $("#xuc_user_status")
            .append("<option data-color='"+item.color+"' value='"+item.name+"'>"+ item.longName + "</option>");
      });

      // TODO: in 9.3, check if this declaration is still valid (select2 upgraded 4.0)
      $("#xuc_user_status").select2({
         'width': '180px',
         'minimumResultsForSearch': -1,
         'formatResult': function(status) {
            var option = status.element;
            var color = $(option).data('color');

            return "<i class='fa fa-circle' style='color: "+color+"'></i>&nbsp;"
                   + status.text.toUpperCase();
         },
      });

      // set cti event on change select
      $('#xuc_user_status').on('change', function (e) {
         var optionSelected = $(this).find("option:selected").val();
         Cti.changeUserStatus(optionSelected);
      });
   };

   /**
    * Callback triggerd when user status changes
    * @param  Object event the event passed by CTI on status change
    *                      it should contains:
    *                         - status = key of status,
    *                                    we can match to the Xuc object user_status property
    */
   my_xuc.userStatusUpdate = function(event) {
      var current_status = userStatuses.filter(function(status) {
         return status.name == event.status;
      })[0];

      $("#xivo_agent_button")
         .removeClass()
         .addClass('logged fa fa-phone')
         .addClass('status_' + current_status.name);

      $("#xivo_agent_status")
         .css('color', current_status.color);

      if (event.status !== null) {
         //$("#xuc_user_status").val(current_status.name);
         // TODO 9.3 moved to select2 version 4, the following line could be broken
         $("#xuc_user_status").select2("val", current_status.name);
      }
   };

   /**
    * Retrieve xivo properties in LocalStorage
    * @return bool
    */
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

   /**
    * Clear Xivo data in LocalStorage
    */
   my_xuc.destroyXivoSession = function() {
      xivo_store.remove('xivo');
   };

   /**
    * Save xivo properties in LocalStorage
    */
   my_xuc.saveXivoSession = function() {
      var xivo_data = {
         'username':    username,
         'password':    password,
         'phoneNumber': phoneNumber,
         'bearerToken': bearerToken
      }
      xivo_store.set('xivo', xivo_data);
   };

   /**
    * Load login form in GLPI UI
    */
   my_xuc.loadLoginForm = function() {
      $("#xivo_agent_form").load(plugin_ajax_url, {
         'action': 'get_login_form'
      });
   };

   /**
    * Load logged form in GLPI UI
    */
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

   /**
    * Take login form parameters, store them in LocalStorage, and init CTI connection
    */
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

   /**
    * Logout from CTI (and reset GLPI UI)
    */
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

   /**
    * Login on Xuc Rest API
    * @return Ajax Promise
    */
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

   /**
    * Find all link to user form and append they 'callto' links
    * @return nothing
    */
   my_xuc.click2Call = function() {
      var elements = [],
          users_id = [];

      users_cache = xivo_store.get('users_cache') || {};

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
      $.when.apply($, my_xuc.getUsers(users_id)).then(function() {
         xivo_store.set('users_cache', users_cache);
         my_xuc.appendCalltoIcons(elements);
      });

      // event for callto icons
      $(document)
         .on("click", "#page .xivo_callto_link", function() {
            my_xuc.dial($(this).data('phone'));
         });
   };

   /**
    * For each elements passed, add 'callto_link_added' cl and append 'callto:'' link after
    * @param  Array elements list of dom elements
    *                        (each should have a user_id key to match users_cache list)
    */
   my_xuc.appendCalltoIcons = function(elements) {
      $.each(elements, function(index, element) {
         var user_id = element.user_id;
         var data = users_cache[user_id];
         if ('phone' in data
             && data.phone != null) {

            element
               .addClass("callto_link_added")
               .after("<span"
                  + "' data-phone='" + data.phone
                  + "' class='xivo_callto_link" + data.append_classes
                  + "' title='" + data.title+ "'></a>");
         }
      });
   };

   /**
    * Launch on CTI a call with target_num parameter
    * @param  String target_num the to call
    */
   my_xuc.dial = function(target_num) {
      var variables = {};
      Cti.dial(String(target_num), variables);
   };

   /**
    * Callback triggered when phone status changes
    * @param  Object event original CTI event
    */
   my_xuc.phoneEvents = function(event) {
      my_xuc.callerNum = event.otherDN;
      my_xuc.callerName = event.otherDName;
      switch (event.eventType) {
         case "EventRinging":
            my_xuc.phoneRinging();
            break;
         case "EventReleased":
            my_xuc.commReleased();
            break;
         case "EventEstablished":
            my_xuc.commEstablished();
            break;
      }
   };

   /**
    * Callback triggered when phone is ringing
    */
   my_xuc.phoneRinging = function() {
      $("#xivo_agent_form").show();
      $("#xuc_call_titles div").hide();
      $("#xuc_ringing_title").show();
      $("#xivo_agent_button").addClass('ringing');

      $.ajax({
         url: plugin_ajax_url,
         method: "POST",
         dataType: 'json',
         data: {
            'action': 'get_user_infos_by_phone',
            'caller_num': my_xuc.callerNum
         }
      })
      .done(function(data) {
         console.log(data);
         my_xuc.callerGlpiInfos = data;
         my_xuc.displayCallerInformation();

         if (data.redirect !== false) {
            my_xuc.redirectTo = data.redirect
         }
      })
   };

   /**
    * Callback triggered when a phone call etablished
    */
   my_xuc.commEstablished = function() {
      $("#xuc_call_titles div").hide();
      $("#xuc_oncall_title").show();
      // $("#xuc_hold").show();
      $("#xivo_agent_button").removeClass('ringing');
      this.displayCallerInformation();

      if (my_xuc.redirectTo !== false) {
         window.location = my_xuc.redirectTo;
      }
   };

   /**
    * Callback triggered when communication hanged up
    */
   my_xuc.commReleased = function() {
      $("#xivo_agent_form").hide();
      $("#xuc_hold").hide();
      $("#xuc_call_informations").hide();
      $("#xivo_agent_button").removeClass('ringing');
      my_xuc.callerNum = null;
      my_xuc.callerName = 'null';
      $("#xuc_caller_num").html('');
      $("#xuc_caller_numname").html('');
   };

   /**
    * display caller inforamtion in GLPI UI
    */
   my_xuc.displayCallerInformation = function() {
      $("#xuc_call_informations").show();
      $("#xuc_caller_num").html(my_xuc.callerNum);

      // display caller information (from glpi ajax request)
      var html = ''
      var data = my_xuc.callerGlpiInfos;
      if (data.users.length == 1) {
         var user = data.users[0];
         html = user.link;
      }

      $('#xuc_caller_infos').html(html);
   };

   /**
    * Hangup the current call on CTI
    */
   my_xuc.hangup = function() {
      Cti.hangup();
   };

   /**
    * Answer the current call on CTI
    * Warning: the function doesn't seem to work at the moment.
    */
   my_xuc.answer = function() {
      Cti.answer();
   };

   /**
    * Hold the current call on CTI
    * Warning: the function doesn't seem to work at the moment.
    */
   my_xuc.hold = function() {
      xc_webrtc.answer();
   };

   /**
    * For all user's id passed, retrieve the user information by calling ajax requests
    * @param  Array users_id list of integer user's id
    * @return Array deferreds ajax request
    */
   my_xuc.getUsers = function(users_id) {
      var deferreds = [];

      $.each(users_id, function(index, user_id) {
         if (user_id in users_cache) {
            return true;
         }
         deferreds.push($.ajax({
            'url': plugin_ajax_url,
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
};
