<?php

namespace Cloakings\CloakingsPalladium;

class PalladiumApiResponse
{
    private function __construct(
        public readonly bool $status,
        public readonly string $target,
        public readonly PalladiumApiResponseModeEnum $mode,
        public readonly string $content,
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
        $status = (bool)($apiResponse['result'] ?: false);
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
        );
    }
}
