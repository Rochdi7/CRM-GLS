<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Phone\Countries;
use PHPUnit\Framework\TestCase;

final class CountriesTest extends TestCase
{
    public function test_join_combines_dial_code_and_national_number(): void
    {
        $this->assertSame('+212661954125', Countries::join('MA', '661954125'));
        $this->assertSame('+385661954125', Countries::join('HR', '66 19-54.125'));
        $this->assertNull(Countries::join('MA', ''));
        $this->assertNull(Countries::join('MA', null));
        // Already-qualified values are kept as-is.
        $this->assertSame('+33612345678', Countries::join('MA', '+33612345678'));
        // Unknown country falls back to the default dial code.
        $this->assertSame('+212661954125', Countries::join('ZZ', '661954125'));
    }

    public function test_split_resolves_the_longest_dial_code(): void
    {
        $this->assertSame(['MA', '661954125'], Countries::split('+212661954125'));
        $this->assertSame(['HR', '661954125'], Countries::split('+385661954125'));
        // +1246 (Barbade) must win over +1 (États-Unis).
        $this->assertSame(['BB', '2501234'], Countries::split('+12462501234'));
        $this->assertSame(['US', '5551234567'], Countries::split('+15551234567'));
        // Legacy local values fall back to the default country untouched.
        $this->assertSame(['MA', '0661954125'], Countries::split('0661954125'));
        $this->assertSame(['MA', ''], Countries::split(null));
    }

    public function test_all_is_sorted_by_french_name_and_dial_lookup_works(): void
    {
        $all = Countries::all();
        $this->assertArrayHasKey('MA', $all);
        $this->assertSame('Maroc', $all['MA']['nom']);
        $this->assertSame('+212', Countries::dial('MA'));
        $this->assertSame('+212', Countries::dial(null));
        $this->assertSame('+212', Countries::dial('XX'));

        $names = array_map(fn (array $c) => iconv('UTF-8', 'ASCII//TRANSLIT', $c['nom']), array_values($all));
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
    }
}
