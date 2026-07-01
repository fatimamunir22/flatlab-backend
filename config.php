<?php
define('BREVO_API_KEY', getenv('BREVO_API_KEY'));
define('SENDER_NAME',   getenv('SENDER_NAME')   ?: 'FlatLab');
define('SENDER_EMAIL',  getenv('SENDER_EMAIL')  ?: 'contact@flatlab.io');
define('SITE_URL',      getenv('SITE_URL')      ?: 'https://flatlabs.infinityfreeapp.com');
