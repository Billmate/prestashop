<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$to = "yuksel@combain.com";
$to = 'vipan.eminence@gmail.com';
$subject = "Test mail";
$message = "Hello! This is a simple email message.";
$from = "important@combain.com";
$headers = "From:" . $from;
var_dump(mail($to,$subject,$message,$headers));
echo "Mail Sent.";
?>
