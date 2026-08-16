<?php

function checkDomain($domain) {
    echo "Checking $domain...\n";
    $ch = curl_init("https://$domain/api/v1/auth/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    if(curl_errno($ch)){
        echo "Curl error: " . curl_error($ch) . "\n";
    } else {
        echo "Status: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
    }
    curl_close($ch);
}

checkDomain('ecc-test.empoweredtechinnovations.org');
