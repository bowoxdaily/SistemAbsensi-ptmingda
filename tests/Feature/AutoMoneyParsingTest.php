<?php

namespace Tests\Feature;

use Tests\TestCase;

class AutoMoneyParsingTest extends TestCase
{
    public function test_currency_input_normalizes_indonesian_and_decimal_values(): void
    {
        $parse = static function (string $value): float {
            $normalized = preg_replace('/[^\d,.-]/', '', $value);
            if (str_contains($normalized, ',')) {
                return (float) (str_replace(',', '.', str_replace('.', '', $normalized)) ?: 0);
            }
            if (substr_count($normalized, '.') > 1 || preg_match('/\.\d{3}$/', $normalized)) {
                return (float) (str_replace('.', '', $normalized) ?: 0);
            }
            return (float) ($normalized ?: 0);
        };

        $this->assertSame(1234.5, $parse('Rp 1.234,50'));
        $this->assertSame(1234.5, $parse('1234.5'));
        $this->assertSame(1234567.0, $parse('Rp 1.234.567'));
    }
}