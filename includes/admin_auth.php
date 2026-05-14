<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

start_secure_session();

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('login.php');
    }
}

function current_admin(): ?array
{
    if (!admin_logged_in()) {
        return null;
    }

    $stmt = pdo()->prepare('SELECT id, username, nama FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    return $admin ?: null;
}
