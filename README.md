# tado-connect
A tool to check whether users are connected to a UniFi Wifi network, and then marks them as "at home" or "away" on a tado smart thermostat

## Requirements

- php (a recent version, 5.6 works fine)
- [composer](https://getcomposer.org)
- a way to run a script every few minutes (cron)
- a UniFi controller you can query (no affiliation)
- a tado smart thermostat (no affiliation)

## Installation

```sh
git clone https://github.com/Martijn02/tado-connect.git
cd tado-connect
composer install
cp config.yaml.template config.yaml
# edit config.yaml
php check-guests.php
```
When this works as expected, set a cron to run the script ~every 5 minutes.

## Getting tado data

### Your homeId:
```
https://my.tado.com/api/v2/me?username=<your tado login>&password=<your tado password>
```

### Your home geolocation

You can lookup your geolocation using google maps, or do a call to 
```
https://my.tado.com/api/v2/homes/<your tado homeId>?username=<your tado login>&password=<your tado password>
```

### Create a tado device

This script uses one or more virtual tado devices, that are located your home geolocation, or have geolocation disabled. This causes the tado to think that someone is at your home, or not. 

```
https://my.tado.com/mobile/1.9/createAppUser?username=<your tado login>&password=<your tado password>&nickname=<name the device will have the tado app>&geoTrackingEnabled=false&deviceName=tado-connect&devicePlatform=tado-connect&deviceUuid=tado-connect&deviceOsVer=0.1&appVersion=0.1
```

This will give you the deviceId, and device specific username and password. (no need to put your real tado credentials in the config.yaml)

The api above seems to be a little older, I have not been able to capture the device creation of the current api. If someone knows the way to create a device using the /api/v2/ I would be happy to update it. 

## Contribute

If you would like to contribute code (improvements), please open an issue and include your code there or else create a pull request.

## Credits


This script would not be possible without the following dependencies:
 - [UniFi-API-client](https://github.com/Art-of-WiFi/UniFi-API-client)
 - [The work of scphillips](http://blog.scphillips.com/posts/2017/01/the-tado-api-v2/)

## Important Disclaimer

This script uses the API's of both tado and Ubiquity. Neither of those API's is officially supported by their creators, and the used functionalities might break when new versions of those API's are released.

