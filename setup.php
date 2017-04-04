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

define('PLUGIN_XIVO_VERSION', '0.1.0');

if (!defined("PLUGINXIVO_DIR")) {
   define("PLUGINXIVO_DIR", GLPI_ROOT . "/plugins/xivo");
}

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_xivo() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['xivo'] = true;

   // add autoload for vendor
   include_once(PLUGINXIVO_DIR . "/vendor/autoload.php");

   // don't load hooks if plugin not enabled (or glpi not logged)
   $plugin = new Plugin();
   if (!$plugin->isInstalled('xivo')
       || !$plugin->isActivated('xivo')
       || !Session::getLoginUserID() ) {
      return true;
   }

   // config page
   Plugin::registerClass('PluginXivoConfig', ['addtabon' => 'Config']);
   $PLUGIN_HOOKS['config_page']['xivo'] = 'front/config.form.php';

   // additional tabs
   Plugin::registerClass('PluginXivoPhone_Line',
                         ['addtabon' => 'Phone']);

   // add Line to GLPI types
   Plugin::registerClass('PluginXivoLine',
                         ['linkuser_types' => 'Line',
                          'contract_types' => 'Line',
                          'document_types' => 'Line']);

   // css & js
   $PLUGIN_HOOKS['add_css']['xivo'] = 'xivo.css';
   $PLUGIN_HOOKS['add_javascript']['xivo'] = 'xivo.js';

   // standard hooks
   $PLUGIN_HOOKS['item_purge']['xivo'] = [
      'Phone' => ['PluginXivoPhone', 'phonePurged']
   ];

   // display autoinventory in phones
   $PLUGIN_HOOKS['autoinventory_information']['xivo'] = [
      'Phone' =>  ['PluginXivoPhone', 'displayAutoInventory'],
   ];

   // add menu hook
   $PLUGIN_HOOKS['menu_toadd']['xivo'] = [
      // insert into 'plugin menu'
      'management' => 'PluginXivoLine'
   ];
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

function xivoGetIdByField($itemtype = "", $field = "", $value = "") {
   global $DB;

   $query = "SELECT `id`
             FROM `".$itemtype::getTable()."`
             WHERE `$field` = '".addslashes($value)."'";
   $result = $DB->query($query);

   if ($DB->numrows($result) == 1) {
      return $DB->result($result, 0, 'id');
   }
   return false;
}
