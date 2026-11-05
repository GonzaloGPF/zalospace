<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Routes exposed to the frontend
    |--------------------------------------------------------------------------
    |
    | Ziggy prints these routes in the HTML of every page, so it only gets the
    | ones the frontend links to, not the ones registered by packages.
    |
    */

    'only' => ['welcome', 'info.*', 'contacts.*', 'larecipe.docs.page'],
];
