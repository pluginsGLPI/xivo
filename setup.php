<?php
/*
 -------------------------------------------------------------------------
 xivo plugin for GLPI
 Copyright (C) 2017 by the xivo Development Team.

 https://github.com/pluginsGLPI/xivo
 -------------------------------------------------------------------------

 LICENSE

 This file is part of xivo.

 xivo is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 xivo is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with xivo. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_XIVO_VERSION', '0.0.6');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_xivo() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['xivo'] = true;

   // config page
   Plugin::registerClass('PluginXivoConfig', ['addtabon' => 'Config']);
   $PLUGIN_HOOKS['config_page']['xivo'] = 'front/config.form.php';

   // css & js
   $PLUGIN_HOOKS['add_css']['xivo'] = 'xivo.css';
   $PLUGIN_HOOKS['add_javascript']['xivo'] = 'xivo.js';

}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_xivo() {
   return [
      'name'           => 'xivo',
      'version'        => PLUGIN_XIVO_VERSION,
      'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
      'license'        => '',
      'homepage'       => '',
      'minGlpiVersion' => '9.1'
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_xivo_check_prerequisites() {
   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION, '9.1', 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.1');
      } else {
         echo "This plugin requires GLPI >= 9.1";
      }
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_xivo_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured', 'xivo');
   }
   return false;
}


function plugin_xivo_recursive_remove_empty($haystack) {
   foreach ($haystack as $key => $value) {
      if (is_array($value)) {
         if (count($value) == 0) {
            unset($haystack[$key]);
         } else {
            $haystack[$key] = plugin_xivo_recursive_remove_empty($haystack[$key]);
         }
      } else if ($haystack[$key] === "") {
         unset($haystack[$key]);
      }
   }

   return $haystack;
}