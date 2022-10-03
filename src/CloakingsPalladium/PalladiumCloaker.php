<?php

namespace Cloakings\CloakingsPalladium;

use Cloakings\CloakingsCommon\CloakerInterface;
use Cloakings\CloakingsCommon\CloakerResult;
use Cloakings\CloakingsCommon\CloakModeEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PalladiumCloaker implements CloakerInterface
{
    public function __construct(
        private readonly int $clientId,
        private readonly string $clientCompany,
        private readonly string $clientSecret,
        private readonly PalladiumTrafficSourceEnum $trafficSource = PalladiumTrafficSourceEnum::Adwords,
        private readonly string $fakeTargetContains = 'fake',
        private readonly string $realTargetContains = 'real',
        private readonly PalladiumDataCollector $dataCollector = new PalladiumDataCollector(),
        private readonly PalladiumHttpClient $httpClient = new PalladiumHttpClient(),
    ) {
    }

    public function handle(Request $request): CloakerResult
    {
        if ((int)$request->query->get('dr_jsess', '') === 1) {
            return new CloakerResult(CloakModeEnum::Response, new Response());
        }

        $params = $this->collectParams($request);
        $apiResponse = $this->httpClient->execute($params);

        return $this->createResult($apiResponse);
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

    private function createResult(PalladiumApiResponse $apiResponse): CloakerResult
    {
        return new CloakerResult(
            mode: match (true) {
                str_contains($apiResponse->target, $this->fakeTargetContains) => CloakModeEnum::Fake,
                str_contains($apiResponse->target, $this->realTargetContains) => CloakModeEnum::Real,
                !$apiResponse->status => CloakModeEnum::Error,
                default => CloakModeEnum::Response,
            },
            response: new Response($apiResponse->content),
            apiResponse: $apiResponse,
            params: [
                'mode' => $apiResponse->mode->value,
                'target' => $apiResponse->target,
            ]
        );
    }
}
