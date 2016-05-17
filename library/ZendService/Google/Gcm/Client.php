<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @category  ZendService
 * @package   ZendService_Google\Gcm
 */

namespace ZendService\Google\Gcm;

use ZendService\Google\Exception;
use Zend\Http\Client as HttpClient;
use Zend\Json\Json;
use Zend\Http\Response as HttpResponse;

/**
 * Google Cloud Messaging Client
 * This class allows the ability to send out messages
 * through the Google Cloud Messaging API.
 *
 * @category   ZendService
 * @package    ZendService_Google
 * @subpackage Gcm
 */
class Client
{
    /**
     * @const string Server URI
     */
    const SERVER_URI = 'https://gcm-http.googleapis.com/gcm/send';

    /**
     * @var Zend\Http\Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * Get API Key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set API Key
     *
     * @param string $apiKey
     * @return Client
     * @throws InvalidArgumentException
     */
    public function setApiKey($apiKey)
    {
        if (!is_string($apiKey) || empty($apiKey)) {
            throw new Exception\InvalidArgumentException('The api key must be a string and not empty');
        }
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Get HTTP Client
     *
     * @return Zend\Http\Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new HttpClient();
            $this->httpClient->setOptions(array('strictredirects' => true));
        }
        return $this->httpClient;
    }

    /**
     * Set HTTP Client
     *
     * @param Zend\Http\Client
     * @return Client
     */
    public function setHttpClient(HttpClient $http)
    {
        $this->httpClient = $http;
        return $this;
    }

    /**
     * Send Message
     *
     * @param Mesage $message
     * @return Response
     * @throws Exception\RuntimeException
     */
    public function send(Message $message)
    {
        $client = $this->getHttpClient();
        $client->setUri(self::SERVER_URI);
        $headers = $client->getRequest()->getHeaders();
        $headers->addHeaderLine('Authorization', 'key=' . $this->getApiKey());

        $response = $client->setHeaders($headers)
                           ->setMethod('POST')
                           ->setRawBody($message->toJson())
                           ->setEncType('application/json')
                           ->send();

        if($response->getStatusCode() == 500) {
            
            $retry = $this->getRetryAfter($response);
            $message = '500 Internal Server Error';
            
            if($retry != null) {
                $message .= '; Retry After: '.$retry;
            }
            
            throw new Exception\RuntimeException($message, 500, null, array('retry-after' => $retry));
            
        } elseif($response->getStatusCode() == 503) {
            
            $retry = $this->getRetryAfter($response);
            $message = '503 Server Unavailable';
            
            if($retry != null) {
                $message .= '; Retry After: '.$retry;
            }
            
            throw new Exception\RuntimeException($message, 500, null, array('retry-after' => $retry));
            
        } elseif($response->getStatusCode() == 401) {
            
            throw new Exception\RuntimeException('401 Forbidden; Authentication Error', 401);
            
        } elseif($response->getStatusCode() == 400) {
            
            throw new Exception\RuntimeException(sprintf('400 Bad Request; %s', $response->getBody()), 400);
            
        }

        if (!$response = Json::decode($response->getBody(), Json::TYPE_ARRAY)) {
            throw new Exception\RuntimeException('Response body did not contain a valid JSON response');
        }

        return new Response($response, $message);
    }
    
    /**
     * Return the retry after header value if available
     * 
     * @param HttpResponse $response
     * @return null|integer
     */
    private function getRetryAfter(HttpResponse $response)
    {
        $retry = $response->getHeaders()->get('Retry-After');
        
        if($retry !== false) {
            return $retry->getFieldValue();
        } else {
            return null;
        }
    }
}
