<?php

namespace Cloakings\CloakingsPalladium;

use Cloakings\CloakingsCommon\CloakerInterface;
use Cloakings\CloakingsCommon\CloakerResult;
use Cloakings\CloakingsCommon\CloakModeEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PalladiumCloaker implements CloakerInterface
{
    private PalladiumDataCollector $dataCollector;
    private PalladiumHttpClient $httpClient;

    public function __construct(
        private readonly int $clientId,
        private readonly string $clientCompany,
        private readonly string $clientSecret,
        private readonly PalladiumTrafficSourceEnum $trafficSource = PalladiumTrafficSourceEnum::Adwords,
        private readonly string $fakeTargetContains = 'fake',
        private readonly string $realTargetContains = 'real',
        PalladiumDataCollector $dataCollector = null,
        PalladiumHttpClient $httpClient = null,
    ) {
        $this->dataCollector = $dataCollector ?? new PalladiumDataCollector();
        $this->httpClient = $httpClient ?? new PalladiumHttpClient();
    }

    public function handle(Request $request): CloakerResult
    {
        if ((int)$request->query->get('dr_jsess', '') === 1) {
            return new CloakerResult(CloakModeEnum::Response, new Response());
        }

        $params = $this->collectParams($request);
        $apiResponse = $this->httpClient->curlSend($params);

        $mode = match (true) {
            (!$apiResponse || !$apiResponse->isValidTarget()) => CloakModeEnum::Error,
            str_contains($apiResponse->target, $this->fakeTargetContains) => CloakModeEnum::Fake,
            str_contains($apiResponse->target, $this->realTargetContains) => CloakModeEnum::Real,
            default => CloakModeEnum::Response,
        };

        $response = new Response(
            content: $apiResponse->content,
            headers: [
                'x-mode' => $apiResponse->mode->value,
                'x-target' => $apiResponse->target,
            ],
        );

        return new CloakerResult($mode, $response);
    }

    private function collectParams(Request $request): array
    {
        return [
            'request' => $this->dataCollector->collectRequestData($request),
            'jsrequest' => $this->dataCollector->collectJsRequestData($request),
            'server' => array_merge(
                $this->dataCollector->collectHeaders($request),
                ['bannerSource' => $this->trafficSource->value],
            ),
            'auth' => [
                'clientId' => $this->clientId,
                'clientCompany' => $this->clientCompany,
                'clientSecret' => $this->clientSecret,
            ],
        ];
    }
}
