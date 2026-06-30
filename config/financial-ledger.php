<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registre financier BantuDelice
    |--------------------------------------------------------------------------
    |
    | write_enabled active les doubles écritures sur les nouveaux événements.
    | read_partner_balances bascule les dashboards vers les soldes du registre.
    | La lecture ne doit être activée qu'après reprise et rapprochement des
    | positions historiques.
    |
    */
    'write_enabled' => filter_var(env('FINANCIAL_LEDGER_WRITE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'read_partner_balances' => filter_var(env('FINANCIAL_LEDGER_READ_PARTNER_BALANCES', false), FILTER_VALIDATE_BOOLEAN),
];
