<?php

namespace Cloakings\CloakingsPalladium;

use Cloakings\CloakingsCommon\CloakerApiResponseInterface;

class PalladiumApiResponse implements CloakerApiResponseInterface
{
    private function __construct(
        public readonly bool $result, // 0 - fake, 1 - real
        public readonly string $target,
        public readonly PalladiumApiResponseModeEnum $mode,
        public readonly string $content,
        public readonly string $requestId,
        public readonly string $query,
        public readonly int $responseStatus = 0,
        public readonly array $responseHeaders = [],
        public readonly string $responseBody = '',
        public readonly float $responseTime = 0.0,
    ) {
    }

    public function isEmpty(): bool
    {
        return (
            $this->mode === PalladiumApiResponseModeEnum::Unknown ||
            ($this->target === '' && $this->content === '')
        );
    }

    public function isValidTarget(): bool
    {
        return (
            $this->target !== '' &&
            in_array($this->mode, [
                PalladiumApiResponseModeEnum::Iframe,
                PalladiumApiResponseModeEnum::Redirect,
                PalladiumApiResponseModeEnum::TargetPath,
            ], true)
        );
    }

    public static function create(array $apiResponse): self
    {
        $result = (bool)(($apiResponse['result'] ?? '') ?: false);
        $target = (string)($apiResponse['target'] ?? '');
        $mode = PalladiumApiResponseModeEnum::tryFrom((int)($apiResponse['mode'] ?? 0)) ?? PalladiumApiResponseModeEnum::Unknown;

        if ($mode === PalladiumApiResponseModeEnum::TargetPath && preg_match('#^https?:#i', $target)) {
            $mode = PalladiumApiResponseModeEnum::Redirect; // fallback to mode2
        }

        return new self(
            result: $result,
            target: $target,
            mode: $mode,
            content: (string)($apiResponse['content'] ?? ''),
            requestId: (string)($apiResponse['requestId'] ?? ''),
            query: (string)($apiResponse['query'] ?? ''),
            responseStatus: (int)($apiResponse['response_status'] ?? 0),
            responseHeaders: ($apiResponse['response_headers'] ?? []),
            responseBody: ($apiResponse['response_body'] ?? ''),
            responseTime: ($apiResponse['response_time'] ?? 0.0),
        );
    }

    public function isReal(): bool
    {
        return $this->result;
    }

    public function isFake(): bool
    {
        return !$this->isReal();
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    public function jsonSerialize(): array
    {
        return [
            'result' => $this->result,
            'target' => $this->target,
            'mode' => $this->mode->value,
            'content' => $this->content,
            'request_id' => $this->requestId,
            'query' => $this->query,
            'response_status' => $this->responseStatus,
            'response_headers' => $this->responseHeaders,
            'response_body' => $this->responseBody,
            'response_time' => $this->responseTime,
        ];
    }
}
