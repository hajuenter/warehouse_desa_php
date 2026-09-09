CREATE TABLE webhook_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(100),
    config_id INT,
    event_type VARCHAR(20),
    table_name VARCHAR(100),
    record_id INT,
    payload JSON,
    status ENUM('pending', 'success', 'failed', 'retry'),
    retry_count INT,
    error_message TEXT,
    received_at TIMESTAMP NULL DEFAULT NULL,
    processed_at TIMESTAMP NULL DEFAULT NULL
);