<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CAINFO, "C:/Program Files/PHP/extras/ssl/cacert.pem"); // Corrected path
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    echo 'Success: ' . substr($response, 0, 200); // Show first 200 characters of response
}
curl_close($ch);
