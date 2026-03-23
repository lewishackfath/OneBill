<?php

declare(strict_types=1);

require_once APP_PATH . '/repositories/UserRepository.php';

final class AuthService
{
    public function attemptLogin(string $email, string $password): bool
    {
        $email = mb_strtolower(trim($email));
        $repo = new UserRepository();
        $user = $repo->findActiveByEmail($email);

        $this->recordLoginAttempt($email, $user !== null && password_verify($password, (string) $user['password_hash']));

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        login_user($user);
        $repo->updateLastLoginAt((int) $user['id']);
        audit_log((int) $user['id'], null, 'login_success', 'auth', (string) $user['id'], 'User signed in successfully');
        return true;
    }

    private function recordLoginAttempt(string $email, bool $success): void
    {
        $stmt = db()->prepare('INSERT INTO login_attempts (email, ip_address, user_agent, was_successful) VALUES (:email, :ip_address, :user_agent, :was_successful)');
        $stmt->execute([
            ':email' => $email,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':was_successful' => $success ? 1 : 0,
        ]);
    }
}
