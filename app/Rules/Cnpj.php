<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidCnpj($value)) {
            $fail("The {$attribute} is not a valid CNPJ.");
        }
    }

private function isValidCnpj(string $cnpj): bool
{
    $cnpj = preg_replace('/\D/', '', $cnpj);

    if (strlen($cnpj) != 14) {
        return false;
    }

    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }

    $weights = [
        [5,4,3,2,9,8,7,6,5,4,3,2],
        [6,5,4,3,2,9,8,7,6,5,4,3,2]
    ];

    for ($t = 0; $t < 2; $t++) {
        $sum = 0;
        for ($i = 0; $i < count($weights[$t]); $i++) {
            $sum += intval($cnpj[$i]) * $weights[$t][$i];
        }

        $remainder = $sum % 11;
        $digit = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($cnpj[12 + $t]) !== $digit) {
            return false;
        }
    }

    return true;
}

}
