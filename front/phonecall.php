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

if (!$plugin->isActivated('xivo')) {
   echo __("Xivo plugin not activated", 'xivo');
   exit;
}

if (!isset($_REQUEST['caller_num'])) {
   exit;
}

$xuc  = new PluginXivoXuc;
$data = $xuc->getUserInfosByPhone($_REQUEST);

if ($data['redirect'] && isset($_REQUEST['redirect'])) {
   \Session::checkValidSessionId();
   \Html::redirect($data['redirect']);
} else {
   echo json_encode($data);
}
