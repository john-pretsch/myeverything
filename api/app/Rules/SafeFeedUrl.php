<?php

namespace App\Rules;

use App\Support\HostSafety;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SafeFeedUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! HostSafety::isSafeFeedUrl($value)) {
            $fail('The :attribute must be a public http(s) URL.');
        }
    }
}
