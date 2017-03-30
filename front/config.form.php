<?php

include ("../../../inc/includes.php");

if (isset($_REQUEST["forcesync"])) {
   CronTask::launch(-1, 1, 'xivoimport');
   Html::back();
} else {
   Html::redirect($CFG_GLPI["root_doc"]."/front/config.form.php?forcetab=PluginXivoConfig\$1");
}
