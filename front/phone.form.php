<?php

include ('../../../inc/includes.php');

Session::checkRight("phone", READ);

if (isset($_REQUEST["forcesync"])) {
   PluginXivoPhone::forceSync($_REQUEST['xivo_id']);
   Html::back();
}
