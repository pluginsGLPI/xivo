<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginXivoInventory extends CommonGLPI {
   const NOT_CONFIGURED = 'not_configured';

   static function cronInfo($name) {
      switch ($name) {
         case 'xivoimport' :
            return ['description' => __('Import Xivo assets', 'xivo')];
      }

      return [];
   }

   /**
    * Execute a full inventory synchronisation
    * Import from xivo api:
    *    - Phones
    *    - Lines
    *    - Association with users
    *
    * @param  CronTask $crontask
    * @return boolean
    */
   static function cronXivoimport(CronTask $crontask) {
      $xivoconfig    = PluginXivoConfig::getConfig();
      $apiclient     = new PluginXivoAPIClient;
      $totaldevices  = 0;
      $totallines    = 0;
      $phone_lines   = [];

      // check if api config is valid
      if (!PluginXivoConfig::isValid(true)) {
         return false;
      }

      // track execution time
      $time_start = microtime(true);

      // retrieve devices
      $devices = $apiclient->paginate('Devices');

      // retrieve lines
      $lines = $apiclient->paginate('Lines');

      // retrieve users
      $users = $apiclient->paginate('Users');

      // build an association between call_id (present in lines) and username (ldap)
      $caller_id_list = [];
      foreach ($users as $user) {
         if (!empty($user['username'])) {
            $caller_id_name = trim($user['caller_id'], '"');
            $caller_id_list[$caller_id_name] = $user['username'];
         }
      }

      // import lines
      foreach($lines as &$line) {
         //check if we can retrive the ldap username
         $line['glpi_users_id'] = 0;
         if (isset($caller_id_list[$line['caller_id_name']])) {
            $line['glpi_users_id'] = User::getIdByName($caller_id_list[$line['caller_id_name']]);
         }

         // add or update assets
         $plugin_xivo_lines_id         = PluginXivoLine::importSingle($line);
         $line['plugin_xivo_lines_id'] = $plugin_xivo_lines_id;
         $totallines                  += (int) (bool) $plugin_xivo_lines_id;
      }

      if ($totallines) {
         $crontask->log(sprintf(_n('%1$d line imported',
                                   '%1$d lines imported',
                                    $totallines, 'xivo')."\n",
                                $totallines));
      }

      foreach($devices as $index => &$device) {
         // remove devices with missing mandatory informations
         if (!$xivoconfig['import_empty_sn']
             && empty($device['sn'])) {
            unset($devices[$index]);
            continue;
         }
          if (!$xivoconfig['import_empty_mac']
             && empty($device['mac'])) {
            unset($devices[$index]);
            continue;
         }
         if (!$xivoconfig['import_notconfig']
             && $device['status'] == self::NOT_CONFIGURED) {
            unset($devices[$index]);
            continue;
         }

         // find possible lines for this device
         $device['lines'] = [];
         foreach($lines as $line) {
            if ($line['device_id'] == $device['id']) {
               $device['lines'][] = $line;
            }
         }

         // add or update assets
         $phones_id     = PluginXivoPhone::importSingle($device);
         $totaldevices += (int) (bool) $phones_id;
      }

      if ($totaldevices) {
         $crontask->log(sprintf(_n('%1$d phone imported',
                                   '%1$d phones imported',
                                    $totaldevices, 'xivo')."\n",
                                $totaldevices));
      }
      $totalimported = $totaldevices + $totallines;

      // end track of execution time
      $time_end = microtime(true);
      $totaltime = $time_end - $time_start;
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         Toolbox::logDebug("XIVO import (time + number)", round($totaltime, 2), $totalimported);
      }

      $crontask->setVolume($totalimported);
      if ($totalimported) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   static function install(Migration $migration) {
      CronTask::register(__CLASS__,
                         'xivoimport',
                         12 * HOUR_TIMESTAMP,
                         [
                           'comment'   => 'Import assets from xivo-confd api',
                           'mode'      => CronTask::MODE_EXTERNAL
                         ]);

      return true;
   }

   /**
    * Database table uninstallation for the item type
    *
    * @return boolean True on success
    */
   static function uninstall() {
      return true;
   }
}