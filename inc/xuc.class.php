<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginXivoXuc {
   function getLoginForm() {
      $out = "<form id='xuc_login_form'>
         <h2>".__("Connect to XIVO", 'xivo')."</h2>

         <label for='xuc_username'>".__("XIVO username", 'xivo')."</label>
         <input type='text' id='xuc_username'>

         <label for='xuc_password'>".__("XIVO password", 'xivo')."</label>
         <input type='password' id='xuc_password'>

         <label for='xuc_phoneNumber'>".__("XIVO phone number", 'xivo')."</label>
         <input type='text' id='xuc_phoneNumber' size='6'>

         <input type='submit' class='submit' id='xuc_sign_in' value='".__("Connect")."'>

         <div id='xuc_message'></div>
      </form>";

      return $out;
   }

   function getLoggedForm() {
      $user = new User;
      $user->getFromDB($_SESSION['glpiID']);
      $picture = "";
      if (isset($user->fields['picture'])) {
         $picture = $user->fields['picture'];
      }

      $out = "<form id='xuc_logged_form'>
         <h2>".
            __("XIVO connected", 'xivo')."&nbsp;
            <i id='xuc_sign_out' class='fa fa-power-off pointer'></i>
         </h2>

         <div id='xuc_user_info'>
            <div id='xuc_user_picture'>
               <img src='".User::getThumbnailURLForPicture($picture)."'>
            </div>
            <div class='floating_text'>
               <div id='xuc_fullname'></div>
               <div id='xuc_statuses'>
                  <div>
                     <label for='xuc_user_status'>".__("User", 'xivo')."</label>
                     <select id='xuc_user_status'></select>
                  </div>
                  <div>
                     <label for='xuc_phone_status'>".__("Phone", 'xivo')."</label>
                     <input type='text' id='xuc_phone_status' readonly>
                  </div>
               </div>
            </div>
         </div>
      </form>

      <div class='separ'></div>

      <div id='xuc_call_informations'>
         <h2 id='xuc_call_titles'>
            <div id='xuc_ringing_title'>".__("Incoming call", 'xivo')."</div>
            <div id='xuc_oncall_title'>".__("On call", 'xivo')."</div>
         </h2>
         <div class='xuc_content'>
            <div><b>".__('Caller num:')."</b>&nbsp;<span id='xuc_caller_num'></span></div>
            <div id='xuc_caller_infos'></div>
         </div>
         <h2>".__("Phone actions", 'xivo')."</h2>
         <div class='xuc_content'>
            <i class='fa fa-phone-square fa-flip-horizontal' id='xuc_answer'></i>
            <i class='fa fa-phone-square fa-rotate-90' id='xuc_hangup'></i>
            <i class='fa fa-pause-circle' id='xuc_hold'></i>
         </div>
      </div>";

      return $out;
   }

   function getCallLink($users_id = 0) {
      $data = [
         'phone'          => null,
         'title'          => '',
      ];
      $user = new User;
      if ($user->getFromDB($users_id)) {
         if (!empty($user->fields['phone'])) {
            $data['phone'] = $user->fields['phone'];
            $data['title'] = sprintf(__("Call %s: %s"), $user->getName(), $user->fields['phone']);
         }
      }

      return $data;
   }

   function getUserInfosByPhone($params = []) {
      global $DB;

      $data = [
         'users'    => [],
         'tickets'  => [],
         'redirect' => false,
         'message'  => null
      ];

      $caller_num = isset($params['caller_num'])
         ? preg_replace('/\D+/', '', $params['caller_num']) // only digits
         : 0;

      if (empty($caller_num)) {
         return $data;
      }

      $r_not_digit = "[^0-9]*";
      $regex_num = $r_not_digit.implode($r_not_digit, str_split($caller_num)).$r_not_digit;

      // try to find user by its phone or mobile numbers
      $iterator_users = $DB->request("SELECT id FROM glpi_users
                                      WHERE phone  REGEXP '$regex_num'
                                         OR mobile REGEXP '$regex_num'");
      foreach ($iterator_users as $data_user) {
         $userdata = getUserName($data_user["id"], 2);
         $name     = "<b>".__("User found in GLPI:", 'xivo')."</b>".
                     "&nbsp;".$userdata['name'];
         $name     = sprintf(__('%1$s %2$s'), $name,
                             Html::showToolTip($userdata["comment"],
                                               ['link'    => $userdata["link"],
                                                'display' => false]));

         $data_user['link'] = $name;
         $data['users'][]   = $data_user;
      }

      // one user search for tickets
      if (count($data['users']) > 1) {
         // mulitple user, no redirect and return a message
         $data['message'] = __("Multiple users found with this phone number", 'xivo');
      } elseif (count($data['users']) == 1) {
         $current_user     = current($data['users']);
         $users_id         = $current_user['id'];
         $iterator_tickets = $DB->request("SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content
                                           FROM glpi_tickets
                                           INNER JOIN glpi_tickets_users
                                             ON glpi_tickets_users.tickets_id = glpi_tickets.id
                                             AND glpi_tickets_users.type = ".CommonITILActor::REQUESTER."
                                           WHERE glpi_tickets.status < ".CommonITILObject::SOLVED);
         $data['tickets'] = iterator_to_array($iterator_tickets);
         $nb_tickets = count($iterator_tickets);

         $ticket = new Ticket;
         $user   = new User;
         $user->getFromDB($users_id);

         if ($nb_tickets == 1) {
            // if we have one user with one ticket, redirect to ticket
            $ticket->getFromDB(current($data['tickets'])['id']);
            $data['redirect'] = $ticket->getLinkURL();
         } elseif ($nb_tickets > 1) {
            // if we have one user with multiple tickets, redirect to user (on Ticket tab)
            $data['redirect'] = $user->getLinkURL().'&forcetab=Ticket$1';
         } else {
            // if the current user has no tickets, redirect to ticket creation form
            $data['redirect'] = $ticket->getFormUrl().'?_users_id_requester='.$user->getID();
         }
      }

      return $data;
   }
}