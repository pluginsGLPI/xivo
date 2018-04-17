<?php

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (!isset($_REQUEST['action'])) {
   exit;
}

$xuc = new PluginXivoXuc;
switch ($_REQUEST['action']) {
   case 'get_login_form':
      echo $xuc->getLoginForm();
      break;

   case 'get_logged_form':
      echo $xuc->getLoggedForm();
      break;

   case 'comm_established':
      echo $xuc->commEstablished();
      break;

   case 'get_call_link':
      $data = [];
      if (isset($_REQUEST['id'])) {
         $data = $xuc->getCallLink((int) $_REQUEST['id']);
         echo json_encode($data);
      }
      break;
}
