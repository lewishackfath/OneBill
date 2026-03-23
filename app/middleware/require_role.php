<?php

declare(strict_types=1);

function require_role(string|array $roles): void
{
    if (!user_has_role($roles)) {
        http_response_code(403);
        exit('Forbidden');
    }
}
