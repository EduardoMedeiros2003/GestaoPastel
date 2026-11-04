<?php
declare(strict_types=1);

const STATE_LABEL = ['hot' => 'Quente', 'warm' => 'Morno', 'cold' => 'Frio', 'stale' => 'Parado', 'won' => 'Ganho', 'lost' => 'Perdido'];
const ACTIVITY_ICON = ['call' => 'phone', 'whatsapp' => 'message', 'meeting' => 'calendar', 'email' => 'mail', 'note' => 'note', 'stage' => 'arrow'];
const ACTIVITY_LABEL = ['call' => 'Ligação', 'whatsapp' => 'WhatsApp', 'meeting' => 'Reunião', 'email' => 'E-mail', 'note' => 'Anotação', 'stage' => 'Sistema'];
const TASK_LOOK = [
    'step' => ['calendar', 'tone-blue'],
    'followup' => ['message', 'tone-amber'],
    'plan' => ['flag', 'tone-blue'],
    'date' => ['clock', 'tone-amber'],
    'close' => ['target', 'tone-green'],
];

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function money(float $v): string
{
    return 'R$ ' . number_format($v, 0, ',', '.');
}

function dmy(?string $date): string
{
    return $date ? date('d/m', strtotime(substr($date, 0, 10))) : '';
}

function time_ago(string $iso): string
{
    $minutes = (time() - strtotime($iso)) / 60;
    if ($minutes < 1) {
        return 'agora';
    }
    if ($minutes < 60) {
        return 'há ' . floor($minutes) . ' min';
    }
    if ($minutes < 60 * 24) {
        return 'há ' . floor($minutes / 60) . ' h';
    }
    $days = (int) floor($minutes / 60 / 24);
    return $days === 1 ? 'ontem' : "há {$days} dias";
}

function today_long(): string
{
    $days = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    $months = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    return $days[(int) date('w')] . ', ' . date('j') . ' de ' . $months[(int) date('n')];
}

function url(array $params): string
{
    return '?' . http_build_query($params);
}

function redirect(array $params): never
{
    header('Location: ' . url($params), true, 303);
    exit;
}

/** Volta para a página de onde o formulário veio (só aceita endereços deste sistema). */
function redirect_back(string $back): never
{
    $back = str_starts_with($back, '?') ? $back : '?p=hoje';
    header('Location: ' . $back, true, 303);
    exit;
}

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_ok(): bool
{
    return is_string($_POST['csrf'] ?? null) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/** Campos escondidos que todo formulário de ação leva. */
function form_head(string $action, ?string $id = null): string
{
    return csrf_field() . '<input type="hidden" name="action" value="' . e($action) . '">'
        . ($id !== null ? '<input type="hidden" name="id" value="' . e($id) . '">' : '');
}

function icon(string $name): string
{
    static $paths = [
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
        'columns' => 'M4 4h4v16H4zM10 4h4v11h-4zM16 4h4v7h-4z',
        'users' => 'M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM20 20v-1.5a3.5 3.5 0 0 0-2.5-3.35M15.5 4.2a3.5 3.5 0 0 1 0 6.6',
        'chart' => 'M4 20V11M10 20V5M16 20v-6M21 20H3',
        'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
        'plus' => 'M12 5v14M5 12h14',
        'sun' => 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4',
        'moon' => 'M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z',
        'auto' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 3v18',
        'phone' => 'M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
        'message' => 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z',
        'mail' => 'M4 6h16v12H4zM4 7l8 6 8-6',
        'calendar' => 'M5 5h14v15H5zM5 10h14M9 3v4M15 3v4',
        'note' => 'M6 3h9l4 4v14H6zM14 3v5h5M9 13h6M9 17h4',
        'flag' => 'M5 21V4M5 4h11l-2 4 2 4H5',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
        'target' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 12h.01',
        'check' => 'M5 12.5l4.5 4.5L19 7',
        'wallet' => 'M3 7h15a3 3 0 0 1 3 3v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 7l12-4v4M17 14h.01',
        'alert' => 'M12 9v4M12 17h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'back' => 'M19 12H5M11 6l-6 6 6 6',
        'x' => 'M6 6l12 12M18 6 6 18',
        'trash' => 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
    ];
    return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $paths[$name] . '"/></svg>';
}

function avatar(?array $user, bool $small = false): string
{
    if (!$user) {
        return '';
    }
    return '<span class="avatar' . ($small ? ' sm' : '') . '" style="background:' . e($user['color']) . '" title="' . e($user['name']) . '">'
        . e($user['initials']) . '</span>';
}

function first_name(?array $user): string
{
    return $user ? explode(' ', $user['name'])[0] : '';
}

/** Logo: pastel com a borda fechada no garfo. Com $size vai na página; sem, vira o ícone da aba. */
function logo_svg(?int $size = null): string
{
    $attrs = $size ? " width=\"{$size}\" height=\"" . round($size * 2 / 3) . '" aria-hidden="true"' : '';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32"' . $attrs . '>'
        . '<path d="M4 24 Q4 6 24 6 Q44 6 44 24 Z" fill="#f2b632" stroke="#b9801c" stroke-width="1.5"/>'
        . '<path d="M4 24 l3 3 3-3 3 3 3-3 3 3 3-3 3 3 3-3 3 3 3-3 3 3 3-3 3 3" fill="none" stroke="#b9801c" stroke-width="1.6" stroke-linejoin="round"/></svg>';
}
