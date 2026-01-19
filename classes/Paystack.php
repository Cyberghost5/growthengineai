<?php
/**
 * GrowthEngineAI LMS - Paystack Payment Integration
 * Handles payment initialization, verification, and management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';

class Paystack {
    private $db;
    private $secretKey;
    private $publicKey;
    private $settings;
    
    public function __construct() {
        $this->db = getDB();
        $this->settings = new Settings();
        
        // Load keys from database
        $this->publicKey = $this->settings->get('paystack_public_key', 'YOUR_PAYSTACK_PUBLIC_KEY');
        $this->secretKey = $this->settings->get('paystack_secret_key', 'YOUR_PAYSTACK_SECRET_KEY');
    }
    
    /**
     * Get Paystack public key
     */
    public function getPublicKey() {
        return $this->publicKey;
    }
    
    /**
     * Initialize a payment transaction
     */
    public function initializePayment($email, $amount, $courseId, $userId, $metadata = []) {
        // Convert amount to kobo (Paystack uses smallest currency unit)
        $amountInKobo = $amount * 100;
        
        // Generate unique reference
        $reference = 'GE_' . time() . '_' . uniqid();
        
        // Get currency and callback URL from settings
        $currency = $this->settings->get('paystack_currency', 'NGN');
        $callbackUrl = SITE_URL . '/student/payment-callback.php?course_id=' . $courseId;
        
        // Prepare payment data
        $data = [
            'email' => $email,
            'amount' => $amountInKobo,
            'reference' => $reference,
            'currency' => $currency,
            'callback_url' => $callbackUrl,
            'metadata' => array_merge([
                'user_id' => $userId,
                'course_id' => $courseId,
                'custom_fields' => [
                    [
                        'display_name' => 'Course Purchase',
                        'variable_name' => 'course_id',
                        'value' => $courseId
                    ]
                ]
            ], $metadata)
        ];
        
        // Make API request
        $response = $this->makeRequest('transaction/initialize', 'POST', $data);
        
        if ($response && $response['status']) {
            // Save transaction to database
            $this->createTransaction([
                'reference' => $reference,
                'user_id' => $userId,
                'course_id' => $courseId,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'payment_method' => 'paystack',
                'email' => $email
            ]);
            
            return [
                'success' => true,
                'authorization_url' => $response['data']['authorization_url'],
                'access_code' => $response['data']['access_code'],
                'reference' => $reference
            ];
        }
        
        // Log the full response for debugging
        error_log("Paystack initialization failed. Full response: " . json_encode($response));
        
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to initialize payment',
            'debug_response' => $response
        ];
    }
    
    /**
     * Verify a payment transaction
     */
    public function verifyPayment($reference) {
        error_log("=== PAYSTACK VERIFICATION START ===");
        error_log("Verifying reference: {$reference}");
        
        // First check if transaction exists in our database
        $existingTransaction = $this->getTransactionByReference($reference);
        if (!$existingTransaction) {
            error_log("ERROR: Transaction not found in database for reference: {$reference}");
            return [
                'success' => false,
                'message' => 'Transaction not found in our records'
            ];
        }
        
        error_log("Transaction found in DB - ID: {$existingTransaction['id']}, Status: {$existingTransaction['status']}");
        
        // If already completed, return success with cached data
        // Note: Caller should still check enrollment status!
        if ($existingTransaction['status'] === 'completed') {
            error_log("Transaction already completed, returning cached result");
            
            // Parse saved response, fallback to constructing from transaction data
            $savedResponse = json_decode($existingTransaction['paystack_response'] ?? '{}', true);
            if (empty($savedResponse)) {
                $savedResponse = [
                    'amount' => $existingTransaction['amount'] * 100, // Convert back to kobo
                    'currency' => $existingTransaction['currency'],
                    'status' => 'success'
                ];
            }
            
            return [
                'success' => true,
                'data' => $savedResponse,
                'transaction' => $existingTransaction,
                'cached' => true
            ];
        }
        
        // Call Paystack API
        $response = $this->makeRequest("transaction/verify/" . urlencode($reference), 'GET');
        
        error_log("Paystack API response received: " . json_encode($response));
        
        // Check for API success
        if ($response && isset($response['status']) && $response['status'] === true) {
            $paymentStatus = $response['data']['status'] ?? 'unknown';
            error_log("Payment status from Paystack: {$paymentStatus}");
            
            if ($paymentStatus === 'success') {
                // Update transaction in database
                $updateResult = $this->updateTransaction($reference, [
                    'status' => 'completed',
                    'paid_at' => date('Y-m-d H:i:s'),
                    'paystack_response' => json_encode($response['data'])
                ]);
                
                error_log("Transaction update result: " . ($updateResult ? 'SUCCESS' : 'FAILED'));
                
                // Fetch updated transaction
                $updatedTransaction = $this->getTransactionByReference($reference);
                error_log("Updated transaction status: " . ($updatedTransaction['status'] ?? 'NOT FOUND'));
                
                error_log("=== PAYSTACK VERIFICATION SUCCESS ===");
                
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'transaction' => $updatedTransaction
                ];
            }
            
            // Payment not successful (abandoned, failed, etc.)
            error_log("Payment not successful - Status: {$paymentStatus}");
            $this->updateTransaction($reference, [
                'status' => 'failed',
                'paystack_response' => json_encode($response['data'])
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment was not successful. Status: ' . $paymentStatus,
                'payment_status' => $paymentStatus
            ];
        }
        
        // API call failed or returned error
        $errorMessage = $response['message'] ?? 'Unknown error from payment gateway';
        error_log("Paystack API error: {$errorMessage}");
        error_log("=== PAYSTACK VERIFICATION FAILED ===");
        
        return [
            'success' => false,
            'message' => $errorMessage,
            'debug_response' => $response
        ];
    }
    
    /**
     * Make HTTP request to Paystack API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = []) {
        $apiUrl = 'https://api.paystack.co';
        $url = $apiUrl . '/' . $endpoint;
        
        // Log the request being sent (mask secret key for security)
        $maskedKey = substr($this->secretKey, 0, 10) . '...' . substr($this->secretKey, -4);
        error_log("Paystack API Request - Endpoint: {$endpoint}, Method: {$method}, Key: {$maskedKey}");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 30 second connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification for security
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        // Log the raw response
        error_log("Paystack API Response - HTTP Code: {$httpCode}, Response length: " . strlen($response));
        
        // Handle cURL errors
        if ($curlErrno) {
            error_log("Paystack cURL Error #{$curlErrno}: {$curlError}");
            return [
                'status' => false,
                'message' => 'Connection error: ' . $curlError,
                'curl_error' => true
            ];
        }
        
        // Parse response
        $decodedResponse = json_decode($response, true);
        
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Paystack JSON decode error: " . json_last_error_msg() . " - Raw: " . substr($response, 0, 500));
            return [
                'status' => false,
                'message' => 'Invalid response from payment gateway'
            ];
        }
        
        // Log success/failure
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Paystack API Success - Status: " . ($decodedResponse['status'] ? 'true' : 'false'));
            return $decodedResponse;
        }
        
        // Log API errors with more details
        error_log("Paystack API Error - HTTP {$httpCode}: " . json_encode($decodedResponse));
        
        return $decodedResponse ?: [
            'status' => false,
            'message' => 'HTTP Error: ' . $httpCode
        ];
    }
    
    /**
     * Create a new transaction record
     */
    private function createTransaction($data) {
        try {
            $sql = "INSERT INTO transactions 
                    (reference, user_id, course_id, amount, currency, status, payment_method, email, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['reference'],
                $data['user_id'],
                $data['course_id'],
                $data['amount'],
                $data['currency'],
                $data['status'],
                $data['payment_method'],
                $data['email']
            ]);
        } catch (PDOException $e) {
            error_log("Transaction creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update transaction status
     */
    private function updateTransaction($reference, $data) {
        try {
            $updates = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
            
            $params[] = $reference;
            
            $sql = "UPDATE transactions SET " . implode(', ', $updates) . " WHERE reference = ?";
            
            error_log("Updating transaction - SQL: {$sql}, Params: " . json_encode($params));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            
            error_log("Transaction update result - Success: " . ($result ? 'true' : 'false') . ", Rows affected: {$rowCount}");
            
            if ($rowCount === 0) {
                error_log("WARNING: No rows updated for reference: {$reference}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Transaction update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save transaction to database
     */
    public function saveTransaction($data) {
        $currency = $this->settings->get('paystack_currency', 'NGN');
        
        return $this->createTransaction([
            'reference' => $data['reference'],
            'user_id' => $data['user_id'],
            'course_id' => $data['course_id'],
            'amount' => $data['amount'],
            'currency' => $currency,
            'status' => $data['status'],
            'payment_method' => 'paystack',
            'email' => $data['email'] ?? ''
        ]);
    }
    
    /**
     * Get transaction by reference
     */
    public function getTransactionByReference($reference) {
        $sql = "SELECT t.*, c.title as course_title, c.slug as course_slug,
                CONCAT(u.first_name, ' ', u.last_name) as user_name
                FROM transactions t
                LEFT JOIN courses c ON t.course_id = c.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.reference = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reference]);
        return $stmt->fetch();
    }
    
    /**
     * Get all transactions for a user
     */
    public function getUserTransactions($userId, $status = null) {
        $sql = "SELECT t.*, c.title as course_title, c.slug as course_slug, c.thumbnail as course_image
                FROM transactions t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.user_id = ?";
        
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get transaction statistics for a user
     */
    public function getUserTransactionStats($userId) {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_spent,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions
                FROM transactions
                WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
