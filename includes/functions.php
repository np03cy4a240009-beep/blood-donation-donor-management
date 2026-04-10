<?php

function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function getStatusBadge($status) {
    $status = strtolower(trim((string)$status));

    switch ($status) {
        case 'approved':
        case 'completed':
        case 'confirmed':
        case 'safe':
        case 'eligible':
            return 'badge badge-green';

        case 'pending':
        case 'tested':
        case 'booked':
        case 'reserved':
            return 'badge badge-yellow';

        case 'rejected':
        case 'cancelled':
        case 'expired':
        case 'unsafe':
        case 'urgent':
        case 'not eligible':
            return 'badge badge-red';

        case 'available':
        case 'normal':
            return 'badge badge-blue';

        case 'rare':
        case 'rare priority':
            return 'badge badge-dark';

        default:
            return 'badge badge-blue';
    }
}

function getBloodGroups() {
    return [
        'A+', 'A-',
        'B+', 'B-',
        'AB+', 'AB-',
        'O+', 'O-',
        'A1+', 'A1-',
        'A2+', 'A2-',
        'A1B+', 'A1B-',
        'A2B+', 'A2B-',
        'Bombay (Oh)',
        'Rh-null'
    ];
}

function getRareBloodGroups() {
    return [
        'A2-',
        'A2B-',
        'Bombay (Oh)',
        'Rh-null'
    ];
}

function isRareBloodGroup($bloodGroup) {
    return in_array(trim((string)$bloodGroup), getRareBloodGroups(), true);
}

function getBloodGroupBadgeClass($bloodGroup) {
    return isRareBloodGroup($bloodGroup) ? 'badge badge-dark' : 'badge badge-blue';
}

function getRareBloodGroupNote($bloodGroup) {
    if (!isRareBloodGroup($bloodGroup)) {
        return '';
    }

    switch ($bloodGroup) {
        case 'Bombay (Oh)':
            return 'Special handling: extremely rare blood type. Keep priority donor contact active and reserve units carefully.';
        case 'Rh-null':
            return 'Special handling: extremely rare blood type. Manual approval and priority coordination recommended.';
        case 'A2-':
        case 'A2B-':
            return 'Special handling: rare subtype. Confirm matching before approval and prioritize stock monitoring.';
        default:
            return 'Special handling required for this rare blood group.';
    }
}

function getSymbol($type) {
    $symbols = [
        'donor' => '[D]',
        'blood' => '[B]',
        'request' => '[R]',
        'urgent' => '[!]',
        'appointment' => '[A]',
        'confirmed' => '[OK]',
        'cancelled' => '[X]',
        'testing' => '[T]',
        'safe' => '[+]',
        'unsafe' => '[-]',
        'inventory' => '[I]',
        'profile' => '[P]'
    ];

    return $symbols[$type] ?? '[*]';
}
?>