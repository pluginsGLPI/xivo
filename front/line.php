<?php

include ('../../../inc/includes.php');

Html::header(PluginXivoLine::getTypeName(),
             $_SERVER['PHP_SELF'],
             "assets",
             "pluginxivoline");
Search::show('PluginXivoLine');
Html::footer();