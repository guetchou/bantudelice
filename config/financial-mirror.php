<?php

return [
    'collections_enabled' => filter_var(
        env('FINANCIAL_MIRROR_COLLECTIONS_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'max_error_length' => (int) env('FINANCIAL_MIRROR_MAX_ERROR_LENGTH', 2000),
];
