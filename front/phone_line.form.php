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
 * @copyright Copyright (C) 2017-2024 by xivo plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/xivo
 * -------------------------------------------------------------------------
 */

include ('../../../inc/includes.php');

Session::checkCentralAccess();
$phoneline = new PluginXivoPhone_Line();
if (isset($_POST["add"])) {
   $phoneline->check(-1, CREATE, $_POST);

   if (isset($_POST["phones_id"]) && ($_POST["phones_id"] > 0)
       && isset($_POST["lines_id"]) && ($_POST["lines_id"] > 0)) {
      $phoneline->add($_POST);
   }
   Html::back();
}

Html::displayErrorAndDie('Lost');
