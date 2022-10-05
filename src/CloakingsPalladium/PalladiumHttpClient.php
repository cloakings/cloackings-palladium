<?php

namespace Cloakings\CloakingsPalladium;

use Gupalo\Json\Json;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class PalladiumHttpClient
{
    private const SERVICE_NAME = 'palladium';

    public function __construct(
        private readonly string $apiUrl = 'https://request.palladium.expert',
        private readonly HttpClientInterface $httpClient = new CurlHttpClient(),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function execute(array $params): PalladiumApiResponse
    {
        try {
            $startTime = microtime(true);
            $response = $this->httpClient->request(Request::METHOD_POST, $this->apiUrl, [
                'body' => http_build_query($params),
                'verify_peer' => false,
                'verify_host' => false,
                'max_duration' => 4000, // ms
            ]);
            $time = microtime(true) - $startTime;

            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
            $content = $response->getContent();
            $data = array_merge([
                Json::toArray(trim($content, " \t\n\r\0\x0B\"")),
                'response_status' => $status,
                'response_headers' => $headers,
                'response_body' => $content,
                'response_time' => $time,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('cloaking_request_error', ['service' => self::SERVICE_NAME, 'params' => $params, 'status' => $status ?? 0, 'headers' => $headers ?? [], 'content' => $content ?? '', 'exception' => $e]);

            return PalladiumApiResponse::create([]);
        }

        $this->logger->error('cloaking_request', ['service' => self::SERVICE_NAME, 'params' => $params, 'status' => $status ?? 0, 'headers' => $headers ?? [], 'content' => $content ?? '', 'time' => $time ?? 0]);

        return PalladiumApiResponse::create($data ?? []);
    }
}
