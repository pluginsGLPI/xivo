<?php

include ('../../../inc/includes.php');

Html::header(PluginXivoLine::getTypeName(),
             $_SERVER['PHP_SELF'],
             "management",
             "pluginxivoline");
Search::show('PluginXivoLine');
Html::footer();