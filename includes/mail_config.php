<?php
// Email (SMTP) settings for NEUMed.
// KEEP THIS FILE PRIVATE: never share it, paste it in chats, or upload it to GitHub.
// Fill in your NEW Gmail app password below (create one at Google Account > Security > 2-Step Verification > App passwords).

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 465,
    'username'   => '',   // your Gmail address, e.g. yourname@gmail.com
    'password'   => '',   // the NEW 16-character app password (spaces are fine)
    'from_email' => '',   // leave empty to use the username
    'from_name'  => 'NEUMed Support',

    // The address people use to open your site. Used inside email links.
    // Local testing:  'http://localhost/meditrack'
    // Hosted site:    'https://jstnagls.shop'
    'base_url'   => 'http://localhost/meditrack',
];