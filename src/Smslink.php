<?php

namespace LHDev\Smslink;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LHDev\Smslink\Exceptions\InvalidConfigException;

use Illuminate\Support\Facades\Http;

class Smslink
{
    const string _ENDPOINT_SINGLE = 'https://secure.smslink.ro/sms/gateway/communicate/index.php';
    const string _ENDPOINT_BULK = 'https://secure.smslink.ro/sms/gateway/communicate/bulk-v3.php';

    private string $connection_id;
    private string $connection_password;

    protected $client_version = "2.1";

    /**
     * @throws InvalidConfigException
     */
    public function __construct(array $config_data)
    {
        $this->saveConfig($config_data);
    }

    /**
     * Save the configuration data
     *
     * @param $config_data
     * @return void
     * @throws InvalidConfigException
     */
    private function saveConfig($config_data): void
    {
        $this->validateConfig($config_data);

        $this->connection_id = $config_data['connection_id'];
        $this->connection_password = $config_data['connection_password'];
    }

    /**
     * Validate the configuration data
     *
     * @param array $config_data
     * @return void
     * @throws InvalidConfigException
     */
    private function validateConfig(array $config_data): void
    {
        $required_credentials = ['connection_id', 'connection_password'];

        foreach($required_credentials as $credential) {
            if(empty($config_data[$credential])) {
                throw new InvalidConfigException("You need to provide the SmsLink API credentials [`".implode('`, `', $required_credentials)."`]");
            }
        }
    }

    /**
     * Set the endpoint
     *
     * @param $bulk
     * @return string
     */
    private function setEndpoint($bulk = false): string
    {
        return $bulk ? self::_ENDPOINT_BULK : self::_ENDPOINT_SINGLE;
    }

    /**
     * Connect to the SmsLink API and send the request
     *
     * @param $request_params
     * @param $method
     * @return false|string
     */
    private function connect($request_params, $method = 'GET'): false|string
    {
        $parameters = [
            'connection_id' => $this->connection_id,
            'password' => $this->connection_password
        ] + $request_params;

        $endpoint = $this->setEndpoint(isset($parameters['bulk'])).'?timestamp='.date("U");

        if($method == 'GET') {
            $response = Http::get($endpoint, $parameters);
        }

        if($method == 'POST') {
            $response = Http::attach(
                'attachment',
                file_get_contents($parameters['Package']),
                basename($parameters['Package']),
            )->post($endpoint, $parameters);
        }

        return $this->parseResponse($response->body());
    }

    /**
     * Parse the response from the SmsLink API
     *
     * @param $response
     * @return false|string
     */
    public function parseResponse($response): false|string
    {
        $response_parts = explode(';', $response);

        return json_encode([
            'type' => $response_parts[0],
            'id' => $response_parts[1],
            'message' => $response_parts[2],
            'variables' => explode(',', $response_parts[3] ?? ''),
        ]);
    }

    /**
     * Prepare the phone number
     *
     * @param string $phone_number
     * @return string
     */
    private function preparePhoneNumber(string $phone_number): string
    {
        return preg_replace("/[^0-9]/", "", str_replace("+", "00", $phone_number));
    }

    /**
     * Prepare the message
     *
     * @param string $message
     * @return string
     */
    private function prepareMessage(string $message): string
    {
        return str_replace(
            ["\r\n", "\n", ";", "\t", "\r"],
            ["\n", "%0A", "%3B", "", ""],
            trim($message)
        );
    }

    /**
     * Send a single SMS message
     *
     * @param string $to
     * @param string $message
     * @param array $options
     * @return false|string
     */
    public function send(string $to, string $message, array $options = []): false|string
    {
        $params = [
            'to' => $this->preparePhoneNumber($to),
            'message' => $this->prepareMessage($message),
        ] + $options;

        return $this->connect($params);
    }

    /**
     * Send a bulk SMS messages
     *
     * @param array $package
     * @param array $options
     * @return false|string
     */
    public function bulk($package, $options): false|string
    {
        $payload = [];

        foreach($package as $message) {
            $payload[] = implode(";", [
                "localMessageId" => $message['id'],
                "receiverNumber" => $this->preparePhoneNumber($message['to']),
                "messageText" => $this->prepareMessage($message['message']),
                "senderId" => $message['sender'] ?? 'numeric',
                "timestampProgrammed" => $message['schedule'] ?? 0
            ]);
        }

        $package_file = [
            "contentPlain" => implode("\r\n", $payload),
            "contentCompressed" => implode("\r\n", $payload)
        ];

        $package_validation = [
            "hashMD5" => [
                "contentPlain" => md5(implode("\r\n", $payload)),
                "contentCompressed" => md5(implode("\r\n", $payload))
            ]
        ];

        $package_filename = 'smslink_bulk_'.Str::uuid();

        Storage::put($package_filename, $package_file['contentCompressed']);

        $params = [
            'bulk' => true,
            'Compression' => 0,
            "MD5Plain" => $package_validation["hashMD5"]["contentPlain"],
            "MD5Compressed" => $package_validation["hashMD5"]["contentCompressed"],
            "SizePlain" => strlen($package_file["contentPlain"]),
            "SizeCompressed" => strlen($package_file["contentCompressed"]),
            "Timestamp" => date("U"),
            "Buffering" => 1,
            "Version" => $this->client_version,
            "Receivers" => sizeof($package),
            "Package" => Storage::path($package_filename)
        ] + $options;

        return $this->connect($params, 'POST');
    }

    /**
     * Get the account balance
     *
     * @return false|string
     */
    public function credit(): false|string
    {
        $params = [
            'mode' => 'account-balance',
        ];

        return $this->connect($params);
    }

    /**
     * Parse the delivery report received from the SmsLink API via a POST or a GET request
     *
     * @return false|string
     */
    public function delivery_report(): false|string
    {
        return json_encode([
            'message_id' => request('message_id'),
            'status' => request('status'),
            'timestamp' => request('timestamp'),
            'network_id' => request('network_id', ''),
            'network_type' => request('network_type', ''),
            'delivery_report' => request('delivery_report', ''),
            'connection_id' => request('connection_id', ''),
            'message_count' => request('message_count', ''),
        ]);
    }
}