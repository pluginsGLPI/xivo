<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginXivoPhoneinventory extends CommonGLPI {
   static $rightname = 'phone';

   const NOT_CONFIGURED = 'not_configured';

   static function cronInfo($name) {
      switch ($name) {
         case 'xivo_importdevices' :
            return ['description' => __('Import Xivo devices', 'xivo')];
      }

      return [];
   }

   static function cronxivo_importdevices(CronTask $crontask) {
      $nb = self::import();
      $crontask->setVolume($nb);

      if ($nb) {
         $crontask->log(sprintf(_n('Xivo: %1$d phone imported',
                                   'Xivo: %1$d phones imported',
                                    $nb)."\n",
                                $nb));
         return true;
      }

      return false;
   }

   static function import() {
      $xivoconfig = PluginXivoConfig::getConfig();
      $imported   = 0;

      // retrieve devices
      $devices = self::paginate('Devices');

      // retrieve lines
      $lines = self::paginate('Lines');

      foreach($devices as $index => &$device) {
         // remove devices with missing mandatory informations
         if ($xivoconfig['del_empty_sn']
             && empty($device['sn'])) {
            unset($devices[$index]);
            continue;
         }
          if ($xivoconfig['del_empty_mac']
             && empty($device['sn'])) {
            unset($devices[$index]);
            continue;
         }
         if ($xivoconfig['del_notconfig']
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
         $imported+= (int) self::importSingleDevice($device);
      }

      Toolbox::logDebug(count($devices));
      Toolbox::logDebug($devices);

      return $imported;
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

   static function importSingleDevice($device) {
      $phone        = new Phone;
      $model        = new PhoneModel;
      $manufacturer = new Manufacturer;
      $networkport  = new NetworkPort();

      $manufacturers_id = $manufacturer->import(['name' => $device['vendor']]);
      $phonemodels_id   = $model->import(['name' => $device['model']]);
      $entities_id      = 0;
      $number_line      = count($device['lines']);
      $contact          = '';
      $contact_num      = '';
      if ($number_line) {
         $last_line   = array_pop($device['lines']);
         $contact     = $last_line['caller_id_name'];
         $contact_num = $last_line['caller_id_num'];
      }

      $input = [
         'serial'           => $device['sn'],
         'manufacturers_id' => $manufacturers_id,
         'phonemodels_id'   => $phonemodels_id,
         'entities_id'      => $entities_id,
         'contact'          => $contact,
         'contact_num'      => $contact_num,
         'number_line'      => $number_line,
         'firmware'         => $device['plugin'],
         'comment'          => $device['description'],
      ];


      if (!$phones_id = self::getPhone($device)) {
         // add phone
         $phones_id = $phone->add($input);
      } else {
         //update phone
         $input['id'] = $phones_id;
         $phone->update($input);
      }

      // import network ports
      if (!empty($device['mac'])) {
         $found_netports = $networkport->find("`itemtype` = 'Phone'
                                               AND `items_id` = '$phones_id'");
         $net_input = [
            'items_id'                    => $phones_id,
            'itemtype'                    => 'Phone',
            'entities_id'                 => $phone->fields['entities_id'],
            'mac'                         => $device['mac'],
            'instantiation_type'          => 'NetworkPortEthernet',
            'name'                        => '',
            'NetworkName_name'            => '',
            'NetworkName__ipaddresses'    => ['-1' => $device['ip']],
            '_create_children'            => true
         ];
         if (count($found_netports) == 0){
            $networkport->add($net_input);
         } else {
            $current_netport = array_shift($found_netports);
            $net_input['id'] = $current_netport['id'];
            $net_input['NetworkName__ipaddresses'] = ['1' => $device['ip']];
            $networkport->update($net_input);
         }
      }

      return (bool) $phones_id;
   }

   static function getPhone($device) {
      global $DB;

      $table = Phone::getTable();
      $query = "SELECT phone.`id`
                FROM `$table` AS phone
                LEFT JOIN `glpi_networkports` AS net
                  ON net.`itemtype` = 'Phone'
                  AND net.`items_id` = phone.`id`
                WHERE phone.`serial` = '{$device['sn']}'
                  AND phone.`serial` IS NOT NULL
                  OR net.mac = '{$device['mac']}'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         return $DB->result($result, 0, 'id');
      }
      return false;
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   static function install(Migration $migration) {
      CronTask::register(__CLASS__,
                         'xivo_importdevices',
                         12 * HOUR_TIMESTAMP,
                         [
                           'comment'   => 'Import phone devices from xivo-confd api',
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