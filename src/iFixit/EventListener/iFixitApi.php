<?php declare(strict_types = 1);

namespace iFixit\Akeneo\iFixitBundle\EventListener;

use iFixit\Akeneo\iFixitBundle\iFixitConfig;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

class iFixitApi {
   private const IFIXIT_SECRET_HEADER = 'x-ifixit-api-secret';

   /** @var \GuzzleHttp\Client */
   private $client;

   /** @var iFixitConfig */
   private $config;

   /** @var LoggerInterface */
   private $logger;

   public function __construct(iFixitConfig $config, LoggerInterface $logger) {
      $this->config = $config;
      $this->logger = $logger;

      $settings = [
         'connect_timeout' => 1,
         'timeout' => 3,
      ];

      $this->client = new \GuzzleHttp\Client($settings);
   }

   public function log($message, $context = []) {
      $this->logger->debug($message, $context);
   }

   public function post(string $apiPath, array $body = null): ResponseInterface {
      $host = $this->config->get("ifixit-api-hostname");
      $url = new Uri("https://$host/api/2.0/$apiPath");
      $request = new Request('POST', $url);
      $headers = [
         self::IFIXIT_SECRET_HEADER => $this->config->get('ifixit-api-secret'),
      ];

      $this->logger->debug("POST:$apiPath", $body, $headers);
      $this->logger->debug("HEADERS:", $headers);

      $response = $this->client->send($request, [
         'json' => $body,
         'follow_redirects' => false,
         'headers' => $headers
      ]);

      $code = $response->getStatusCode();
      if ($code < 200 || $code >= 300) {
         throw new \Exception("iFixit api failed:$apiPath with code:$code");
      }
      return $response;
   }
}
