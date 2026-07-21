<?php

$to_email = "abidj3477@gmail.com";

// SMTP credentials
$smtp_host       = "smtp.hostinger.com";
$smtp_port       = 465;
$smtp_username   = "no-reply@expatcarbuyers.com";
$smtp_password   = "7#XY4zOm7Bl";
$smtp_encryption = "ssl";
$from_email      = "no-reply@expatcarbuyers.com";
$from_name       = "Abid Sabir Portfolio";

if (!defined("PHP_EOL")) define("PHP_EOL", "\r\n");

$error = false;
$fields = array('mail', 'phone', 'message');

foreach ($fields as $field) {
    if (empty($_POST[$field]) || trim($_POST[$field]) == '')
        $error = true;
}

if (!$error) {
    $mail    = htmlspecialchars(stripslashes($_POST['mail']));
    $phone   = htmlspecialchars(stripslashes($_POST['phone']));
    $message = htmlspecialchars(stripslashes($_POST['message']));

    $subject = "New Contact from Portfolio — " . $mail;
    $body    = "You have a new message from your portfolio contact form.\r\n\r\n"
             . "From:    $mail\r\n"
             . "Phone:   $phone\r\n\r\n"
             . "Message:\r\n$message\r\n";

    // Open SMTP socket
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ]);

    $socket = stream_socket_client(
        "ssl://{$smtp_host}:{$smtp_port}",
        $errno, $errstr, 30,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        echo "ERROR: Cannot connect to SMTP ($errstr)";
        exit;
    }

    function smtp_cmd($socket, $cmd, $expect) {
        if ($cmd) fwrite($socket, $cmd . "\r\n");
        $res = '';
        while ($line = fgets($socket, 515)) {
            $res .= $line;
            if ($line[3] == ' ') break;
        }
        return (substr(trim($res), 0, 3) == $expect) ? true : false;
    }

    fgets($socket, 515); // greeting

    $ok = smtp_cmd($socket, "EHLO portfolio", "250")
       && smtp_cmd($socket, "AUTH LOGIN", "334")
       && smtp_cmd($socket, base64_encode($smtp_username), "334")
       && smtp_cmd($socket, base64_encode($smtp_password), "235")
       && smtp_cmd($socket, "MAIL FROM:<{$from_email}>", "250")
       && smtp_cmd($socket, "RCPT TO:<{$to_email}>", "250")
       && smtp_cmd($socket, "DATA", "354");

    if ($ok) {
        $headers  = "From: {$from_name} <{$from_email}>\r\n";
        $headers .= "Reply-To: {$mail}\r\n";
        $headers .= "To: {$to_email}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $res = '';
        while ($line = fgets($socket, 515)) {
            $res .= $line;
            if ($line[3] == ' ') break;
        }
        $sent = (substr(trim($res), 0, 3) == "250");
        smtp_cmd($socket, "QUIT", "221");
        fclose($socket);

        echo $sent ? 'Success' : 'ERROR: Message not accepted';
    } else {
        fclose($socket);
        echo 'ERROR: SMTP authentication failed';
    }
}
?>
