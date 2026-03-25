<?php

declare(strict_types=1);

function forbid(): never
{
    http_response_code(403);

    if (!headers_sent() && is_file(PUBLIC_PATH . '/403.php')) {
        require PUBLIC_PATH . '/403.php';
        exit;
    }

    exit('Forbidden');
}

function require_role(string|array $roles): void
{
    if (!user_has_role($roles)) {
        forbid();
    }
}

function require_platform_admin_or_higher(): void
{
    if (!is_platform_user()) {
        forbid();
    }
}

function require_user_admin_access(): void
{
    if (!can_manage_users()) {
        forbid();
    }
}

function require_settings_access(): void
{
    if (!can_access_settings_page()) {
        forbid();
    }
}

function require_audit_access(): void
{
    if (!can_access_audit_page()) {
        forbid();
    }
}

function require_client_scope(int $clientId): void
{
    if (!can_access_client($clientId)) {
        forbid();
    }
}
