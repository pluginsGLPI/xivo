<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginXivoConfig extends Config {

   static function getTypeName($nb=0) {
      return __('Xivo', 'xivo');
   }

   /**
    * Return the current config of the plugin store in the glpi config table
    *
    * @return array config with keys => values
    */
   static function getConfig() {
      return Config::getConfigurationValues('plugin:xivo');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      switch ($item->getType()) {
         case "Config":
            return self::createTabEntry(self::getTypeName());
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item,
                                            $tabnum=1,
                                            $withtemplate=0) {
      switch ($item->getType()) {
         case "Config":
            return self::showForConfig($item, $withtemplate);
      }

      return true;
   }

   static function showForConfig(Config $config,
                                     $withtemplate=0) {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      $current_config = self::getConfig();
      $canedit        = Session::haveRight(self::$rightname, UPDATE);
      echo "<div class='xivo_config'>";
      if ($canedit) {
         echo "<form name='form' action='".Toolbox::getItemTypeFormURL("Config")."' method='post'>";
      }

      echo "<h1>".__("Configuration of XIVO integration")."</h1>";

      echo self::showField([
         'inputtype' => 'yesno',
         'label'     => __("Import phone devices", 'xivo'),
         'attrs'     => [
            'name'      => 'import_devices',
            'value'     => $current_config['import_devices'],
            'on_change' => '$("#import_devices").toggleFromValue(this.value);',
         ]
      ]);

      $style = "";
      if (!$current_config['import_devices']) {
         $style = "display: none;";
      }
      echo "<div id='import_devices' class='xivo_config_block' style='$style'>";
      echo self::showField([
         'label' => __("API url", 'xivo'),
         'attrs' => [
            'name'  => 'api_url',
            'value'       => $current_config['api_url'],
            'placeholder' => 'https://...',
         ]
      ]);
      echo self::showField([
         'label' => __("API username", 'xivo'),
         'attrs' => [
            'name'  => 'api_username',
            'value' => $current_config['api_username'],
            'style' => 'width:100px;',
         ]
      ]);
      echo self::showField([
         'inputtype' => 'password',
         'label'     => __("API password", 'xivo'),
         'attrs'     => [
            'name'  => 'api_password',
            'value' => $current_config['api_password'],
            'style' => 'width:100px;',
         ]
      ]);
      echo self::showField([
         'inputtype' => 'yesno',
         'label'     => __("API check SSL", 'xivo'),
         'attrs'     => [
            'name'  => 'api_ssl_check',
            'value' => $current_config['api_ssl_check'],
         ]
      ]);
      echo self::showField([
         'inputtype' => 'yesno',
         'label'     => __("Import devices with empty serial number", 'xivo'),
         'attrs'     => [
            'name'  => 'import_empty_sn',
            'value' => $current_config['import_empty_sn'],
         ]
      ]);
      echo self::showField([
         'inputtype' => 'yesno',
         'label'     => __("Import devices with empty mac", 'xivo'),
         'attrs'     => [
            'name'  => 'import_empty_mac',
            'value' => $current_config['import_empty_mac'],
         ]
      ]);
      echo self::showField([
         'inputtype' => 'yesno',
         'label'     => __("Import 'not_configured' devices", 'xivo'),
         'attrs'     => [
            'name'  => 'import_notconfig',
            'value' => $current_config['import_notconfig'],
         ]
      ]);
      echo self::showField([
         'inputtype' => 'dropdown',
         'itemtype'  => 'Entity',
         'label'     => __("Default entity", 'xivo'),
         'attrs' => [
            'name'  => 'default_entity',
            'value' => $current_config['default_entity'],
         ]
      ]);

      if (self::isValid()) {
         echo Html::link(__("Force synchronization"), self::getFormURL()."?forcesync");
      }

      echo "</div>";

      if ($canedit) {
         echo Html::hidden('config_class', ['value' => __CLASS__]);
         echo Html::hidden('config_context', ['value' => 'plugin:xivo']);
         echo Html::submit(_sx('button','Save'), [
            'name' => 'update'
         ]);
      }

      Html::closeForm();

      if (self::isValid()) {
         echo "<h1>".__("API XIVO status", 'xivo')."</h1>";
         $apiclient    = new PluginXivoAPIClient;
         $data_connect = $apiclient->connect();
         $all_status   = $apiclient->status();

         echo "<ul>";
         $error = false;
         foreach($all_status as $status_label => $status) {
            $color_png = "greenbutton.png";
            if (!$status) {
               $color_png = "redbutton.png";
               $error = true;
            }
            echo "<li>";
            echo Html::image($CFG_GLPI['url_base']."/pics/$color_png");
            echo "&nbsp;".$status_label;
            echo "</li>";
         }
         echo "</ul>";

         if ($error) {
            echo "<h1>".__("Last Error", 'xivo')."</h1>";
            $error = $apiclient->getLastError();
            echo $error['exception'];
         }

         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            echo "<h1>".__("DEBUG")."</h1>";

            // display token
            if (isset($data_connect['data']['token'])) {
               echo "<h2>".__("Auth token", 'xivo')."</h2>";
               echo $data_connect['data']['token'];
            }

            // display acl
            if (isset($data_connect['data']['acls'])) {
               echo "<h2>".__("ACL", 'xivo')." (".count($data_connect['data']['acls']).")</h2>";
               echo "<ul>";
               foreach($data_connect['data']['acls'] as $right) {
                  echo "<li>$right</li>";
               }
               echo "</ul>";
            }
         }
      }
      echo "</div>"; // .xivo_config
   }

   /**
    * Show a single config field
    * Generic method who call the different GLPI function to display a field
    *
    * @param  array  $options a list of options:
    *                            - inputtype (string), can be
    *                               * text
    *                               * password
    *                               * yesno
    *                               * dropdown
    *                            - itemtype (only for input=dropdown)
    *                            - label, <label> tag to append to the field
    *                            - attrs, an array containing html attributes
    * @return string the html
    */
   static function showField($options = []) {
      $rand            = mt_rand();
      $default_options = [
         'inputtype' => 'text',
         'itemtype'  => '',
         'label'     => '',
         'attrs'     => [
            'name'        => '',
            'value'       => '',
            'placeholder' => '',
            'style'       => 'width:50%;',
            'id'          => "xivoconfig_field_$rand",
            'class'       => 'xivo_input',
            'required'    => 'required',
            'on_change'   => ''
         ]
      ];
      $options = array_replace_recursive($default_options, $options);

      $out = "";
      $out.= "<div class='xivo_field'>";


      // call the field according to its type
      switch($options['inputtype']) {
         default:
         case 'text':
            $out.= Html::input('fakefield', ['style' => 'display:none;']);
            $out.= Html::input($options['attrs']['name'], $options['attrs']);
            break;

         case 'password':
            $out.=  "<input type='password' name='fakefield' style='display:none;'>";
            $out.=  "<input type='password'";
            foreach($options['attrs'] as $key => $value) {
               $out.= "$key='$value' ";
            }
            $out.= ">";
            break;

         case 'yesno':
            $options['attrs']['display'] = false;
            $out.= Dropdown::showYesNo($options['attrs']['name'], $options['attrs']['value'], -1, $options['attrs']);
            break;

         case 'dropdown':
            $options['attrs']['display'] = false;
            $out.= Dropdown::show($options['itemtype'], $options['attrs']);
            break;
      }

      $out.= "<label class='xivo_label' for='{$options['attrs']['id']}'>
              {$options['label']}</label>";
      $out.= "</div>";

      return $out;
   }

   /**
    * Check if current saved config is valid
    * @param  boolean $with_api also check api status
    * @return boolean
    */
   static function isValid($with_api = false) {
      $current_config = self::getConfig();
      $valid_config =  (!empty($current_config['api_url'])
                        && !empty($current_config['api_username'])
                        && !empty($current_config['api_password']));

      $valid_api = true;
      if ($with_api) {
         $apiclient = new PluginXivoAPIClient;
         $apiclient->connect();
         $statuses = $apiclient->status();
         $valid_api = !in_array(false, $apiclient->status());
      }

      return ($valid_config && $valid_api);
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   static function install(Migration $migration) {
      $current_config = self::getConfig();

      // fill config table with default values if missing
      foreach ([
         // api access
         'import_devices'   => 0,
         'api_url'          => '',
         'api_username'     => '',
         'api_password'     => '',
         'api_ssl_check'    => 1,
         'import_empty_sn'  => 0,
         'import_empty_mac' => 0,
         'import_notconfig' => 0,
         'default_entity'   => 0,
      ] as $key => $value) {
         if (!isset($current_config[$key])) {
            Config::setConfigurationValues('plugin:xivo', array($key => $value));
         }
      }
   }

   /**
    * Database table uninstallation for the item type
    *
    * @return boolean True on success
    */
   static function uninstall() {
      $config = new Config();
      $config->deleteByCriteria(['context' => 'plugin:xivo']);

      return true;
   }
}