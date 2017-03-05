<?php

return [

    /*
    secret that is used to sign
     */
    'secret' => env('SIGNER_SECRET', 'my-secret'),

    /*
    salt; set null to apply default salt
     */
    'salt' => null,

    /*
    separator
     */
    'separator' => ':',

    /*
    max age default value; you can redefine this at any time using ->setMaxAge()
    */
    'default_max_age' => 60*60

];
