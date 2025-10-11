
<?php
class Utils {
    public static function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function formatMoney($value) {
        return 'R$ ' . number_format((float)$value, 2, ',', '.');
    }

    public static function formatDate($date) {
        return date('d/m/Y', strtotime($date));
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
