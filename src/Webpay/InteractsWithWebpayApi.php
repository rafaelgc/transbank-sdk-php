<?php

namespace Transbank\Webpay;

use GuzzleHttp\Exception\GuzzleException;
use Transbank\Utils\HttpClient;
use Transbank\Webpay\Exceptions\FailedRequestCapturedData;
use Transbank\Webpay\Exceptions\TransbankException;
use Transbank\Webpay\Exceptions\WebpayRequestException;
use Transbank\Webpay\Modal\Exceptions\TransactionCreateException;
use Transbank\Webpay\Modal\WebpayModal;

/**
 * Trait InteractsWithWebpayApi
 *
 * @package Transbank\Webpay
 */
trait InteractsWithWebpayApi
{
    protected static $lastResponse = null;
    /**
     * @param $method
     * @param $endpoint
     * @param $payload
     * @param Options $options
     * @param HttpClient|null $client
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException|WebpayRequestException
     */
    public static function request(
        $method,
        $endpoint,
        $payload,
        Options $options,
        HttpClient $client = null
    ) {
        $headers = [
            "Tbk-Api-Key-Id" => $options->getCommerceCode(),
            "Tbk-Api-Key-Secret" => $options->getApiKey()
        ];
        
        if ($client == null) {
            $client = new HttpClient();
        }
        
        $baseUrl = static::getBaseUrl($options->getIntegrationType());
        $response = $client->perform($method, $baseUrl . $endpoint, $payload, ['headers' => $headers]);
        static::$lastResponse = $response;
        $httpCode = $response->getStatusCode();
        
        if (!in_array($httpCode, [200, 204])) {
            $reason = $response->getReasonPhrase();
            $message = "Could not obtain a response from the service: $reason (HTTP code $httpCode)";
            $body = json_decode($response->getBody(), true);
            $tbkErrorMessage = null;
            if (isset($body["error_message"])) {
                $tbkErrorMessage = $body["error_message"];
                $message = "Transbank API REST Error: $tbkErrorMessage | $message";
            }
            $failedRequest = new FailedRequestCapturedData($method, $baseUrl, $endpoint, $payload, $headers);
            throw new WebpayRequestException($message, $tbkErrorMessage, $httpCode, $failedRequest);
        }
        
        return json_decode($response->getBody(), true);
    }
    
    /**
     * @param $integrationEnvironment
     * @return mixed|string
     */
    public static function getBaseUrl($integrationEnvironment)
    {
        return WebpayModal::getIntegrationTypeUrl($integrationEnvironment);
    }
    /**
     * @return null
     */
    public static function getLastResponse()
    {
        return self::$lastResponse;
    }
}
