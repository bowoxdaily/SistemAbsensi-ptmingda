<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://demohris.binanexa.com/api/payslip/download?employee_id=MIF-0002&month=2026-03');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: e2662afc79c9cb6129da5c0b7df7d581256f21d819d517f7',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$header\n";
if (substr(trim($body), 0, 1) === '{') {
    echo "Body is JSON:\n" . substr($body, 0, 300) . "...\n";
} else {
    echo "Body starts with: " . substr($body, 0, 50) . "\n";
}
