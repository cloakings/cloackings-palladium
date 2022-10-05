<?php

namespace Cloakings\CloakingsPalladium;

class PalladiumApiResponse
{
    private function __construct(
        public readonly bool $status,
        public readonly string $target,
        public readonly PalladiumApiResponseModeEnum $mode,
        public readonly string $content,
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
        $status = (bool)(($apiResponse['result'] ?? '') ?: false);
        $target = (string)($apiResponse['target'] ?? '');
        $mode = PalladiumApiResponseModeEnum::tryFrom((int)($apiResponse['mode'] ?? 0)) ?? PalladiumApiResponseModeEnum::Unknown;
        $content = (string)($apiResponse['content'] ?? '');

        if ($mode === PalladiumApiResponseModeEnum::TargetPath && preg_match('#^https?:#i', $target)) {
            $mode = PalladiumApiResponseModeEnum::Redirect; // fallback to mode2
        }

        return new self(
            status: $status,
            target: $target,
            mode: $mode,
            content: $content,
            responseStatus: (int)($apiResponse['response_status'] ?? 0),
            responseHeaders: ($apiResponse['response_headers'] ?? []),
            responseBody: ($apiResponse['response_body'] ?? ''),
            responseTime: ($apiResponse['response_time'] ?? 0.0),
        );
    }
}
