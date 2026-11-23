<?php

namespace App\Support;

/** Livewire 2 adaptation: rotate the signed server memo on every response. */
class SubmissionNonce
{
    public static function hydrate($component, $request) {}

    public static function dehydrate($component, $response)
    {
        $response->memo['submissionNonce'] = bin2hex(random_bytes(16));
    }
}
