<?php

return [

    /*
    | The user whose primary HTML resume is served, unauthenticated, at
    | GET /api/public/resume, and the origins (comma-separated) of the
    | external sites allowed to iframe or fetch it.
    */
    'public_user_id' => env('PUBLIC_RESUME_USER_ID'),

    'embed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('PUBLIC_RESUME_EMBED_ORIGINS', '')),
    ))),

];
