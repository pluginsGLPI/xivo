<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

require_once 'vendor/autoload.php';

class RoboFile extends Glpi\Tools\RoboFile
{
   public function __construct() {
      $this->csignore[] = '/js/store2.min.js';
      $this->csignore[] = '/js/xivo/';
   }
}
