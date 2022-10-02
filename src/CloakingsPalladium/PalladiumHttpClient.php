<?php

namespace Cloakings\CloakingsPalladium;

use CurlHandle;
use Gupalo\Json\Json;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PalladiumHttpClient
{
    public function __construct(
        private readonly string $apiUrl = 'https://request.palladium.expert',
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function execute(array $params): PalladiumApiResponse
    {
        $curl = $this->getCurl($params);
        if (!$curl) {
            return PalladiumApiResponse::create([]);
        }

        $result = null;
        $status = 0;
        $time = microtime(true);
        $responseString = curl_exec($curl);
        if ($responseString) {
            try {
                $responseArray = Json::toArray($responseString);
                $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($status === Response::HTTP_OK) {
                    $result = $responseArray;
                }
            } catch (Throwable $e) {
                $this->logger->error('cloaking_request_error', ['service' => 'palladium', 'status' => $status, 'params' => $params, 'response_string' => $responseString, 'time' => $this->elapsedTime($time), 'exception' => $e]);
            }
        } else {
            $this->logger->error('cloaking_request_empty', ['service' => 'palladium', 'params' => $params, 'time' => $this->elapsedTime($time)]);
        }
        $this->logger->info('cloaking_request', ['service' => 'palladium', 'result' => $result, 'status' => $status, 'params' => $params, 'time' => $this->elapsedTime($time)]);

        return PalladiumApiResponse::create($result ?: []);
    }

    /** @noinspection CurlSslServerSpoofingInspection */
    private function getCurl(array $params): ?CurlHandle
    {
        $curl = curl_init($this->apiUrl);

        if ($curl) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($curl, CURLOPT_TIMEOUT, 4);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 4000);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        } else {
            $this->logger->error('cloaking_request_error', ['service' => 'palladium', 'reason' => 'no curl']);
            $curl = null;
        }

        return $curl;
    }

    private function elapsedTime(float $startTime): float
    {
        return round(microtime(true) - $startTime, 4);
    }
}
