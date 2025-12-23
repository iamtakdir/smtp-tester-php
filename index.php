<?php
/*
 * OMEGA SOLUTION SMTP TESTER (Responsive Version)
 * Run on XAMPP/WAMP/LAMP
 */

// --- BACKEND LOGIC (Unchanged) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    set_time_limit(0); 

    $input = json_decode(file_get_contents('php://input'), true);
    $host = $input['host'];
    $port = (int)$input['port'];
    $user = $input['user'];
    $pass = $input['pass'];
    $from = $input['from'];
    $to   = $input['to'];
    $secure = $input['secure']; 

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
        if (substr($response, 0, 3) != $expected_code) return false;
        return true;
    }

    function sendCommand($socket, $cmd) {
        addLog("CLIENT: " . $cmd, 'client');
        fputs($socket, $cmd . "\r\n");
    }

    try {
        $protocol = ($secure === 'ssl') ? 'ssl://' : '';
        addLog("Connecting to {$protocol}{$host}:{$port}...", 'info');
        
        $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, 10);
        if (!$socket) throw new Exception("Could not connect: $errstr ($errno)");
        if (!serverResponse($socket, '220')) throw new Exception("Connection refused.");

        sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
        if (!serverResponse($socket, '250')) throw new Exception("EHLO failed.");

        if ($secure === 'tls') {
            sendCommand($socket, "STARTTLS");
            if (!serverResponse($socket, '220')) throw new Exception("STARTTLS failed.");
            addLog("Initiating Crypto Stream...", 'info');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new Exception("TLS Encryption failed.");
            sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            if (!serverResponse($socket, '250')) throw new Exception("EHLO (after TLS) failed.");
        }

        if (!empty($user) && !empty($pass)) {
            sendCommand($socket, "AUTH LOGIN");
            if (!serverResponse($socket, '334')) throw new Exception("AUTH LOGIN rejected.");
            sendCommand($socket, base64_encode($user));
            if (!serverResponse($socket, '334')) throw new Exception("Username rejected.");
            sendCommand($socket, base64_encode($pass));
            if (!serverResponse($socket, '235')) throw new Exception("Password rejected.");
        }

        sendCommand($socket, "MAIL FROM: <$from>");
        if (!serverResponse($socket, '250')) throw new Exception("MAIL FROM rejected.");
        sendCommand($socket, "RCPT TO: <$to>");
        if (!serverResponse($socket, '250')) throw new Exception("RCPT TO rejected.");
        sendCommand($socket, "DATA");
        if (!serverResponse($socket, '354')) throw new Exception("DATA command rejected.");

        $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Omega Tester <$from>\r\nTo: $to\r\nSubject: SMTP Test Success\r\n";
        $body = "<h3>✅ SMTP Config Working</h3><p>Your server settings are correct.</p>";
        
        sendCommand($socket, $headers . "\r\n" . $body . "\r\n.");
        if (!serverResponse($socket, '250')) throw new Exception("Message body rejected.");

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Omega SMTP Tester</title>
    <style>
        :root { 
            --bg: #0f172a; 
            --card: #1e293b; 
            --text: #e2e8f0; 
            --text-muted: #94a3b8;
            --accent: #6366f1; 
            --border: #334155; 
            --green: #10b981; 
            --red: #ef4444; 
            --yellow: #facc15;
            --blue: #38bdf8;
        }

        * { box-sizing: border-box; }

        body { 
            font-family: 'Segoe UI', monospace, sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            margin: 0; 
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Responsive Layout Container */
        .container {
            display: flex;
            flex-direction: column; /* Mobile Default: Stacked */
            height: 100%;
        }

        /* Sidebar (Form) Styles */
        .sidebar { 
            background: var(--card); 
            padding: 1.5rem; 
            border-bottom: 1px solid var(--border);
            overflow-y: auto; 
            flex-shrink: 0;
        }

        /* Main (Logs) Styles */
        .main { 
            flex: 1; 
            padding: 1.5rem; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; /* Contains the scrollable log box */
            min-height: 300px;
        }

        /* Desktop specific layout */
        @media (min-width: 768px) {
            .container {
                flex-direction: row; /* Desktop: Side by Side */
                overflow: hidden;
            }
            .sidebar {
                width: 380px;
                border-bottom: none;
                border-right: 1px solid var(--border);
                height: 100%;
            }
            .main {
                height: 100%;
            }
        }

        /* Typography & Elements */
        h2 { margin: 0 0 1rem 0; color: #fff; font-size: 1.1rem; }
        
        .form-group { margin-bottom: 1rem; }
        .form-row { display: flex; gap: 10px; }
        
        label { 
            font-size: 0.75rem; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            display: block; 
            margin-bottom: 0.4rem; 
            font-weight: 600;
        }

        input, select { 
            width: 100%; 
            background: #0f172a; 
            border: 1px solid var(--border); 
            color: #fff; 
            padding: 0.75rem; 
            border-radius: 6px; 
            font-size: 1rem; /* Better for mobile touch */
            outline: none;
            transition: border-color 0.2s;
        }
        
        input:focus, select:focus { border-color: var(--accent); }

        button { 
            background: var(--accent); 
            color: white; 
            border: none; 
            padding: 1rem; 
            width: 100%; 
            cursor: pointer; 
            font-weight: bold; 
            border-radius: 6px; 
            font-size: 1rem;
            margin-top: 0.5rem;
            transition: opacity 0.2s;
        }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        button:active { opacity: 0.8; }

        .logs-container {
            flex: 1;
            background: #000;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            overflow-y: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .log-row { 
            margin-bottom: 4px; 
            border-bottom: 1px solid #222; 
            padding-bottom: 2px; 
            word-wrap: break-word; /* Prevents horizontal scroll on mobile */
        }

        /* Log Colors */
        .c-client { color: var(--yellow); }
        .c-server { color: var(--blue); }
        .c-error { color: var(--red); font-weight: bold; }
        .c-success { color: var(--green); font-weight: bold; }
        .c-info { color: var(--text-muted); }

    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2>SMTP Configuration</h2>
        
        <div class="form-group">
            <label>Host</label>
            <input type="text" id="host" placeholder="smtp.gmail.com" autocorrect="off" autocapitalize="off">
        </div>
        
        <div class="form-row">
            <div style="flex:1">
                <label>Port</label>
                <input type="number" id="port" value="587">
            </div>
            <div style="flex:1">
                <label>Security</label>
                <select id="secure">
                    <option value="tls">TLS (587)</option>
                    <option value="ssl">SSL (465)</option>
                    <option value="none">None (25)</option>
                </select>
            </div>
        </div>

        <div style="height: 1px; background: var(--border); margin: 1.5rem 0;"></div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" id="user" placeholder="email@example.com" autocorrect="off" autocapitalize="off">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="pass" placeholder="App Password / Password">
        </div>

        <div style="height: 1px; background: var(--border); margin: 1.5rem 0;"></div>

        <div class="form-group">
            <label>From Email</label>
            <input type="email" id="from" placeholder="sender@example.com">
        </div>
        <div class="form-group">
            <label>To Email</label>
            <input type="email" id="to" placeholder="recipient@example.com">
        </div>

        <button onclick="testSMTP()" id="btn">Send Test Email</button>
    </div>

    <div class="main">
        <h2>Connection Logs</h2>
        <div class="logs-container" id="logBox">
            <div class="log-row c-info">Ready to connect...</div>
        </div>
    </div>
</div>

<script>
    async function testSMTP() {
        const btn = document.getElementById('btn');
        const logs = document.getElementById('logBox');
        
        // UI Reset
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
            const req = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const res = await req.json();
            
            logs.innerHTML = ''; 
            
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