<?php
require_once('vendor/autoload.php');
date_default_timezone_set('Europe/Amsterdam');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Yaml\Yaml;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;

$logger = new Logger("log");
# Log to stdout
$logger->pushHandler(new StreamHandler('php://stdout'));
# Log to file
#$logger->pushHandler(new StreamHandler(__DIR__ . '/check-guests.log'));

$logger->addDebug('Detecting presence');

$config = Yaml::parseFile(__DIR__ . '/config.yaml');

$tado = new Client(['base_uri' => 'https://my.tado.com/api/v2/', 'timeout'  => 5.0]);
$unifiConnection = new UniFi_API\Client(
    $config['unifi']['username'], 
    $config['unifi']['password'],
    $config['unifi']['url'], 
    $config['unifi']['siteId'], 
    $config['unifi']['version'], 
    $config['unifi']['verifyCertificate']
);
$unifiConnection->login();
$clients = $unifiConnection->list_clients();


foreach($config['users'] as $user) {
    $user['present'] = false;

    if (isset($user['mac']) && $user['mac']) {
        foreach ($clients as $client) {
            if (!$client->is_wired && $client->mac == $user['mac']) {
                $user['present'] = true;
            }
        }
    } elseif(isset($user['network']) && $user['network']) {
        foreach ($clients as $client) {
            if (!$client->is_wired && $client->network == $user['network']) {
                $user['present'] = true;
            }
        }
    }

    $logger->addInfo('UniFi: ' . $user['name']. ': ' . ($user['present'] ? 'Present' : 'Not present'));

    $data = tadoRequest('settings', $user['tado']);
    $logger->addInfo('Tado: ' .  $user['name']. ': ' . ($data->geoTrackingEnabled ? "At home": "Not at home"));

    if (!$user['present'] && $data->geoTrackingEnabled) {

        $logger->addInfo('Tado: Disable geotracking');
        tadoRequest('settings', $user['tado'], ['geoTrackingEnabled' => false]);

    } elseif ($user['present'] && !$data->geoTrackingEnabled) {

        $logger->addInfo('Tado: Enable geotracking');
        tadoRequest('settings', $user['tado'], ['geoTrackingEnabled' => true]);

        $logger->addDebug('Tado: Update location to home');
        $data = [
            'geolocation' => [
                'latitude' => $config['tado']['geolocation']['latitude'],
                'longitude' => $config['tado']['geolocation']['longitude']
            ],
            'timestamp' => date('c'),
            'accuracy' => 32
        ];
        tadoRequest('geolocationFix', $user['tado'], $data);
    }
}

function tadoRequest($url, $userConfig = false, $data = false) {
    global $tado;
    global $config;
    global $logger;

    $method = 'GET';
    $url = 'homes/' . $config['tado']['homeId'] . '/mobileDevices/' . $userConfig['deviceId'] . '/' . $url;
    $params = [];
    $params['query'] = [
        'username' => $userConfig['username'],
        'password' => $userConfig['password']
    ];
    if ($data) {
        $method = 'PUT';
        $params['body'] = json_encode($data);
    }
    
    try {
        $response = $tado->request($method, $url, $params);
        return json_decode($response->getBody());
    } catch (BadResponseException $e) {
        $logger->addError($e->getMessage());
        return false;
    }
}

