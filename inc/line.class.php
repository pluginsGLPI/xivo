<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginXivoLine extends CommonDBTM {
   static $rightname = 'phone';
   public $dohistory = true;

   static function getTypeName($nb = 0) {
      return _n("Line", "Lines", $nb, 'xivo');
   }

   /**
    * Import a single line into GLPI
    *
    * @param  array  $line the line to import
    * @return mixed the line id (integer) or false
    */
   static function importSingle($line = []) {
      $xivoconfig = PluginXivoConfig::getConfig();
      $myline     = new self;
      $lines_id   = xivoGetIdByField(__CLASS__, 'line_id', $line['id']);
      $input      = [
         'protocol'               => $line['protocol'],
         'name'                   => $line['name'],
         'provisioning_extension' => $line['provisioning_extension'],
         'provisioning_code'      => $line['provisioning_code'],
         'device_slot'            => $line['device_slot'],
         'caller_id_num'          => $line['caller_id_num'],
         'caller_id_name'         => $line['caller_id_name'],
         'context'                => $line['context'],
         'position'               => $line['position'],
         'registrar'              => $line['registrar'],
         'line_id'                => $line['id'],
         'date_mod'               => $_SESSION["glpi_currenttime"],
      ];

      if (isset($line['glpi_users_id'])) {
         $input['users_id'] = $line['glpi_users_id'];
      }

      if (!$lines_id) {
         $input['entities_id'] = $xivoconfig['default_entity'];
         $lines_id = $myline->add($input);
      } else {
         $input['id'] = $lines_id;
         $myline->update($input);
      }

      return $lines_id;
   }

   function defineTabs($options=array()) {
      $tabs = array();
      $this->addDefaultFormTab($tabs)
           ->addStandardTab('PluginXivoPhone_Line', $tabs, $options)
           ->addStandardTab('Contract_Item', $tabs, $options)
           ->addStandardTab('Infocom', $tabs, $options)
           ->addStandardTab('Document_Item', $tabs, $options)
           ->addStandardTab('Notepad', $tabs, $options)
           ->addStandardTab('Log', $tabs, $options);

      return $tabs;
   }

   function showForm($ID, $options=array()) {
      // init form html
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'name', ['value' => $this->fields['name']]);
      echo "</td>";
      echo "<td>".__('Protocol', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('protocol', ['value' => $this->fields['protocol']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Provisioning extension', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('provisioning_extension', ['value' => $this->fields['provisioning_extension']]);
      echo "</td>";
      echo "<td>".__('Provisioning code', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('provisioning_code', ['value' => $this->fields['provisioning_code']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Device slot', 'xivo')."</td>";
      echo "<td>";
      Dropdown::showInteger('device_slot', $this->fields['device_slot'], 0, 100, 1, []);
      echo "</td>";
      echo "<td>".__('Position', 'xivo')."</td>";
      echo "<td>";
      Dropdown::showInteger('position', $this->fields['position'], 0, 100, 1, []);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Caller num', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('caller_id_num', ['value' => $this->fields['caller_id_num']]);
      echo "</td>";

      $rowspan = 4;
      echo "<td rowspan='$rowspan'>".__('Comments')."</td>";
      echo "<td rowspan='$rowspan' class='middle'>";
      echo "<textarea cols='45' rows='".($rowspan+3)."' name='comment' >".
           $this->fields['comment'];
      echo "</textarea></td></tr>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Caller name', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('caller_id_name', ['value' => $this->fields['caller_id_name']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(['value' => $this->fields['users_id']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Registrar', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('registrar', ['value' => $this->fields['registrar']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Xivo line_id', 'xivo')."</td>";
      echo "<td>";
      echo Html::input('line_id', ['value' => $this->fields['line_id']]);
      echo "</td>";
      echo "</tr>";



      // end form html and show controls
      $this->showFormButtons($options);

      return true;
   }

   function getSearchOptions() {
      $options           = [];
      $options['common'] = __('Characteristics');

      $options['1'] = [
         'table' => self::getTable(),
         'field' => 'name',
         'name'  => __('Name'),
         'datatype' => 'itemlink'
      ];

      $options['2'] = [
         'table'    => self::getTable(),
         'field'    => 'protocol',
         'name'     => __('Protocol', 'xivo'),
         'datatype' => 'text'
      ];

      $options['3'] = [
         'table'    => self::getTable(),
         'field'    => 'provisioning_extension',
         'name'     => __('Provisioning extension', 'xivo'),
         'datatype' => 'text'
      ];

      $options['4'] = [
         'table'    => self::getTable(),
         'field'    => 'provisioning_code',
         'name'     => __('Provisioning code', 'xivo'),
         'datatype' => 'text'
      ];

      $options['5'] = [
         'table'    => self::getTable(),
         'field'    => 'device_slot',
         'name'     => __('Device slot', 'xivo'),
         'datatype' => 'integer'
      ];

      $options['6'] = [
         'table'    => self::getTable(),
         'field'    => 'position',
         'name'     => __('Position', 'xivo'),
         'datatype' => 'integer'
      ];

      $options['7'] = [
         'table'    => self::getTable(),
         'field'    => 'caller_id_num',
         'name'     => __('Caller num', 'xivo'),
         'datatype' => 'text'
      ];

      $options['8'] = [
         'table'    => self::getTable(),
         'field'    => 'caller_id_name',
         'name'     => __('Caller name', 'xivo'),
         'datatype' => 'text'
      ];

      $options['9'] = [
         'table'    => User::getTable(),
         'field'    => 'name',
         'name'     => __('User'),
         'datatype' => 'dropdown',
         'right'    => 'all'
      ];

      $options['10'] = [
         'table'    => self::getTable(),
         'field'    => 'registrar',
         'name'     => __('Registrar', 'xivo'),
         'datatype' => 'text'
      ];

      $options['11'] = [
         'table'    => self::getTable(),
         'field'    => 'line_id',
         'name'     => __('Xivo line_id', 'xivo'),
         'datatype' => 'text'
      ];

      $options['12'] = [
         'table'         => Phone::getTable(),
         'field'         => 'name',
         'name'          => __('Associated phones', 'xivo'),
         'datatype'      => 'itemlink',
         'forcegroupby'  => true,
         'massiveaction' => true,
         'joinparams'    => [
            'beforejoin' => [
               'table' => 'glpi_plugin_xivo_phones_lines',
               'joinparams' => [
                  'jointype' => 'child'
               ]
            ]
         ]
      ];

      return $options;
   }

   /**
    * Define menu name
    */
   static function getMenuName() {
      // call class label
      return self::getTypeName(2);
   }

   /**
    * Define additionnal links used in breacrumbs and sub-menu
    */
   static function getMenuContent() {
      $title  = self::getMenuName(2);
      $search = self::getSearchURL(false);
      $form   = self::getFormURL(false);

      // define base menu
      $menu = [
         'title' => $title,
         'page'  => $search,

         'links' => [
            'search' => $search,
            'add'    => $form
         ]
      ];

      return $menu;
   }

   static function getAddSearchOptions($itemtype = '') {
      $options = [];
      $index   = 95120;

      switch ($itemtype) {
         case "Phone":
            $options[$index] = [
               'table'         => self::getTable(),
               'field'         => 'name',
               'name'          => __('Associated lines', 'xivo'),
               'datatype'      => 'itemlink',
               'forcegroupby'  => true,
               'massiveaction' => true,
               'joinparams'    => [
                  'beforejoin' => [
                     'table' => 'glpi_plugin_xivo_phones_lines',
                     'joinparams' => [
                        'jointype' => 'child'
                     ]
                  ]
               ]
            ];
            $index++;
            break;
      }

      return $options;
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();
      if (!TableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `$table` (
                  `id`                     INT(11) NOT NULL auto_increment,
                  `entities_id`            INT(11) NOT NULL DEFAULT 0,
                  `is_recursive`           TINYINT(1) NOT NULL DEFAULT 0,
                  `is_deleted`             TINYINT(1) NOT NULL DEFAULT 0,
                  `protocol`               VARCHAR(25) NOT NULL DEFAULT '',
                  `name`                   VARCHAR(50) NOT NULL DEFAULT '',
                  `provisioning_extension` VARCHAR(25) NOT NULL DEFAULT '',
                  `provisioning_code`      VARCHAR(25) NOT NULL DEFAULT '',
                  `device_slot`            INT(11) NOT NULL DEFAULT 0,
                  `caller_id_num`          INT(11) NOT NULL DEFAULT 0,
                  `caller_id_name`         VARCHAR(50) NOT NULL DEFAULT '',
                  `users_id`               INT(11) NOT NULL DEFAULT 0,
                  `contect`                VARCHAR(25) NOT NULL DEFAULT '',
                  `position`               INT(11) NOT NULL DEFAULT 0,
                  `registrar`              VARCHAR(50) NOT NULL DEFAULT '',
                  `line_id`                VARCHAR(50) NOT NULL DEFAULT '',
                  `date_mod`               DATETIME DEFAULT NULL,
                  `comment`                TEXT DEFAULT NULL,
                  PRIMARY KEY        (`id`),
                  KEY `entities_id`  (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `users_id`     (`users_id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->query($query) or die ($DB->error());
      }

      // insert display preferences
      $rank = 1;
      foreach([2, 4, 7, 8, 6, 5] as $option) {
         $DB->query("REPLACE INTO `glpi_displaypreferences` VALUES (
            NULL,
            'PluginXivoLine',
            '$option',
            '$rank',
            0
         )");
         $rank++;
      }


      return true;
   }

   /**
    * Database table uninstallation for the item type
    *
    * @return boolean True on success
    */
   static function uninstall() {
      global $DB;
      $DB->query("DROP TABLE IF EXISTS `".self::getTable()."`");
      $DB->query("DELETE FROM `glpi_displaypreferences`
                  WHERE `itemtype` = 'PluginXivoLine'");
      $DB->query("DELETE FROM `glpi_logs`
                  WHERE `itemtype` = 'PluginXivoLine'");

      return true;
   }
}