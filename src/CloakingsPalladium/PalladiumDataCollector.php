<?php

namespace Cloakings\CloakingsPalladium;

use Gupalo\Json\Json;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class PalladiumDataCollector
{
    public function collectRequestData(Request $request): array
    {
        $data = [];
        if ($request->request->all()) {
            $postData = $request->request->get('data');
            if ($postData) {
                try {
                    $data = Json::toArray($postData);
                } catch (Throwable) {
                    $data = Json::toArray(stripslashes($postData));
                }
            }

            $postCrossrefSessionid = $request->request->get('crossref_sessionid');
            if ($postCrossrefSessionid) {
                $data['cr-session-id'] = $postCrossrefSessionid;
            }
        }

        return $data;
    }

    public function collectJsRequestData(Request $request): array
    {
        $data = [];
        if ($request->request->all()) {
            $postJsdata = $request->request->get('jsdata');
            if ($postJsdata) {
                try {
                    $data = Json::toArray($postJsdata);
                } catch (Throwable) {
                    $data = Json::toArray(stripslashes($postJsdata));
                }
            }
        }
        return $data;
    }

    public function collectHeaders(Request $request): array
    {
        $userParams = [
            'remote-addr' => true,
            'server-protocol' => true,
            'server-port' => true,
            'remote-port' => true,
            'query-string' => true,
            'request-scheme' => true,
            'request-uri' => true,
            'request-time-float' => true,
            'x-fb-http-engine' => true,
            'x-purpose' => true,
            'x-forwarded-for' => true,
            'x-wap-profile' => true,
            'x-forwarded-host' => true,
            'x-frame-options' => true,
        ];

        $headers = [];
        foreach ($request->server->all() as $key => $value) {
            $normalizedKey = str_replace('_', '-', mb_strtolower($key));
            if (isset($userParams[$normalizedKey]) || str_starts_with($normalizedKey, 'http')) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
