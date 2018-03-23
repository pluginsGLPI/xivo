<?php

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (!isset($_REQUEST['id'])
    || !isset($_REQUEST['action'])) {
   exit;
}

$data = [];
switch ($_REQUEST['action']) {
   case 'get_call_link':
      $data = PluginXivoUser::getCallLink((int) $_REQUEST['id']);
      break;
}

echo json_encode($data);
