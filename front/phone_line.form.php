<?php

include ('../../../inc/includes.php');

Session::checkCentralAccess();
$phoneline = new PluginXivoPhone_Line();
if (isset($_POST["add"])) {
   $phoneline->check(-1, CREATE,$_POST);

   if (isset($_POST["phones_id"]) && ($_POST["phones_id"] > 0)
       && isset($_POST["plugin_xivo_lines_id"]) && ($_POST["plugin_xivo_lines_id"] > 0)) {
      $phoneline->add($_POST);
   }
   Html::back();
}

Html::displayErrorAndDie('Lost');
?>