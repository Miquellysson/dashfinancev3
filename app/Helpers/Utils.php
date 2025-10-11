
<?php
class Utils {
    public static function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeHtml(string $content): string {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    public static function slugify(string $text): string {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^a-zA-Z0-9]+/', '-', $text);
        return strtolower(trim($text, '-'));
    }

    public static function formatMoney($value) {
        return 'R$ ' . number_format((float)$value, 2, ',', '.');
    }

    public static function normalizePhone(?string $value): ?string {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        return $digits !== '' ? $digits : null;
    }

    public static function formatPhone(?string $digits): string {
        if (!$digits) {
            return '';
        }
        $len = strlen($digits);
        if ($len === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }
        if ($len === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }
        return $digits;
    }

    public static function decimalFromInput($value): float {
        if (is_numeric($value)) {
            return (float)$value;
        }
        $clean = str_replace(['.', ' '], '', (string)$value);
        $clean = str_replace(',', '.', $clean);
        return is_numeric($clean) ? (float)$clean : 0.0;
    }

    public static function sanitizeArray(array $input): array {
        $clean = [];
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $clean[$key] = self::sanitize($value);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    public static function validateEmail(string $email): bool {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function formatDate($date) {
        return date('d/m/Y', strtotime($date));
    }

    public static function buildSortUrl(string $column): string {
        $query = $_GET;
        $currentColumn = $query['order_by'] ?? 'default';
        $currentDir = strtoupper($query['order_dir'] ?? 'DESC');
        if ($currentColumn === $column) {
            $query['order_dir'] = $currentDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $query['order_dir'] = 'DESC';
        }
        $query['order_by'] = $column;
        return '?' . http_build_query($query);
    }

    public static function buildPageUrl(int $page): string {
        $query = $_GET;
        $query['page'] = $page;
        return '?' . http_build_query($query);
    }

    public static function badgeForPaymentStatus(string $status): string {
        $status = ucfirst(strtolower($status));
        return match ($status) {
            'Pago' => 'badge-soft-success',
            'Parcial' => 'badge-soft-warning',
            'Cancelado' => 'badge-soft-danger',
            default => 'badge-soft-secondary',
        };
    }

    public static function renderSatisfactionBadge(string $status): string {
        $map = [
            'Satisfeito' => 'badge-soft-success',
            'Parcialmente Satisfeito' => 'badge-soft-warning',
            'Insatisfeito' => 'badge-soft-danger',
            'Aguardando Feedback' => 'badge-soft-secondary',
        ];
        $class = $map[$status] ?? 'badge-soft-secondary';
        return '<span class="badge ' . $class . '">' . self::sanitize($status) . '</span>';
    }

    public static function diffHours(?float $estimadas, ?float $reais): float {
        if ($estimadas === null || $reais === null) {
            return 0.0;
        }
        return round($reais - $estimadas, 2);
    }

    public static function redirect($url, $message = null) {
        if ($message) {
            $_SESSION['flash_message'] = $message;
        }
        header("Location: $url");
        exit;
    }

    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $msg;
        }
        return null;
    }
}
