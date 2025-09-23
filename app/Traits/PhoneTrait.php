<?php

namespace App\Traits;

use App\Utils\PhoneUtil;

trait PhoneTrait
{
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = $value ? PhoneUtil::digits($value) : null;
    }

    public function getPhoneAttribute($value)
    {
        return $value ? PhoneUtil::format($value) : null;
    }

    public static function isValidPhone(string $phone): bool
    {
        return PhoneUtil::isValid($phone);
    }
}
