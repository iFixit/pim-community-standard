<?php declare(strict_types = 1);

namespace iFixit\Akeneo\iFixitBundle\EventListener;

use iFixit\Akeneo\iFixitBundle\iFixitConfig;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Uri;

class iFixitApi {
   private const IFIXIT_SECRET_HEADER = 'x-ifixit-api-secret';

   /** @var \GuzzleHttp\Client */
   private $client;

   /** @var iFixitConfig */
   private $config;

   public function __construct(iFixitConfig $config) {
      $this->config = $config;

      $settings = [
         'connect_timeout' => 1,
         'timeout' => 3,
      ];

      $this->client = new \GuzzleHttp\Client($settings);
   }

   public function post(string $apiPath, array $body = null): ResponseInterface {
      $host = $this->config->get("ifixit-api-hostname");
      $url = new Uri("https://$host/api/2.0/$apiPath");
      $request = new Request('POST', $url);
      $headers = [
         self::IFIXIT_SECRET_HEADER => $this->config->get('ifixit-api-secret'),
      ];

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
