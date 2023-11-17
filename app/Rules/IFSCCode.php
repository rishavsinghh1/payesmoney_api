<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IFSCCode implements Rule
{
    public function passes($attribute, $value)
    {
        // Define a regular expression pattern to validate IFSC code.
        $pattern = '/^[A-Z]{4}0[A-Z0–9]{6}$/';

        // Check if the value matches the pattern.
        return preg_match($pattern, $value) === 1;
    }

    public function message()
    {
        return 'The :attribute is not a valid IFSC code.';
    }
}
