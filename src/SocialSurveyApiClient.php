<?php

namespace SocialSurveyApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\XmlParseException;
use GuzzleHttp\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SocialSurveyApi\Model\Response;

/**
 * Social Survey PHP API Client
 *
 * @author Chris Carrel <support@alquemie.net>
 */
class SocialSurveyApiClient
{
    /**
     * @var string
     */
    protected $url = 'https://api.socialsurvey.me/v2/';

    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $authkey;

    /**
     * @var string
     */
    protected $userFilter = "";

    /**
     * @var boolean
     */
    protected $includeTeamFilter = true;

    /**
     * @var int
     */
    protected $responseCode = 0;

    /**
     * @var string
     */
    protected $responseMessage = null;

    /**
     * @var array
     */
    protected $response;

    /**
     * @var array
     */
    protected $results;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @var array
     *
     * Valid API functions
     */
    public static $validMethods = [
        'surveys',
        'surveycount'
    ];

    /**
     * @param string $zwsid
     * @param string|null $url
     */
    public function __construct($authkey, $url = null)
    {
        $this->authkey = $authkey;

        if ($url) {
            $this->url = $url;
        }
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    protected function getAuthKey()
    {
        return $this->authkey;
    }

    /**
     * @param GuzzleClientInterface $client
     *
     * @return SocialSurveyApiClien
     */
    public function setClient(GuzzleClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return GuzzleClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client(
                [
                    'base_uri' => $this->url,
                    'allow_redirects' => false,
                    'cookies'         => true
                ]
            );
        }

        return $this->client;
    }

    public function setUser($user) {
        $this->userFilter = $user;
    }

    public function includeTeam($teamVal) {
        $this->includeTeamFilter = $teamVal;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return Response
     */
    public function execute($name, $arguments)
    {
        if (!in_array($name, self::$validMethods)) {
            throw new SocialSurveyException(sprintf('Invalid Social Survey API method (%s)', $name));
        }

        $arguments = array_merge( ['user' => $this->userFilter, 'includeManagedTeam' => $this->includeTeamFilter], $arguments);

        return $this->doRequest($name, $arguments);
    }

    /**
     * @param string $call
     * @param array $params
     *
     * @return Response
     * @throws SocialSurveyException
     */
    protected function doRequest($call, array $params)
    {
        if (!$this->getAuthKey()) {
            throw new SocialSurveyException('Missing Authorization Key');
        }

        $response = $this->getClient()->get(
            $call,
            [
                'headers' => ['Authorization' => 'Basic ' . $this->getAuthKey() ],
                'query' => $params,
            ]
        );

        return $this->parseResponse($call, $response);
    }

    /**
     * @param string $call
     * @param ResponseInterface $rawResponse
     *
     * @return Response
     */
    protected function parseResponse($call, ResponseInterface $rawResponse)
    {
        $response      = new Response();

        if ($rawResponse->getStatusCode() === '200') {
            try {
                $responseArray = json_decode($rawResponse->getBody(true)->getContents());
            } catch (Exception $e) {
                $this->fail($response, $rawResponse, true, $e);

                return $response;
            }

            $response->setMethod($call);

            if (!array_key_exists('msg', $responseArray)) {
                $this->fail($response, $rawResponse, false);
            } else {
                $response->setCode(intval($responseArray['msg']['code']));
                $response->setMessage($responseArray['msg']['message']);
            }

            if ($response->isSuccessful() && array_key_exists('data', $responseArray)) {
                $response->setData($responseArray['data']);
            }
        } else {
            $this->fail($response, $rawResponse, true);
        }

        return $response;
    }

    /**
     * @param Response $response
     * @param ResponseInterface $rawResponse
     * @param bool $logException
     * @param null $exception
     */
    private function fail(Response $response, ResponseInterface $rawResponse, $logException = false, $exception = null)
    {
        $response->setCode(999);
        $response->setMessage('Invalid response received.');

        if ($logException && $this->logger) {
            $this->logger->error(
                new \Exception(
                    sprintf(
                        'Failed Social Survey call.  Status code: %s, Response string: %s',
                        $rawResponse->getStatusCode(),
                        (string) $rawResponse->getBody()
                    ),
                    0,
                    $exception
                )
            );
        }
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
