<?php
/**
 * NoticeConfirm — core library
 * Handles all notice.ro API calls and order confirmation flow utilities.
 * Compatible with OpenCart 2.x and 3.x
 */
class NoticeConfirm {

    private $registry;
    private $db;
    private $settings = [];

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db       = $registry->get('db');
        $this->loadSettings();
    }

    private function loadSettings() {
        $query = $this->db->query("
            SELECT `key`, `value` FROM `" . DB_PREFIX . "setting`
            WHERE `code` = 'noticeconfirm'
        ");
        foreach ($query->rows as $row) {
            $key = str_replace('noticeconfirm_', '', $row['key']);
            $this->settings[$key] = $row['value'];
        }
    }

    public function get($key, $default = null) {
        return isset($this->settings[$key]) && $this->settings[$key] !== ''
            ? $this->settings[$key]
            : $default;
    }

    public function isEnabled() {
        return (bool)$this->get('status', 0);
    }

    // ─── notice.ro API ────────────────────────────────────────────────────────

    public function sendAudio(string $text, string $phone): int {
        $result = $this->curl('https://api.notice.ro/api/v1/audio', [
            'number'       => $phone,
            'text'         => $text,
            'callback_url' => $this->callbackUrl(),
            'type'         => 'confirmation',
            'retriable'    => false,
        ]);
        return (int)($result['audio_id'] ?? 0);
    }

    public function sendWhatsapp(string $text, string $phone): array {
        $phone = $this->formatPhoneIntl($phone);
        return $this->curl('https://api.notice.ro/api/v1/whatsapp/send', [
            'number'  => $phone,
            'message' => $text,
        ]);
    }

    public function sendSms(string $text, string $phone): array {
        return $this->curl('https://api.notice.ro/api/v1/sms-out', [
            'number'  => $phone,
            'message' => $text,
        ]);
    }

    // ─── Order helpers ────────────────────────────────────────────────────────

    public function addOrderHistory(int $order_id, int $status_id, string $comment, bool $confirmed = false): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET
            order_status_id = '" . $status_id . "',
            date_modified   = NOW()
            WHERE order_id  = '" . $order_id . "'
        ");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET
            order_id        = '" . $order_id . "',
            order_status_id = '" . $status_id . "',
            notify          = '0',
            comment         = '" . $this->db->escape($comment) . "',
            date_added      = NOW()
        ");
        if ($confirmed) {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET
                confirmata   = 1,
                confirm_date = NOW()
                WHERE order_id = '" . $order_id . "'
            ");
        }
    }

    public function repairPhone(string $phone, int $order_id): string {
        if (strlen($phone) === 10) {
            return $phone;
        }
        $phone = str_replace(['o', 'O', ' ', '.', '-'], ['0', '0', '', '', ''], $phone);
        if (strpos($phone, '+4') !== false) {
            $phone = str_replace('+4', '', $phone);
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET telephone = '" . $this->db->escape($phone) . "' WHERE order_id = '" . $order_id . "'");
        return $phone;
    }

    public function log(string $channel, string $message): void {
        $dir  = defined('DIR_LOGS') ? DIR_LOGS : sys_get_temp_dir() . '/';
        $path = $dir . date('Y-m-d') . '-noticeconfirm-' . $channel . '.log';
        file_put_contents($path, date('H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    private function curl(string $url, array $params): array {
        $bearer = $this->get('bearer', '');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
        ]);
        if ($bearer) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
            curl_setopt($ch, CURLOPT_XOAUTH2_BEARER, $bearer);
        }
        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        curl_close($ch);

        if ($errno || !$result) {
            return ['error' => true, 'message' => curl_strerror($errno ?: 0)];
        }
        return json_decode($result, true) ?: ['error' => true, 'raw' => $result];
    }

    private function callbackUrl(): string {
        $base = $this->get('callback_url', '');
        if ($base) {
            return $base;
        }
        $config = $this->registry->get('config');
        $store_url = $config ? rtrim($config->get('config_url'), '/') : '';
        return $store_url . '/index.php?route=api/audio/callback';
    }

    private function formatPhoneIntl(string $phone): string {
        if (strpos($phone, '+') === 0) {
            return ltrim($phone, '+');
        }
        if (strpos($phone, '0') === 0) {
            return '4' . $phone;
        }
        return $phone;
    }
}
