<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\GuzzleException;

class PluginXivoAPIClient extends CommonGLPI {
   private $api_config      = [];
   private $auth_token      = '';
   private $current_port    = 0;
   private $current_version = 0;

   function __construct() {
      // retrieve plugin config
      $this->api_config = PluginXivoConfig::getConfig();
   }


   function __destruct() {
      //destroy current token
      //$this->disconnect();
   }


   function useXivoAuth() {
      $this->current_port    = 9497;
      $this->current_version = 0.1;
   }


   function useXivoConfd() {
      $this->current_port    = 9486;
      $this->current_version = 1.1;
   }


   function status() {
      return [
         __('Api access', 'xivo')        => !empty($this->auth_token),
         __('Get phone devices', 'xivo') => boolval($this->getDevices([
            'query' => [
               'limit' => 30
            ]
         ])),
      ];
   }


   function connect() {
      // we use Xivo-auth api
      $this->useXivoAuth();

      // send connect with http query
      $data = $this->httpQuery('token', [
         'auth' => [
            $this->api_config['api_username'],
            $this->api_config['api_password'],
         ],
         'json' => [
            'backend'    => 'xivo_service',
            'expiration' => HOUR_TIMESTAMP,
         ]
      ], 'POST');

      if (is_array($data)) {
         if (isset($data['data']['token'])) {
            $this->auth_token = $data['data']['token'];
         }
      }

      return $data;
   }


   function disconnect() {
      return;
      // we use Xivo-auth api
      $this->useXivoAuth();

      // send disconnect with http query
      $data = $this->httpQuery('token', [
         'verify' => boolval($this->api_config['api_ssl_check']),
         'json' => [
            'token' => $this->auth_token,
         ]
      ], 'DELETE');
   }


   function getDevices($params = []) {
      return $this->getList('devices', $params);
   }

   function getLines($params = []) {
      return $this->getList('lines', $params);
   }

   function getList($endpoint = '', $params = []) {
      // declare default params
      $default_params = [
         'query' => [
            'limit'     => 50,
            'direction' => 'asc',
            'offset'    => 0,
            'order'     => '',
            'search'    => '',
         ]
      ];

      // merge default params
      $params = array_replace_recursive($default_params, $params);

      // check connection
      if (empty($this->auth_token)) {
         $this->connect();
      }

      // we use Xivo-confd api
      $this->useXivoConfd();

      // get devices with http query
      $data = $this->httpQuery($endpoint, $params, 'GET');

      return $data;
   }

   /**
    * Return the XIVO API base uri
    *
    * @return string the uri
    */
   function getAPIBaseUri() {
      return trim($this->api_config['api_url'], '/').":{$this->current_port}/{$this->current_version}/";
   }

   function httpQuery($resource = '', $params = array(), $method = 'GET') {
      global $CFG_GLPI;

      // declare default params
      $default_params = [
         '_with_metadata'  => false,
         'allow_redirects' => false,
         'timeout'         => 5,
         'connect_timeout' => 5,
         'debug'           => false,
         'verify'          => boolval($this->api_config['api_ssl_check']),
         'query'           => [], // url parameter
         'body'            => '', // raw data to send in body
         'json'            => '', // json data to send
         'headers'         => ['content-type'  => 'application/json',
                               'Accept'        => 'application/json'],
      ];
      // if connected, append auth token
      if (!empty($this->auth_token)) {
         $default_params['headers']['X-Auth-Token'] = $this->auth_token;
      }
      // append proxy params if exists
      if (!empty($CFG_GLPI['proxy_name'])) {
         $proxy = $CFG_GLPI['proxy_user'].
                  ":".$CFG_GLPI['proxy_passwd'].
                  "@".preg_replace('#https?://#', '', $CFG_GLPI['proxy_name']).
                  ":".$CFG_GLPI['proxy_port'];

         $default_params['proxy'] = [
            'http'  => "tcp://$proxy",
            'https' => "tcp://$proxy",
         ];
      }
      // merge default params
      $params = array_replace_recursive($default_params, $params);
      //remove empty values
      $params = plugin_xivo_recursive_remove_empty($params);

      // init guzzle
      $http_client = new GuzzleHttp\Client(['base_uri' => $this->getAPIBaseUri()]);

      // send http request
      try {
         $response = $http_client->request($method,
                                           $resource,
                                           $params);
      } catch (GuzzleException $e) {
         $debug = ["XIVO API error"];
         $debug[] = $params;
         $debug[] = Psr7\str($e->getRequest());
         if ($e->hasResponse()) {
            $debug[] = Psr7\str($e->getResponse());
         }
         Toolbox::logDebug($debug);
         return false;
      }

      // parse http response
      $http_code     = $response->getStatusCode();
      $reason_phrase = $response->getReasonPhrase();
      $headers       = $response->getHeaders();

      // check http errors
      if (intval($http_code) > 400) {
         // we have an error if http code is greater than 400
         return false;
      }
      // cast body as string, guzzle return strems
      $json        = (string) $response->getBody();
      $prelude_res = json_decode($json, true);

      // check xivo error
      $xivo_api_error = false;

      $data =  json_decode($json, true);

      //append metadata
      if ($params['_with_metadata']) {
         $data['_headers']   = $headers;
         $data['_http_code'] = $http_code;
      }


      return $data;
   }

}

