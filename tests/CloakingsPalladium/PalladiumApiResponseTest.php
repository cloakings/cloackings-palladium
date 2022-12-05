<?php

namespace Cloakings\Tests\CloakingsPalladium;

use Cloakings\CloakingsPalladium\PalladiumApiResponse;
use Cloakings\CloakingsPalladium\PalladiumApiResponseModeEnum;
use PHPUnit\Framework\TestCase;

class PalladiumApiResponseTest extends TestCase
{
    public function testCreate(): void
    {
        $r = PalladiumApiResponse::create([
            'result' => 1,
            'target' => 'fake.php',
            'mode' => 2,
            'content' => 'test',
        ]);

        self::assertTrue($r->result);
        self::assertSame('fake.php', $r->target);
        self::assertSame(PalladiumApiResponseModeEnum::Redirect, $r->mode);
        self::assertSame('test', $r->content);
    }
}
