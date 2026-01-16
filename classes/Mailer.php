<?php
/**
 * GrowthEngineAI LMS - Mailer Class
 * 
 * Handles email sending via SMTP without external dependencies.
 * Supports TLS/SSL encryption and authentication.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

class Mailer {
    private $socket;
    private $lastError = '';
    private $debugOutput = [];
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string|null $textBody Plain text body (optional)
     * @param array $options Additional options (from_email, from_name, reply_to)
     * @return array ['success' => bool, 'message' => string]
     */
    public function send($to, $subject, $htmlBody, $textBody = null, $options = []) {
        $driver = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'log';
        
        switch ($driver) {
            case 'smtp':
                return $this->sendViaSMTP($to, $subject, $htmlBody, $textBody, $options);
            case 'mail':
                return $this->sendViaMail($to, $subject, $htmlBody, $textBody, $options);
            case 'log':
            default:
                return $this->sendViaLog($to, $subject, $htmlBody, $textBody, $options);
        }
    }
    
    /**
     * Send email via PHP's mail() function
     */
    private function sendViaMail($to, $subject, $htmlBody, $textBody, $options) {
        $fromEmail = $options['from_email'] ?? MAIL_FROM_ADDRESS;
        $fromName = $options['from_name'] ?? MAIL_FROM_NAME;
        
        $headers = $this->buildHeaders($fromEmail, $fromName, $options);
        $body = $this->buildMimeBody($htmlBody, $textBody);
        
        $headerString = '';
        foreach ($headers as $name => $value) {
            if ($name !== 'Subject' && $name !== 'To') {
                $headerString .= "{$name}: {$value}\r\n";
            }
        }
        
        if (mail($to, $subject, $body, $headerString)) {
            return ['success' => true, 'message' => 'Email sent successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email via mail().'];
        }
    }
    
    /**
     * Log email for development/testing
     */
    private function sendViaLog($to, $subject, $htmlBody, $textBody, $options) {
        $logMessage = sprintf(
            "[EMAIL LOG] To: %s | Subject: %s | From: %s <%s>",
            $to,
            $subject,
            $options['from_name'] ?? MAIL_FROM_NAME,
            $options['from_email'] ?? MAIL_FROM_ADDRESS
        );
        
        error_log($logMessage);
        error_log("[EMAIL BODY] " . strip_tags($htmlBody));
        
        // Also write to a file for easier viewing
        $logFile = __DIR__ . '/../logs/emails.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $fullLog = sprintf(
            "\n========================================\n" .
            "Date: %s\n" .
            "To: %s\n" .
            "Subject: %s\n" .
            "From: %s <%s>\n" .
            "----------------------------------------\n" .
            "%s\n" .
            "========================================\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $options['from_name'] ?? MAIL_FROM_NAME,
            $options['from_email'] ?? MAIL_FROM_ADDRESS,
            strip_tags($htmlBody)
        );
        
        file_put_contents($logFile, $fullLog, FILE_APPEND);
        
        return [
            'success' => true, 
            'message' => 'Email logged successfully (development mode). Check logs/emails.log'
        ];
    }
    
    /**
     * Send email via SMTP
     */
    private function sendViaSMTP($to, $subject, $htmlBody, $textBody, $options) {
        try {
            $host = SMTP_HOST;
            $port = SMTP_PORT;
            $encryption = SMTP_ENCRYPTION;
            $username = SMTP_USERNAME;
            $password = SMTP_PASSWORD;
            $timeout = defined('SMTP_TIMEOUT') ? SMTP_TIMEOUT : 30;
            
            $fromEmail = $options['from_email'] ?? MAIL_FROM_ADDRESS;
            $fromName = $options['from_name'] ?? MAIL_FROM_NAME;
            
            // Connect to SMTP server
            $socketHost = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
            
            $this->socket = @fsockopen($socketHost, $port, $errno, $errstr, $timeout);
            
            if (!$this->socket) {
                throw new Exception("Could not connect to SMTP server: {$errstr} ({$errno})");
            }
            
            stream_set_timeout($this->socket, $timeout);
            
            // Read greeting
            $this->getResponse();
            
            // Send EHLO
            $this->sendCommand("EHLO " . gethostname());
            
            // Start TLS if needed
            if ($encryption === 'tls') {
                $this->sendCommand("STARTTLS");
                
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("Failed to enable TLS encryption");
                }
                
                // Send EHLO again after TLS
                $this->sendCommand("EHLO " . gethostname());
            }
            
            // Authenticate
            if (!empty($username) && !empty($password)) {
                $this->sendCommand("AUTH LOGIN");
                $this->sendCommand(base64_encode($username));
                $this->sendCommand(base64_encode($password));
            }
            
            // Set sender
            $this->sendCommand("MAIL FROM:<{$fromEmail}>");
            
            // Set recipient
            $this->sendCommand("RCPT TO:<{$to}>");
            
            // Send data
            $this->sendCommand("DATA");
            
            // Build and send email content
            $headers = $this->buildHeaders($fromEmail, $fromName, $options);
            $headers['To'] = $to;
            $headers['Subject'] = $this->encodeHeader($subject);
            
            $emailContent = '';
            foreach ($headers as $name => $value) {
                $emailContent .= "{$name}: {$value}\r\n";
            }
            $emailContent .= "\r\n";
            $emailContent .= $this->buildMimeBody($htmlBody, $textBody);
            $emailContent .= "\r\n.";
            
            $this->sendCommand($emailContent);
            
            // Quit
            $this->sendCommand("QUIT");
            
            fclose($this->socket);
            
            return ['success' => true, 'message' => 'Email sent successfully via SMTP.'];
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            
            if ($this->socket) {
                fclose($this->socket);
            }
            
            error_log("SMTP Error: " . $e->getMessage());
            
            if (MAIL_DEBUG) {
                return ['success' => false, 'message' => 'SMTP Error: ' . $e->getMessage(), 'debug' => $this->debugOutput];
            }
            
            return ['success' => false, 'message' => 'Failed to send email. Please try again later.'];
        }
    }
    
    /**
     * Send SMTP command and get response
     */
    private function sendCommand($command) {
        $isData = (strpos($command, "Date:") === 0 || strpos($command, "\r\n.") !== false);
        
        if (MAIL_DEBUG && !$isData) {
            $this->debugOutput[] = "C: " . (strpos($command, 'AUTH') !== false ? '[AUTH DATA]' : $command);
        }
        
        fwrite($this->socket, $command . "\r\n");
        
        if ($isData) {
            // For DATA content, just wait for final response
            return $this->getResponse();
        }
        
        return $this->getResponse();
    }
    
    /**
     * Get SMTP server response
     */
    private function getResponse() {
        $response = '';
        
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            
            if (MAIL_DEBUG) {
                $this->debugOutput[] = "S: " . trim($line);
            }
            
            // Check if this is the last line (no hyphen after code)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        
        $code = substr($response, 0, 3);
        
        // Check for error codes
        if ($code >= 400) {
            throw new Exception("SMTP Error: " . trim($response));
        }
        
        return $response;
    }
    
    /**
     * Build email headers
     */
    private function buildHeaders($fromEmail, $fromName, $options) {
        $boundary = '----=_Part_' . md5(uniqid(time()));
        
        $headers = [
            'Date' => date('r'),
            'From' => $this->formatAddress($fromEmail, $fromName),
            'MIME-Version' => '1.0',
            'Content-Type' => "multipart/alternative; boundary=\"{$boundary}\"",
            'X-Mailer' => 'GrowthEngineAI Mailer'
        ];
        
        if (!empty($options['reply_to'])) {
            $headers['Reply-To'] = $options['reply_to'];
        }
        
        $this->boundary = $boundary;
        
        return $headers;
    }
    
    /**
     * Build MIME body with HTML and text parts
     */
    private function buildMimeBody($htmlBody, $textBody = null) {
        $boundary = $this->boundary ?? '----=_Part_' . md5(uniqid(time()));
        
        // Generate plain text from HTML if not provided
        if ($textBody === null) {
            $textBody = $this->htmlToText($htmlBody);
        }
        
        $body = "";
        
        // Plain text part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($textBody) . "\r\n\r\n";
        
        // HTML part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($htmlBody) . "\r\n\r\n";
        
        // End boundary
        $body .= "--{$boundary}--";
        
        return $body;
    }
    
    /**
     * Format email address with name
     */
    private function formatAddress($email, $name = null) {
        if ($name) {
            return $this->encodeHeader($name) . " <{$email}>";
        }
        return $email;
    }
    
    /**
     * Encode header for UTF-8
     */
    private function encodeHeader($text) {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }
    
    /**
     * Convert HTML to plain text
     */
    private function htmlToText($html) {
        // Remove style and script tags
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $text);
        
        // Convert line breaks
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text);
        
        // Convert links
        $text = preg_replace('/<a[^>]+href=([\'"])(.+?)\1[^>]*>(.+?)<\/a>/i', '$3 ($2)', $text);
        
        // Remove remaining tags
        $text = strip_tags($text);
        
        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s+\n/', "\n\n", $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Get debug output
     */
    public function getDebugOutput() {
        return $this->debugOutput;
    }
    
    // Property for boundary
    private $boundary;
}
