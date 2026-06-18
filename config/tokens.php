<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validity period (self-paced expiry window)
    |--------------------------------------------------------------------------
    |
    | Number of days a token paket stays valid, counted from the date of the
    | FIRST consumed session (not the purchase date) so students who delay
    | starting do not lose their window. Applies to the expiry-based rules.
    |
    */

    'validity_period_days' => (int) env('TOKEN_VALIDITY_PERIOD_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Notice window (booked self-paced sessions)
    |--------------------------------------------------------------------------
    |
    | Hours of notice required to reschedule a booked self-paced session
    | without forfeiting the token. Reserved for the (deferred) booking
    | feature; not yet enforced by the current consume/forfeit flow.
    |
    */

    'notice_window_hours' => (int) env('TOKEN_NOTICE_WINDOW_HOURS', 24),

];
