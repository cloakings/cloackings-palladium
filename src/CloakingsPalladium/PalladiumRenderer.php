<?php

namespace Cloakings\CloakingsPalladium;

use Cloakings\CloakingsCommon\CloakerResult;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PalladiumRenderer
{
    public function __construct(
        private readonly string $baseIncludeDir,
    ) {
    }

    public function render(CloakerResult $cloakerResult): Response
    {
        $mode = PalladiumApiResponseModeEnum::tryFrom((int)$cloakerResult->response->headers->get('x-mode', 0)) ?? PalladiumApiResponseModeEnum::Unknown;
        $target = (string)$cloakerResult->response->headers->get('x-target', '');

        if ($mode === PalladiumApiResponseModeEnum::Iframe) {
            $response = $this->displayIframe($target);
        } elseif ($mode === PalladiumApiResponseModeEnum::Redirect) {
            $response = new RedirectResponse($target);
        } elseif ($mode === PalladiumApiResponseModeEnum::TargetPath) {
            $targetUrlParts = parse_url($target);
            parse_str($targetUrlParts['query'], $_GET);
            $response = new Response($this->injectHideFormNotification($this->include($this->sanitizePath($targetUrlParts['path']))));
        } elseif ($mode === PalladiumApiResponseModeEnum::Content || $mode === PalladiumApiResponseModeEnum::Unknown) {
            $response = new Response($cloakerResult->response->getContent(), $cloakerResult->response->getStatusCode());
        } elseif ($mode === PalladiumApiResponseModeEnum::Empty || $mode === PalladiumApiResponseModeEnum::EmptyIfEmptyStatus) {
            $response = new Response();
        } else {
            $path = $this->sanitizePath($target);
            if ($this->isLocal($path)) {
                $response = new Response($this->injectHideFormNotification($this->include($path)));
            } else {
                $response = new Response('404 Not Found', Response::HTTP_NOT_FOUND);
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    private function displayIframe($target): Response
    {
        $content = <<<EOD
            <html>
            <head><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
            <body><iframe src="{target}" style="width:100%;height:100%;position:absolute;top:0;left:0;z-index:999999;border:none;"></iframe></body>
            </html>
        EOD;
        $content = str_replace('{target}', htmlspecialchars($target), $content);

        return new Response($this->injectHideFormNotification($content));
    }

    private function getDefaultAnswer(): Response
    {
        $content = implode("\n", [
            '<h1>500 Internal Server Error</h1>',
            '<p>The request was unsuccessful due to an unexpected condition encountered by the server.</p>',
        ]);

        return new Response($content, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function sanitizePath($path): string
    {
        return sprintf('%s/%s', $this->baseIncludeDir, ltrim($path, '/'));
    }

    private function isLocal($path): bool
    {
        $url = parse_url($path); // do not validate url via filter_var

        return !isset($url['scheme'], $url['host']);
    }

    private function include(string $filename): string
    {
        ob_start();
        include($filename);

        return ob_get_clean();
    }

    private function injectHideFormNotification(string $s): string
    {
        $inject = '<script>if ( window.history.replaceState ) {window.history.replaceState( null, null, window.location.href );}</script>';

        preg_replace('#<body[^>]*>#mi', '$0'.$inject, $s, 1, $count);
        if (!$count) {
            $s = $inject.$s;
        }

        return $s;
    }
}
