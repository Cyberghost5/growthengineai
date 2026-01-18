<?php
/**
 * GrowthEngineAI LMS - Paystack Configuration
 * 
 * Get your API keys from: https://dashboard.paystack.com/#/settings/developer
 */

// Paystack API Keys
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_public_key_here'); // Replace with your public key
define('PAYSTACK_SECRET_KEY', 'sk_test_your_secret_key_here'); // Replace with your secret key

// Paystack API URL
define('PAYSTACK_API_URL', 'https://api.paystack.co');

// Currency
define('PAYSTACK_CURRENCY', 'NGN'); // Nigerian Naira

// Payment callback URL
define('PAYSTACK_CALLBACK_URL', SITE_URL . '/student/payment-callback.php');
