<?php
// One place for status wording and colors, so Home, Records and staff views always match.
require_once __DIR__ . '/security.php';

function is_resubmit($status): bool {
    return in_array($status, ['Resubmit Required', 'Rejected'], true);
}

function status_label($status): string {
    if (!$status)                 return 'Not Started';
    if ($status === 'Pending')    return 'Pending Review';
    if ($status === 'Approved')   return 'Approved';
    if (is_resubmit($status))     return 'Needs Resubmission';
    return (string)$status;
}

/** Bootstrap color name for a status. */
function status_color($status): string {
    if ($status === 'Pending')  return 'warning';
    if ($status === 'Approved') return 'success';
    if (is_resubmit($status))   return 'danger';
    return 'secondary';
}

function status_badge($status): string {
    $color = status_color($status);
    $text  = ($color === 'warning') ? ' text-dark' : '';
    $dim   = !$status ? ' opacity-50' : '';
    return '<span class="badge bg-' . $color . $text . $dim . '" style="font-size:10px;">'
         . h(status_label($status)) . '</span>';
}