<?php

namespace Tests\Unit;

use App\Domain\Payment\MtnErrorCatalog;
use Tests\TestCase;

class MtnErrorCatalogTest extends TestCase
{
    /** @test */
    public function lookup_returns_entry_for_known_code()
    {
        $entry = MtnErrorCatalog::lookup('NOT_ENOUGH_FUNDS');

        $this->assertNotNull($entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertArrayHasKey('action', $entry);
        $this->assertSame('Le solde MTN MoMo du payeur est insuffisant.', $entry['message']);
    }

    /** @test */
    public function lookup_returns_null_for_unknown_code()
    {
        $this->assertNull(MtnErrorCatalog::lookup('NONEXISTENT_CODE'));
        $this->assertNull(MtnErrorCatalog::lookup(''));
    }

    /** @test */
    public function has_returns_true_for_known_codes()
    {
        $this->assertTrue(MtnErrorCatalog::has('PAYER_NOT_FOUND'));
        $this->assertTrue(MtnErrorCatalog::has('COULD_NOT_PERFORM_TRANSACTION'));
        $this->assertTrue(MtnErrorCatalog::has('RESOURCE_ALREADY_EXIST'));
        $this->assertTrue(MtnErrorCatalog::has('RESOURCE_ALREADY_EXISTS'));
    }

    /** @test */
    public function has_returns_false_for_unknown_code()
    {
        $this->assertFalse(MtnErrorCatalog::has('UNKNOWN_CODE'));
        $this->assertFalse(MtnErrorCatalog::has(''));
    }

    /** @test */
    public function all_returns_complete_catalog()
    {
        $catalog = MtnErrorCatalog::all();

        $this->assertIsArray($catalog);
        $this->assertCount(14, $catalog);

        foreach ($catalog as $code => $entry) {
            $this->assertIsString($code);
            $this->assertNotEmpty($code);
            $this->assertArrayHasKey('message', $entry);
            $this->assertArrayHasKey('action', $entry);
            $this->assertNotEmpty($entry['message']);
            $this->assertNotEmpty($entry['action']);
        }
    }

    /** @test */
    public function lookup_and_all_are_consistent()
    {
        foreach (MtnErrorCatalog::all() as $code => $entry) {
            $this->assertSame($entry, MtnErrorCatalog::lookup($code));
            $this->assertTrue(MtnErrorCatalog::has($code));
        }
    }

    /** @test */
    public function critical_codes_have_actionable_messages()
    {
        $critical = [
            'NOT_ENOUGH_FUNDS',
            'PAYER_NOT_FOUND',
            'COULD_NOT_PERFORM_TRANSACTION',
            'PAYER_LIMIT_REACHED',
        ];

        foreach ($critical as $code) {
            $entry = MtnErrorCatalog::lookup($code);
            $this->assertNotNull($entry, "Code critique absent du catalogue : {$code}");
            $this->assertNotEmpty($entry['action'], "Action manquante pour : {$code}");
        }
    }
}
