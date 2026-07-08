<?php
define('BREVO_API_KEY', getenv('BREVO_API_KEY'));
define('SENDER_NAME',   getenv('SENDER_NAME')   ?: 'FlatLab');
define('SENDER_EMAIL',  getenv('SENDER_EMAIL')  ?: 'admin@flatlab.io');
define('SITE_URL',      getenv('SITE_URL')      ?: 'https://flatlabs.infinityfreeapp.com');
define('BACKEND_URL',   getenv('BACKEND_URL')   ?: 'https://flatlab-backend-production.up.railway.app');
define('UNSUB_SECRET',  getenv('UNSUB_SECRET')  ?: '');
define('WEBHOOK_SECRET', getenv('WEBHOOK_SECRET') ?: '');
