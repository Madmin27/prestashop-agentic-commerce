<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Support\HtaccessRules;

final class HtaccessRulesTest extends TestCase
{
    public function testApplyIsIdempotentAndContainsDiscoveryAndDataRoutes(): void
    {
        $first = HtaccessRules::apply("# ~~start~~\n");
        $second = HtaccessRules::apply($first);

        self::assertSame($first, $second);
        self::assertStringContainsString('.well-known/ai-catalog', $first);
        self::assertStringContainsString('ai/v1/s([1-9][0-9]*)', $first);
        self::assertStringContainsString('canonical_variant_id=$5', $first);
    }

    public function testRemovePreservesPrestaShopBlock(): void
    {
        $source = HtaccessRules::apply("before\n# ~~start~~\nafter\n");
        $removed = HtaccessRules::remove($source);

        self::assertStringNotContainsString(HtaccessRules::MARKER, $removed);
        self::assertStringContainsString('# ~~start~~', $removed);
        self::assertStringContainsString('after', $removed);
    }
}
