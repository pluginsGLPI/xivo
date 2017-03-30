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

   static function cronXivoimport(CronTask $crontask) {
      $xivoconfig    = PluginXivoConfig::getConfig();
      $totaldevices  = 0;
      $totallines    = 0;
      $phone_lines   = [];

      // retrieve devices
      $devices = self::paginate('Devices');

      // retrieve lines
      $lines = self::paginate('Lines');

      foreach($lines as &$line) {
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
      $crontask->setVolume($totalimported);
      if ($totalimported) {
         return true;
      } else {
         return false;
      }
   }

   static function paginate($function = "Devices") {
      $apiclient = new PluginXivoAPIClient;
      $offset    = 0;
      $limit     = 200;
      $devices   = [];

      do {
         $page = $apiclient->{"get$function"}([
            'query' => [
               'offset'         => $offset,
               'limit'          => $limit,
            ],
            '_with_metadata' => true
         ]);

         $devices = array_merge($devices, $page['items']);
         $offset+= $limit;
      } while($offset < $page['total']);

      return $devices;
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