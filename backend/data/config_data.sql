INSERT INTO app_config (config_key, config_value) VALUES
    ('max_avatar_count', '3'),
    ('restricted_data_retention_hours', '2400'),
    ('smtp_enabled', '0'),
    ('smtp_host', ''),
    ('smtp_port', '587'),
    ('smtp_secure', 'tls'),
    ('smtp_username', ''),
    ('smtp_password', ''),
    ('smtp_from_email', ''),
    ('smtp_from_name', 'AIMonkey'),
    ('smtp_timeout', '10')
ON DUPLICATE KEY UPDATE
    config_value = VALUES(config_value);
