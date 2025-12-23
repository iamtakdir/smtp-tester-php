<?php
/*
 * OMEGA SOLUTION SMTP TESTER (PHP Native Socket Version)
 * No libraries required. Works on standard XAMPP/cPanel.
 */

// --- BACKEND LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Disable script execution time limit for slow SMTP servers
    set_time_limit(0); 

    $input = json_decode(file_get_contents('php://input'), true);
    $host = $input['host'];
    $port = (int)$input['port'];
    $user = $input['user'];
    $pass = $input['pass'];
    $from = $input['from'];
    $to   = $input['to'];
    $secure = $input['secure']; // "ssl", "tls", or "none"

    $logs = [];

    function addLog($msg, $type = 'info') {
        global $logs;
        $logs[] = ['time' => date('H:i:s'), 'msg' => $msg, 'type' => $type];
    }

    function serverResponse($socket, $expected_code) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        addLog("SERVER: " . trim($response), 'server');
        
        if (substr($response, 0, 3) != $expected_code) {
            return false;
        }
        return true;
    }

    function sendCommand($socket, $cmd) {
        addLog("CLIENT: " . $cmd, 'client');
        fputs($socket, $cmd . "\r\n");
    }

    try {
        // 1. CONNECT
        $protocol = ($secure === 'ssl') ? 'ssl://' : '';
        addLog("Connecting to {$protocol}{$host}:{$port}...", 'info');
        
        $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, 10);
        
        if (!$socket) {
            throw new Exception("Could not connect: $errstr ($errno)");
        }
        
        if (!serverResponse($socket, '220')) throw new Exception("Connection refused by server greeting.");

        // 2. EHLO
        sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
        if (!serverResponse($socket, '250')) throw new Exception("EHLO failed.");

        // 3. STARTTLS (If Port 587/TLS selected)
        if ($secure === 'tls') {
            sendCommand($socket, "STARTTLS");
            if (!serverResponse($socket, '220')) throw new Exception("STARTTLS failed.");
            
            addLog("Initiating Crypto Stream...", 'info');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("TLS Encryption failed.");
            }
            
            // Send EHLO again after TLS handshake
            sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            if (!serverResponse($socket, '250')) throw new Exception("EHLO (after TLS) failed.");
        }

        // 4. AUTH
        if (!empty($user) && !empty($pass)) {
            sendCommand($socket, "AUTH LOGIN");
            if (!serverResponse($socket, '334')) throw new Exception("AUTH LOGIN rejected.");

            sendCommand($socket, base64_encode($user));
            if (!serverResponse($socket, '334')) throw new Exception("Username rejected.");

            sendCommand($socket, base64_encode($pass));
            if (!serverResponse($socket, '235')) throw new Exception("Password rejected. Check credentials or 'App Password' requirements.");
        }

        // 5. MAIL FROM
        sendCommand($socket, "MAIL FROM: <$from>");
        if (!serverResponse($socket, '250')) throw new Exception("MAIL FROM rejected.");

        // 6. RCPT TO
        sendCommand($socket, "RCPT TO: <$to>");
        if (!serverResponse($socket, '250')) throw new Exception("RCPT TO rejected.");

        // 7. DATA
        sendCommand($socket, "DATA");
        if (!serverResponse($socket, '354')) throw new Exception("DATA command rejected.");

        $subject = "SMTP Test from Omega Tool";
        $message = "This is a test email sent via raw PHP socket.\r\nHost: $host\r\nTime: " . date('Y-m-d H:i:s');
        
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "From: Omega Tester <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $email_content = $headers . "\r\n" . "<h3>✅ SMTP Config Working</h3><p>Your server settings are correct.</p>";

        sendCommand($socket, $email_content . "\r\n.");
        if (!serverResponse($socket, '250')) throw new Exception("Message body rejected.");

        // 8. QUIT
        sendCommand($socket, "QUIT");
        fclose($socket);

        echo json_encode(['success' => true, 'logs' => $logs]);

    } catch (Exception $e) {
        addLog("❌ ERROR: " . $e->getMessage(), 'error');
        echo json_encode(['success' => false, 'logs' => $logs]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP SMTP Tester</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --text: #e2e8f0; --accent: #6366f1; --border: #334155; --green: #10b981; --red: #ef4444; }
        body { font-family: monospace; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; }
        .sidebar { width: 350px; background: var(--card); padding: 20px; border-right: 1px solid var(--border); overflow-y: auto; display: flex; flex-direction: column; gap: 15px; }
        .main { flex: 1; padding: 20px; display: flex; flex-direction: column; }
        h2 { margin: 0 0 10px 0; color: #fff; font-size: 18px; }
        label { font-size: 12px; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 5px; }
        input, select { width: 100%; background: #0f172a; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 4px; box-sizing: border-box; }
        button { background: var(--accent); color: white; border: none; padding: 12px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 4px; }
        button:disabled { opacity: 0.5; }
        .logs { flex: 1; background: #000; border: 1px solid var(--border); padding: 15px; overflow-y: auto; font-size: 13px; }
        .log-row { margin-bottom: 4px; border-bottom: 1px solid #222; padding-bottom: 2px; }
        .c-client { color: #facc15; } /* Yellow for what WE sent */
        .c-server { color: #38bdf8; } /* Blue for what SERVER sent */
        .c-error { color: var(--red); font-weight: bold; }
        .c-success { color: var(--green); font-weight: bold; }
        .c-info { color: #9ca3af; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>SMTP Config</h2>
    <div><label>Host</label><input type="text" id="host" placeholder="smtp.gmail.com"></div>
    <div style="display:flex; gap:10px;">
        <div style="flex:1"><label>Port</label><input type="number" id="port" value="587"></div>
        <div style="flex:1"><label>Security</label>
            <select id="secure">
                <option value="tls">TLS (587)</option>
                <option value="ssl">SSL (465)</option>
                <option value="none">None (25)</option>
            </select>
        </div>
    </div>
    <hr style="border:0; border-top:1px solid var(--border); width:100%">
    <div><label>Username</label><input type="text" id="user" placeholder="email@example.com"></div>
    <div><label>Password</label><input type="password" id="pass" placeholder="App Password / Password"></div>
    <hr style="border:0; border-top:1px solid var(--border); width:100%">
    <div><label>From Email</label><input type="email" id="from" placeholder="sender@example.com"></div>
    <div><label>To Email</label><input type="email" id="to" placeholder="recipient@example.com"></div>
    <button onclick="testSMTP()" id="btn">Send Test Email</button>
</div>

<div class="main">
    <h2>Debug Console (Raw Transaction)</h2>
    <div class="logs" id="logBox">
        <div class="log-row c-info">Waiting for test...</div>
    </div>
</div>

<script>
    async function testSMTP() {
        const btn = document.getElementById('btn');
        const logs = document.getElementById('logBox');
        
        btn.disabled = true;
        btn.innerText = "Connecting...";
        logs.innerHTML = '<div class="log-row c-info">Initializing request...</div>';

        const data = {
            host: document.getElementById('host').value,
            port: document.getElementById('port').value,
            secure: document.getElementById('secure').value,
            user: document.getElementById('user').value,
            pass: document.getElementById('pass').value,
            from: document.getElementById('from').value,
            to: document.getElementById('to').value
        };

        try {
            const req = await fetch('', { // Post to self
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const res = await req.json();
            
            logs.innerHTML = ''; // Clear initial message
            
            res.logs.forEach(l => {
                let colorClass = 'c-info';
                if (l.type === 'client') colorClass = 'c-client';
                if (l.type === 'server') colorClass = 'c-server';
                if (l.type === 'error') colorClass = 'c-error';
                
                const row = document.createElement('div');
                row.className = `log-row ${colorClass}`;
                row.innerText = `[${l.time}] ${l.msg}`;
                logs.appendChild(row);
            });

            if(res.success) {
                const successMsg = document.createElement('div');
                successMsg.className = 'log-row c-success';
                successMsg.innerText = "✅ EMAIL SENT SUCCESSFULLY!";
                logs.appendChild(successMsg);
            }

        } catch (e) {
            logs.innerHTML += `<div class="log-row c-error">Script Error: ${e.message}</div>`;
        }
        
        btn.disabled = false;
        btn.innerText = "Send Test Email";
    }
</script>
</body>
</html>