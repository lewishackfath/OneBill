-- Run this once after adjusting the email address if needed.
INSERT INTO users (email, password_hash, first_name, last_name, is_active)
VALUES ("admin@example.com", "$2y$12$nF919mlm8xnUtsvRJ8ozeeo8W/xMULT/sHbhrHqwSNBBtdBYKvgnW", "System", "Administrator", 1);

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
INNER JOIN roles r ON r.role_key = "super_admin"
WHERE u.email = "admin@example.com";
