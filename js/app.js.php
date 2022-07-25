<?php

/**
 * -------------------------------------------------------------------------
 * xivo plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of xivo.
 *
 * xivo is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * xivo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with xivo. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2017-2022 by xivo plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/xivo
 * -------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

//change mimetype
header("Content-type: application/javascript");

if (!Plugin::isPluginActive("xivo")) {
   exit;
}

// retrieve plugin config
$xivoconfig = json_encode(PluginXivoConfig::getConfig(), JSON_NUMERIC_CHECK);

//check constants to disable builtin features
$enable_presence = PLUGIN_XIVO_ENABLE_PRESENCE;
$enable_callcenter = PLUGIN_XIVO_ENABLE_CALLCENTER;

$JS = <<<JAVASCRIPT

// pass php xivo config to javascript
var xivo_config = $xivoconfig;

// disable features from constants
xivo_config.enable_presence &= $enable_presence;
xivo_config.enable_callcenter &= $enable_callcenter;

$(function() {
   // call xuc integration
   xuc_obj = new Xuc();
   xuc_obj.init(xivo_config);

   // append 'callto:' links to domready events and also after tabs change
   if (xivo_config.enable_click2call) {
      xuc_obj.click2Call();
      $(".glpi_tabs").on("tabsload", function(event, ui) {
         xuc_obj.click2Call();
      });
   }
});

JAVASCRIPT;
echo $JS;
