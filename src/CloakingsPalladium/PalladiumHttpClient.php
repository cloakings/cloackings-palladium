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
        private readonly string $serverUrl = 'https://request.palladium.expert',
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function curlSend(array $params): ?PalladiumApiResponse
    {
        $curl = $this->getCurl($params);
        if (!$curl) {
            return null;
        }

        $result = null;
        $status = 0;
        $responseString = curl_exec($curl);
        if ($responseString) {
            try {
                $responseArray = Json::toArray($responseString);
                $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($status === Response::HTTP_OK) {
                    $result = $responseArray;
                }
            } catch (Throwable $e) {
                $this->logger->error('palladium_request_error', ['status' => $status, 'params' => $params, 'response_string' => $responseString, 'exception' => $e]);
            }
        } else {
            $this->logger->error('palladium_request_empty', ['params' => $params]);
        }
        $this->logger->info('palladium_request', ['result' => $result, 'status' => $status, 'params' => $params]);

        return $result ? PalladiumApiResponse::create($result) : null;
    }

    /** @noinspection CurlSslServerSpoofingInspection */
    private function getCurl(array $params): ?CurlHandle
    {
        $curl = curl_init($this->serverUrl);

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
            $this->logger->error('palladium_request_error', ['reason' => 'no curl']);
            $curl = null;
        }

        return $curl;
    }
}
