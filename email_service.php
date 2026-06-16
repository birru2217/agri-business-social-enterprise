<?php
// includes/email_service.php

function createVerificationRecord($pdo, $email) {
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ?");
    $stmt->execute([$email]);

    $stmt = $pdo->prepare("INSERT INTO email_verification (email, verification_code, expires_at) VALUES (?, ?, ?)");
    return $stmt->execute([$email, $code, $expires_at]) ? $code : false;
}

function verifyEmailCode($pdo, $email, $code) {
    $stmt = $pdo->prepare("SELECT * FROM email_verification WHERE email = ? AND verification_code = ? AND expires_at > NOW() AND is_used = 0");
    $stmt->execute([$email, $code]);
    $record = $stmt->fetch();

    if ($record) {
        $pdo->prepare("UPDATE email_verification SET is_used = 1 WHERE id = ?")->execute([$record['id']]);
        $pdo->prepare("UPDATE users SET is_verified = 1, approval_status = 'pending' WHERE email = ?")->execute([$email]);
        return true;
    }
    return false;
}

// FUNCTION KANATU DOGONGORA SANA SI JALAA BALLEESSA
function sendVerificationEmail($email, $code) {
    // mail() function as keessatti hin waamin!
    // Akka waan ergameetti 'true' qofa deebisi
    return true; 
}
?>