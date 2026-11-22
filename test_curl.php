<?php
$ch = curl_init('http://127.0.0.1:8000/contact');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$resp = curl_exec($ch);
$body = substr($resp, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
echo 'Body length: ' . strlen($body) . PHP_EOL;
if (strpos($body, '<!DOCTYPE') !== false) {
    echo 'DOFOUND at position: ' . strpos($body, '<!DOCTYPE') . PHP_EOL;
} else {
    echo 'DOCT NOT FOUND' . PHP_EOL;
}
if (strpos($body, '<html') !== false) {
    echo 'HTML found at position: ' . strpos($body, '<html') . PHP_EOL;
} else {
    echo 'HTML NOT FOUND' . PHP_EOL;
}
if (strpos($body, '<head') !== false) {
    echo 'HEAD found at position: ' . strpos($body, '<head') . PHP_EOL;
} else {
    echo 'HEAD NOT FOUND' . PHP_EOL;
}
if (strpos($body, '<body') !== false) {
    echo 'BODY found at position: ' . strpos($body, '<body') . PHP_EOL;
} else {
    echo 'BODY NOT FOUND' . PHP_EOL;
}
if (strpos($body, '<nav') !== false) {
    echo 'NAV found at position: ' . strpos($body, '<nav') . PHP_EOL;
} else {
    echo 'NAV NOT FOUND' . PHP_EOL;
}
if (strpos($body, '<main') !== false) {
    echo 'MAIN found at position: ' . strpos($body, '<main') . PHP_EOL;
} else {
    echo 'MAIN NOT FOUND' . PHP_EOL;
}