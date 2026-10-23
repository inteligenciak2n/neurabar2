<?php

return [
    /*
     * The URL path prefix for the Platform backoffice.
     * Set PLATFORM_PATH in .env to customize.
     */
    'path' => env('PLATFORM_PATH', 'backoffice'),

    /*
     * The URL of the static landing page served from the root domain.
     * Used to restrict CORS on public API endpoints.
     * Set LANDING_PAGE_URL in .env to match your production domain.
     */
    'landing_page_url' => env('LANDING_PAGE_URL', 'http://localhost:8080'),

    /*
     * Hours a delivery order's 6-digit code stays reserved before the scheduled
     * job releases it for reuse.
     */
    'delivery_code_ttl_hours' => (int) env('DELIVERY_CODE_TTL_HOURS', 24),
];
