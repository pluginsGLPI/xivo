<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginXivoUser {
   static function getCallLink($users_id = 0) {
      $data = [
         'phone'          => null,
         'append_classes' => '',
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
}