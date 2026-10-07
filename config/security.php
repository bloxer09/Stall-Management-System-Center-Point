<?php

return [
    // Content-Security-Policy: "enforce" (block), "report" (log only) or "off".
    'csp_mode' => env('CSP_MODE', 'enforce'),
];
