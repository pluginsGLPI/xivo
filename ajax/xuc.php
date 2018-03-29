<?php

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (!isset($_REQUEST['action'])) {
   exit;
}

switch ($_REQUEST['action']) {
   case 'get_login_form':
      echo PluginXivoXuc::getLoginForm();
      break;
   case 'get_logged_form':
      echo PluginXivoXuc::getLoggedForm();
      break;
}

