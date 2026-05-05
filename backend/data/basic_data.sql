TRUNCATE TABLE password_resets;
DELETE FROM users;
ALTER TABLE users AUTO_INCREMENT = 1;

INSERT INTO users (
    account_uid,
    username,
    email,
    password_hash,
    avatar_state,
    avatar_count_for_contact,
    created_at,
    last_login_at
) VALUES
('seed_user_5511', 'alice-alpha', 'alice@example.com', '$2y$10$A5x9mXxQ8Vt7Jw6Nk2Gf4eH6qP7mR8sT9uV0wX1yZ2aB3cD4eF5gG', 'active', 1, '2026-04-24 10:00:00', '2026-04-25 10:06:00' ),
('seed_user_0002', 'alice-beta', 'alice@example.com', '$2y$10$B6y0nYyR9Wu8Kx7Pl3Hg5fI7rQ8nS9tU0vW1xY2zA3bC4dE5fG6hH', 'active', 2, '2026-04-24 10:02:00', '2026-04-25 10:06:00' ),
('seed_user_0003', 'alice-gamma', 'alice@example.com', '$2y$10$C7z1oZzS0Xv9Ly8Qm4Ih6gJ8sR9oT0uV1wX2yZ3aB4cD5eF6gH7iI', 'active', 3, '2026-04-24 10:04:00', '2026-04-25 10:06:00' ),
('seed_user_0001', 'lukelin', 'luke.l.lin@hotmail.com', '$2y$10$D8a2pAaT1Yw0Mz9Rn5Ji7hK9tS0pU1vW2xY3zA4bC5dE6fG7hI8jJ', 'frozen', 4, '2026-04-24 10:06:00', '2026-04-25 10:06:00' );