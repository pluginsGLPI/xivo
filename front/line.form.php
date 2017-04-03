<?php

include ('../../../inc/includes.php');

$line = new PluginXivoLine();

if (isset($_POST["add"])) {
   $newID = $line->add($_POST);

   if ($_SESSION['glpibackcreated']) {
      Html::redirect($line->getFormURL()."?id=".$newID);
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $line->delete($_POST);
   $line->redirectToList();

} else if (isset($_POST["restore"])) {
   $line->restore($_POST);
   $line->redirectToList();

} else if (isset($_POST["purge"])) {
   $line->delete($_POST, 1);
   $line->redirectToList();

} else if (isset($_POST["update"])) {
   $line->update($_POST);
   Html::back();

} else {
   // fill id, if missing
   isset($_GET['id'])
      ? $ID = intval($_GET['id'])
      : $ID = 0;

   // display form
   Html::header(PluginXivoLine::getTypeName(),
             $_SERVER['PHP_SELF'],
             "management",
             "pluginxivoline");
   $line->display(['id' => $ID]);
   Html::footer();
}