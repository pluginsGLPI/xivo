<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginXivoXuc {
   static function getLoginForm() {
      $out = "<form id='xuc_login_form'>
         <h2>".__("Connect to XIVO", 'xivo')."</h2>

         <label for='xuc_username'>".__("XIVO username", 'xivo')."</label>
         <input type='text' id='xuc_username'>

         <label for='xuc_password'>".__("XIVO password", 'xivo')."</label>
         <input type='password' id='xuc_password'>

         <label for='xuc_phoneNumber'>".__("XIVO phone number", 'xivo')."</label>
         <input type='text' id='xuc_phoneNumber' size='6'>

         <a class='vsubmit' id='xuc_sign_in'>".__("Connect")."</a>
      </form>";

      return $out;
   }

   static function getLoggedForm() {
      $out = "<form id='xuc_logged_form'>
         <h2>".
            __("XIVO connected", 'xivo')."&nbsp;
            <i id='xuc_sign_out' class='fa fa-power-off pointer'></i>
         </h2>
         <div id='xuc_user_info'>
            <i id='xuc_user_picture' class='fa fa-user-circle-o'></i>
            <div class='floating_text'>
               <div id='xuc_fullname'></div>
               <div id='xuc_user_status'></div>
            </div>
         </div>
      </form>";

      return $out;
   }
}