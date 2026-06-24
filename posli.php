<?php
/**
 * Webnite — kontaktni obrazec / contact form handler
 * Pošlje povpraševanje naravnost v info@webnite.si.
 *
 * Privzeto pošlje prek PHP mail() (deluje takoj po nalaganju).
 * Za TAKOJŠNJO dostavo (brez ~30 min zamude zaradi greylistinga) lahko
 * vklopiš pošiljanje prek SMTP — glej nastavitve spodaj.
 */

// ============ NASTAVITVE ============
$TO = 'info@webnite.si';

// --- SMTP (po želji; za hitrejšo dostavo) ---
// 1) V cPanel > Email Accounts poznaj/ponastavi geslo za info@webnite.si
// 2) Nastavi $SMTP_ENABLED = true in vpiši $SMTP_PASS
$SMTP_ENABLED = false;
$SMTP_HOST = 'localhost';       // ali npr. 'mail.webnite.si'
$SMTP_PORT = 465;               // 465 = SSL
$SMTP_USER = 'info@webnite.si';
$SMTP_PASS = '';                // <-- vpiši geslo za info@webnite.si
// ====================================

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

// honeypot proti robotom — blokiraj LE, ce skrito polje vsebuje znacilnosti
// spama (povezave/HTML). Tako samodejno izpolnjevanje brskalnika (autofill),
// ki vanj vpise npr. ime obiskovalca, NE blokira pravega povprasevanja.
$hp = $_POST['_gotcha'] ?? '';
if ($hp !== '' && preg_match('~https?://|www\.|\[url|</?[a-z]~i', $hp)) {
    echo json_encode(['ok' => true]);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$vrsta   = trim($_POST['vrsta']   ?? '');
$budget  = trim($_POST['budget']  ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

// zaščita pred header injection
$strip = function ($s) { return str_replace(["\r", "\n", "%0a", "%0d"], ' ', $s); };
$cut   = function ($s, $n) { return function_exists('mb_substr') ? mb_substr($s, 0, $n) : substr($s, 0, $n); };
$name  = $cut($strip($name),  120);
$email = $strip($email);
$phone = $cut($strip($phone), 60);
$vrsta = $cut($strip($vrsta), 120);
$budget = $cut($strip($budget), 60);
$message = $cut($message, 5000);

$subject = 'Novo povpraševanje s spletne strani';
$body  = "Novo povpraševanje prek webnite.si\n";
$body .= "-----------------------------------\n";
$body .= "Ime:     $name\n";
$body .= "Email:   $email\n";
if ($phone !== '') $body .= "Telefon: $phone\n";
if ($vrsta !== '') $body .= "Storitev: $vrsta\n";
if ($budget !== '') $body .= "Proračun: $budget\n";
$body .= "-----------------------------------\n\n";
$body .= "Sporočilo:\n$message\n";

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

/**
 * Pošlji prek SMTP (implicit SSL). Vrne true ob uspehu, sicer vrže Exception.
 */
function send_via_smtp($host, $port, $user, $pass, $to, $subject, $body, $replyName, $replyEmail) {
    $errno = 0; $errstr = '';
    $fp = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 15);
    if (!$fp) throw new Exception('connect failed: ' . $errstr);
    stream_set_timeout($fp, 15);

    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function ($c, $expect) use ($fp, $read) {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        if ($expect !== null && strpos($r, $expect) !== 0) {
            throw new Exception('SMTP: expected ' . $expect . ' got ' . substr($r, 0, 3));
        }
        return $r;
    };

    $read();                                   // pozdrav strežnika
    $cmd('EHLO webnite.si', '250');
    $cmd('AUTH LOGIN', '334');
    $cmd(base64_encode($user), '334');
    $cmd(base64_encode($pass), '235');         // 235 = avtentikacija OK
    $cmd('MAIL FROM:<' . $user . '>', '250');
    $cmd('RCPT TO:<' . $to . '>', '250');
    $cmd('DATA', '354');

    // dot-stuffing + CRLF
    $bodyCRLF = preg_replace('/\r\n|\r|\n/', "\r\n", $body);
    $bodyCRLF = preg_replace('/^\./m', '..', $bodyCRLF);

    $data  = 'From: Webnite obrazec <' . $user . ">\r\n";
    $data .= 'To: <' . $to . ">\r\n";
    $data .= 'Reply-To: ' . $replyName . ' <' . $replyEmail . ">\r\n";
    $data .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $data .= "MIME-Version: 1.0\r\n";
    $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $data .= "\r\n" . $bodyCRLF . "\r\n.";
    $cmd($data, '250');
    $cmd('QUIT', null);
    fclose($fp);
    return true;
}

$sent = false;

if ($SMTP_ENABLED && $SMTP_PASS !== '') {
    try {
        $sent = send_via_smtp($SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS, $TO, $subject, $body, $name, $email);
    } catch (Exception $e) {
        $sent = false; // pade nazaj na mail()
    }
}

// rezerva (ali privzeto): PHP mail()
if (!$sent) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "From: Webnite obrazec <$TO>\r\n";
    $headers .= "Reply-To: $name <$email>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $sent = @mail($TO, $encodedSubject, $body, $headers);
}

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'send']);
}
