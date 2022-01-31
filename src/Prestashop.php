<?php

namespace Pdik\LaravelPrestashop;

use Pdik\LaravelPrestashop\Exceptions\PrestashopWebserviceException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SimpleXMLElement;

class Prestashop
{
    /** @var string Shop url */
    protected $shop_url;

    /** @var string api endpoint */
    protected $api_endpoint = '/api';

    /** @var string Authentication key */
    protected $Api_key;
    protected $debug = false;

    protected $client;
    /**
     * @var callable[]
     */
    protected $middleWares = [];

    /**
     * @var string|null
     */
    public $nextUrl = null;

    /**
     * @throws PrestaShopWebserviceException
     */
    function __construct()
    {
        $this->shop_url = config('prestaconfig.shop_url');
        $this->Api_key = config('prestaconfig.token');
        if ($this->needsAuthentication()) {
            throw new PrestaShopWebserviceException("Api needs Prestashop token");
        }
        return $this->client();
    }

    /**
     * @throws PrestaShopWebserviceException
     */
    public function connect(): Client
    {
        if ($this->needsAuthentication()) {
            throw new PrestaShopWebserviceException("Api needs Prestashop token");
        }
        return $this->client();
    }

    /**
     * @throws PrestaShopWebserviceException
     */
    public function checkCustomerExist($email)
    {
        //   $params = ['filter' => $email, 'display' => '[company,id]'];
        return $this->get('customers', ['filter[email]' => $email, 'display' => '[company,id]']);
    }

