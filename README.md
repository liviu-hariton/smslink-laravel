# SMSLink Laravel package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/liviu-hariton/smslink-laravel.svg?style=flat-square)](https://packagist.org/packages/liviu-hariton/smslink-laravel)
<a href="https://github.com/laravel"><img src="https://img.shields.io/badge/Laravel-11-f4645f.svg" /></a>
[![Total Downloads](https://img.shields.io/packagist/dt/liviu-hariton/smslink-laravel.svg?style=flat-square)](https://packagist.org/packages/liviu-hariton/smslink-laravel)
<a href="https://github.com/liviu-hariton/smslink-laravel/blob/main/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue" /></a>

A Laravel PHP package that provides convenient access to the [SMSLink](https://www.smslink.ro) API.

## Overview
Integration with SMSLink offers the ability to perform the following actions:

* send a single SMS message
* send SMS messages in bulk
* get the balance of the account

## Table Of Content
* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
  * [Alter the default credentials after initialization](#alter-the-default-credentials-after-initialization)
  * [Send a single SMS message](#send-a-single-sms-message)
  * [Send messages in bulk](#send-messages-in-bulk)
  * [Check the current balance](#check-the-current-balance)
  * [Get the delivery report](#get-the-delivery-report)
* [Uninstallation](#uninstallation)
* [License](#license)
* [SMSLink official API documentation](#smslink-official-api-documentation)
* [Disclaimer](#disclaimer)

## Requirements
* PHP >= 8.3
* [Laravel](https://github.com/laravel/laravel) >= 11.0

## Installation

You can install the SMSLink Laravel package via Composer. Run the following command in your terminal:

```bash
composer require liviu-hariton/smslink-laravel
```
Laravel will automatically register the package.

Publish the config file of this package with this command

```bash
php artisan vendor:publish --tag=smslink_config
```
The following config file will be published in `config/smslink.php`

```php
return [
    'connection_id' => env('SMSLINK_CONNECTION_ID', ''), // the SmsLink `Connection ID`
    'connection_password' => env('SMSLINK_CONNECTION_PASSWORD', ''), // the SmsLink `Connection Password`
];
```
Edit your `.env` file and add the following to it:

```text
SMSLINK_CONNECTION_ID=
SMSLINK_CONNECTION_PASSWORD=
````
You can get the `Connection ID` and `Connection Password` values from your [SMSLink connections manager section](https://www.smslink.ro/sms/gateway/setup.php)

## Usage

When the installation is done you can easily start consuming the SMSLink's API by using the available methods. All methods will return a JSON formatted data. Just inject the dependency in your controller's methods.

### Alter the default credentials after initialization

In case you want to use different credentials for a specific request or you have them stored in a database, you can set them using the `setConfig()` method.

```php
<?php

namespace App\Http\Controllers;

use LHDev\Smslink\Smslink;

class YourController extends Controller
{
    public function yourMethod(Smslink $smslink)
    {
        $smslink->setConfig([
            'connection_id' => 'your_connection_id',
            'connection_password' => 'your_connection_password',
        ]);
        
        $sms = $smslink->send('0712345678', 'This is a SMS message', $options);
    }
}
```

### Send a single SMS message

```php
<?php

namespace App\Http\Controllers;

use LHDev\Smslink\Smslink;

class YourController extends Controller
{
    public function yourMethod(Smslink $smslink)
    {
        $sms = $smslink->send('0712345678', 'This is a SMS message', $options);
    }
}
```

The `send()` method takes two parameters:
* the phone number of the recipient
* the message to be sent
* an optional array of options
  * all the available options are described in the [official documentation](https://www.smslink.ro/sms-gateway-parametrii-transmisi-catre-sms-gateway-http.html)
  * note that the `connection_id` and `password` parameters are already set in the config file - they are not required in the options array

The method will return a JSON formatted response with the following structure:

```json
{
  "type":"MESSAGE",
  "id":"1",
  "message":"Message sent to 0712345678 from numeric with ID 0!",
  "variables":[
    "0",
    "0712345678",
    "numeric"
  ]
}
```

### Send messages in bulk

```php
<?php

namespace App\Http\Controllers;

use LHDev\Smslink\Smslink;

class YourController extends Controller
{
    public function yourMethod(Smslink $smslink)
    {
        $package = [
            [
                'id' => '1',
                'to' => '0712345678',
                'message' => 'This is a SMS message',
            ],
            [
                'id' => '2',
                'to' => '0723456789',
                'message' => 'This is another (maybe) SMS message',
            ]
        ];
        
        $sms = $smslink->bulk($package, $options);
    }
}
````

The `bulk()` method takes two parameters:
* an array of packages
  * each package must contain the following keys:
    * `id` - the ID of the package
    * `to` - the phone number of the recipient
    * `message` - the message to be sent
* an optional array of options
  * all the available options are described in the [official documentation](https://www.smslink.ro/sms-gateway-implementarea-sms-gateway-bulk.html)
  * note that the `connection_id` and `password` parameters are already set in the config file - they are not required in the options array

### Check the current balance

```php
<?php

namespace App\Http\Controllers;

use LHDev\Smslink\Smslink;

class YourController extends Controller
{
    public function yourMethod(Smslink $smslink)
    {
        $sms = $smslink->credit();
    }
}
```

The `credit()` method takes no parameters and will return a JSON formatted response with the following structure:

```json
{
  "type":"MESSAGE",
  "id":"4",
  "message":"Account balance is 150 national SMS and 0 international SMS",
  "variables":[
    "150",
    "0",
    "1740878151"
  ]
}
```
### Get the delivery report

With this method, you can parse the delivery report received from the SmsLink API via a POST or a GET request

```php
<?php

namespace App\Http\Controllers;

use LHDev\Smslink\Smslink;

class YourController extends Controller
{
    public function yourMethod(Smslink $smslink)
    {
        $sms = $smslink->delivery_report();
    }
}
```

The `delivery_report()` method takes no parameters and will return a JSON formatted response with the following structure:

```json
{
  "message_id":"1",
  "status":"1",
  "timestamp":"1740878151",
  "network_id":"2",
  "network_type":"1",
  "delivery_report":"Lorem ipsum dolor sit amet",
  "connection_id":"your_connection_id",
  "message_count":"1"
}
```
All the available fields are described in the [official documentation](https://www.smslink.ro/sms-gateway-raportul-de-livrare-al-sms-ului-transmis-prin-sms-gateway.html)

## Uninstallation

You can uninstall the **SMSLink Laravel package** via Composer. Run the following command in your terminal:

```bash
composer remove liviu-hariton/smslink-laravel
```
Also, make sure to remove the following files created by the package in your Laravel root directory:
* `/config/smslink.php`

Update your `.env` file and remove the following lines:

```text
SMSLINK_CONNECTION_ID=
SMSLINK_CONNECTION_PASSWORD=
```

## License
This library is licensed under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.

## SMSLink official API documentation
* The official documentation is available on [smslink.ro](https://www.smslink.ro/sms-gateway-documentatie-sms-gateway.html)

## Disclaimer
I am not affiliated with SMSLink, but I am a developer who sees the value of their SMS Gateway services. The development and maintenance of this library is solely my responsibility (and any contributors to this repository).

Feel free to explore this library here on GitHub, contribute, and make the most of SMSLink’s powerful SMS Gateway services!