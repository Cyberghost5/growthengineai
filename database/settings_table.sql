-- Settings table for storing system configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Paystack settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_encrypted`) VALUES
('paystack_public_key', 'pk_test_your_public_key_here', 'text', 'payment', 'Paystack Public Key', 0),
('paystack_secret_key', 'sk_test_your_secret_key_here', 'text', 'payment', 'Paystack Secret Key', 1),
('paystack_mode', 'test', 'text', 'payment', 'Paystack Mode (test/live)', 0),
('paystack_currency', 'NGN', 'text', 'payment', 'Payment Currency', 0)
ON DUPLICATE KEY UPDATE setting_value=setting_value;