    /**
     * @param  string  $method
     * @param  string  $endpoint
     * @param  mixed  $body
     * @param  array  $params
     * @param  array  $headers
     *
     * @return Request
     */
    private function createRequest(
        string $method,
        string $endpoint,
        $body = null,
        array $params = [],
        array $headers = []
    ): Request {
        // Add default json headers to the request
        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Output-Format' => "JSON"
        ]);

        // If we have a token, sign the request
        if (!empty($this->Api_key)) {
            $headers['Authorization'] = 'Basic '.$this->getAccessToken();
        }

        // Create param string

            $endpoint .= strpos($endpoint, '?') === false ? '?' : '&';

            //Check if params match the available options
            $options = array(
                'filter',
                'display',
                'sort',
                'limit',
                'id_shop',
                'id_group_shop',
                'schema',
                'language',
                'date',
                'price'
            );
            $url_params = [];
            //Added full display always
            if (!array_key_exists('display', $params)) {
                $params['display'] = 'full';
            }
            foreach ($options as $p) {
                foreach ($params as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $o;
                    }
                }
            }
            $endpoint .= http_build_query($url_params);


        // Create the request
        return new Request($method, $endpoint, $headers, $body);
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param  string  $url
     * @param  array  $params
     * @param  array  $headers
     *
     * @return mixed
     * @throws PrestaShopWebserviceException
     *
     */
    public function get(string $url, array $params = [], array $headers = [])
    {
        $url = $this->formatUrl($url, $url == $this->nextUrl);
        try {
            $request = $this->createRequest('GET', $url, null, $params, $headers);
            $response = $this->client()->send($request);

            return $this->parseResponse($response);
        } catch (GuzzleException $e) {
            throw new PrestaShopWebserviceException($e);
        }
    }

    /**
     * @param $url
     * @param $body
     * @return mixed|void
     * @throws GuzzleException
     * @throws PrestaShopWebserviceException
     */
    public function post($url, $body)
    {
        $url = $this->formatUrl($url);
        try {
            $request = $this->createRequest('POST', $url, $body);
            $response = $this->client()->send($request);

            return $this->parseResponse($response);
        } catch (Exception $e) {
            $this->parseExceptionForErrorMessages($e);
        }
    }


    /**
     * @param  string  $url
     * @param  mixed  $body
     * @return mixed
     * @throws PrestaShopWebserviceException
     *
     */
    public function put($url, $body)
    {
        $url = $this->formatUrl($url);

        try {
            $request = $this->createRequest('PUT', $url, $body);
            $response = $this->client()->send($request);

            return $this->parseResponse($response);
        } catch (Exception $e) {
            $this->parseExceptionForErrorMessages($e);
        }
    }

    /**
     * @param  string  $url
     *
     * @return mixed
     * @throws PrestaShopWebserviceException
     *
     */
    public function delete($url)
    {
        $url = $this->formatUrl($url);
        try {
            $request = $this->createRequest('DELETE', $url);
            $response = $this->client()->send($request);
            return $this->parseResponse($response);
        } catch (Exception $e) {
            $this->parseExceptionForErrorMessages($e);
        }
    }

    /**
     * @param  Response  $response
     * @param  bool  $returnSingleIfPossible
     *
     * @return mixed
     * @throws PrestaShopWebserviceException
     *
     */
    private function parseResponse(Response $response, $returnSingleIfPossible = true)
    {

        try {
            $this->checkStatusCode($response->getStatusCode());

            Psr7\Message::rewindBody($response);

            $json = json_decode($response->getBody()->getContents(), true);
            if (false === is_array($json)) {
                throw new PrestaShopWebserviceException('Json decode failed. Got response: '.$response->getBody()->getContents());
            }

            return $json;
        } catch (\RuntimeException $e) {
            throw new PrestaShopWebserviceException($e->getMessage());
        }
    }

    /**
     * @param  Response  $response
     *
     * @return mixed
     * @throws PrestaShopWebserviceException
     *
     */
    private function parseResponseXml(Response $response)
    {
        try {
            if ($response->getStatusCode() === 204) {
                return [];
            }

            $answer = [];
            Psr7\Message::rewindBody($response);
            $simpleXml = new SimpleXMLElement($response->getBody()->getContents());
            dd($simpleXml);
//            foreach ($simpleXml->Messages as $message) {
//                $keyAlt = (string)$message->Message->Topic->Data->attributes()['keyAlt'];
//                $answer[$keyAlt] = (string)$message->Message->Description;
//            }

            // return $answer;
        } catch (\RuntimeException $e) {
            throw new PrestaShopWebserviceException($e->getMessage());
        }
    }

    /**
     * Load XML from string. Can throw exception
     *
     * @param  string  $response  String from a CURL response
     *
     * @return SimpleXMLElement status_code, response
     * @throws PrestaShopWebserviceException
     */
    protected function parseXML($response)
    {
        if ($response != '') {
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string(trim($response), 'SimpleXMLElement', LIBXML_NOCDATA);
            if (libxml_get_errors()) {
                $msg = var_export(libxml_get_errors(), true);
                libxml_clear_errors();
                throw new PrestaShopWebserviceException('HTTP XML response is not parsable: '.$msg);
            }
            return $xml;
        } else {
            throw new PrestaShopWebserviceException('HTTP response is empty');
        }
    }

    private function formatUrl($endPoint, $formatNextUrl = false): string
    {
        if ($formatNextUrl) {
            return $endPoint;
        }
        return implode('/', [
            $this->getApiUrl(),
            $endPoint,
        ]);
    }

    /**
     * @return string
     */
    private function getApiUrl(): string
    {
        return $this->shop_url.$this->api_endpoint;
    }

    /**
     * @param  string  $endpoint
     */
    public function setApiEndPoint($endpoint)
    {
        $this->api_endpoint = $endpoint;
    }

    /**
     * @return Client
     */
    private function client(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $handlerStack = HandlerStack::create();
        foreach ($this->middleWares as $middleWare) {
            $handlerStack->push($middleWare);
        }

        $this->client = new Client([
            'http_errors' => true,
            'handler' => $handlerStack,
            'expect' => false,
        ]);

        return $this->client;
    }

    /**
     * Insert a Middleware for the Guzzle-Client.
     *
     * @param  callable  $middleWare
     */
    public function insertMiddleWare(callable $middleWare)
    {
        $this->middleWares[] = $middleWare;
    }

    /**
     * Parse the reponse in the Exception to return the Exact error messages.
     *
     * @param  Exception  $e
     *
     * @throws PrestaShopWebserviceException
     */
    private function parseExceptionForErrorMessages(Exception $e)
    {
        if (!$e instanceof BadResponseException) {
            throw new PrestaShopWebserviceException($e->getMessage(), 0, $e);
        }

        $response = $e->getResponse();

        Psr7\Message::rewindBody($response);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = json_decode($responseBody, true);

        if (!is_null($decodedResponseBody) && isset($decodedResponseBody['error']['message']['value'])) {
            $errorMessage = $decodedResponseBody['error']['message']['value'];
        } else {
            $errorMessage = $responseBody;
        }

        if ($reason = $response->getHeaderLine('Reason')) {
            $errorMessage .= " (Reason: {$reason})";
        }

        throw new PrestaShopWebserviceException('Error '.$response->getStatusCode().': '.$errorMessage,
            $response->getStatusCode(), $e);
    }

    public function getAccessToken()
    {
        return base64_encode($this->Api_key.':');
    }

    /**
     * Take the status code and throw an exception if the server didn't return 200 or 201 code
     *
     * @param  int  $status_code  Status code of an HTTP return
     *
     * @throws PrestaShopWebserviceException if HTTP status code is not 200 or 201
     */
    protected function checkStatusCode($status_code)
    {
        $error_label = 'This call to PrestaShop Web Services failed and returned an HTTP status of %d. That means: %s.';
        switch ($status_code) {
            case 200:
            case 201:
                break;
            case 204:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'No content'));
                break;
            case 400:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Bad Request'));
                break;
            case 401:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Unauthorized'));
                break;
            case 404:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Not Found'));
                break;
            case 405:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Method Not Allowed'));
                break;
            case 500:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Internal Server Error'));
                break;
            default:
                throw new PrestaShopWebserviceException(
                    'This call to PrestaShop Web Services returned an unexpected HTTP status of:'.$status_code
                );
        }
    }

    public function needsAuthentication(): bool
    {
        return empty($this->Api_key);
    }
}
