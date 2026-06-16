<?php
// URL Faayilii Webhook kee isa sirrii kanaan gadiitti bakka buasi
$webhook_url = "http://localhost/Agri-business social entriprice/webhook_receiver.php";

// Deetaa Fake (Fake Data) kan qonnaan bulaa fi ogeessa walqunnamsiisu uumuu
$fake_data = [
    "event" => "order_created",
    "timestamp" => time(),
    "farmer" => [
        "id" => 1024,
        "name" => "Chala Abera",
        "phone" => "+251911000000"
    ],
    "expert_assigned" => [
        "id" => 55,
        "name" => "Dr. Tolassa"
    ],
    "order_details" => [
        "order_id" => "AGRI-9982",
        "product" => "Improved Maize Seed",
        "status" => "Active"
    ]
];

// Deetaa kana gara JSON tti jijjiiri
$json_payload = json_encode($fake_data);

// Gara Webhook keetti Curl fayyadamnee POST gochuu
$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_payload)
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Bu'aa isaa screen irratti argisiisi
echo "<h3>Webhook Fake Ergameera!</h3>";
echo "HTTP Status Code: " . $http_code . "<br>";
echo "Deebii Sarvarii (Response): " . htmlspecialchars($response);
?>