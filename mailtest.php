<?php
$to      = 'fatima.munir@algo.com';
$subject = 'FlatLab Mail Test';
$message = 'This is a test email from InfinityFree.';
$headers = "From: FlatLab <contact@flatlab.io>\r\nContent-Type: text/plain; charset=UTF-8";

$result = mail($to, $subject, $message, $headers);
echo $result ? 'mail() returned TRUE — sent (or queued)' : 'mail() returned FALSE — sending blocked on this server';
