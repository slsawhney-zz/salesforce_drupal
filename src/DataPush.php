<?php

namespace Drupal\sf_drupal;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Link;

class DataPush
{
    protected $sf_api_url;
    protected $sf_consumer_key;
    protected $sf_consumer_secret;
    protected $sf_username;
    protected $sf_password;
    protected $sf_api_token_url;

    /**
     * The HTTP client to fetch the Image data with.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Constructs a new DataPush object.
     *
     * @param \GuzzleHttp\ClientInterface $http_client
     */
    public function __construct(ClientInterface $http_client)
    {
        $this->httpClient = $http_client;
        $this->sf_api_url = \Drupal::config('sf_drupal.adminsettings')->get('sf_api_url');
        $this->sf_consumer_key = \Drupal::config('sf_drupal.adminsettings')->get('sf_consumer_key');
        $this->sf_consumer_secret = \Drupal::config('sf_drupal.adminsettings')->get('sf_consumer_secret');
        $this->sf_username = \Drupal::config('sf_drupal.adminsettings')->get('sf_username');
        $this->sf_password = \Drupal::config('sf_drupal.adminsettings')->get('sf_password');
        $this->sf_api_token_url = $this->getApiTokeUrl();
    }

    /**
     * @return string
     */
    private function getApiTokeUrl()
    {
        $rawUrl = '%s?grant_type=password&client_id=%s&client_secret=%s&username=%s&password=%s';
        $apiTokeUrl = sprintf(
                    $rawUrl,
                    $this->sf_api_url,
                    $this->sf_consumer_key,
                    $this->sf_consumer_secret,
                    $this->sf_username,
                    $this->sf_password
                );

        return $apiTokeUrl;
    }

    /**
     * @param string $apiToken
     * @param string $title
     * @param string $body
     *
     * @return string
     */
    private function pushData($apiToken, $title, $body)
    {
        try {
            $request = $this->httpClient->post(\Drupal::config('sf_drupal.adminsettings')->get('sf_instance_url').'/services/data/v39.0/sobjects/Lead', [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'Title' => $title,
                    'LastName' => $title,
                    'Company' => $title,
                    'Description' => trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($body)))))),
                ],
            ]);

            return $response = json_decode($request->getBody());
        } catch (ClientException | RequestException | TransferException | BadResponseException $e) {
            watchdog_exception('sf_drupal', $e);
        }
    }

    /**
     * @param string $apiTokeUrl
     *
     * @return string
     */
    public function getApiToken($apiTokeUrl)
    {
        try {
            $request = $this->httpClient->post($apiTokeUrl);

            return $response = json_decode($request->getBody());
        } catch (ClientException | RequestException | TransferException | BadResponseException $e) {
            watchdog_exception('sf_drupal', $e);
        }
    }

    /**
     * @param string $title
     * @param string $body
     */
    public function sendDataToSalesForce($title, $body)
    {
        $apiTokenData = $this->getApiToken($this->sf_api_token_url);
        $apiToken = $apiTokenData->access_token;

        if (isset($apiToken)) {
            $apiResponse = $this->pushData($apiToken, $title, $body);
            if (isset($apiResponse->success)) {
                drupal_set_message(t('Data Pushed to Salesforce "Lead Object" successfully'), 'status');
            } else {
                drupal_set_message(t('Not able to Push data to Salesforce. Please check Drupal log message for Detail'), 'warning');
            }
        } else {
            $configurationLink = Link::fromTextAndUrl(
                                            'Configuration',
                                            \Drupal\Core\Url::fromUri('internal:/admin/config/sf_drupal/adminsettings')
                                        )->toString();
            drupal_set_message(t(
                    '@$configurationLink are not correct. Please verify them.',
                    array(
                        '@$configurationLink' => $configurationLink,
                    )
                ), 'warning');
        }
    }
}
