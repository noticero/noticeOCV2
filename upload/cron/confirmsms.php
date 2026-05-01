<?php
/**
 * Notice.ro Order Confirmation Cron
 *
 * Run every 1-5 minutes via cron:
 *   php -f /path/to/opencart/cron/confirmsms.php
 *
 * Set OC_ROOT below to the absolute path of your OpenCart installation.
 */

define('OC_ROOT', dirname(__DIR__) . '/');  // adjust if cron/ is not inside OC root

date_default_timezone_set('Europe/Bucharest');
ini_set('max_execution_time', 0);

require_once(OC_ROOT . 'config.php');
require_once(DIR_SYSTEM . 'startup.php');

$application_config = 'admin';
$registry = new Registry();

$loader = new Loader($registry);
$registry->set('load', $loader);

$config = new Config();
$config->load('default');
$config->load($application_config);
$registry->set('config', $config);

$registry->set('event', new Event($registry));
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

require_once(DIR_SYSTEM . 'library/noticeconfirm.php');
require_once(DIR_SYSTEM . 'library/orderconfirmation.php');

$nc = new NoticeConfirm($registry);

if (!$nc->isEnabled()) {
    exit(0);
}

// ─── Settings ────────────────────────────────────────────────────────────────

$hour           = (int)date('H');
$timestamp      = time();
$call_start     = (int)$nc->get('call_hour_start', 10);
$call_end       = (int)$nc->get('call_hour_end', 21);
$min_age        = (int)$nc->get('min_age', 2);
$delay_wapp     = (int)$nc->get('delay_wapp', 15);
$delay_sms      = (int)$nc->get('delay_sms', 15);
$delay_recall   = (int)$nc->get('delay_recall', 15);
$max_refuzate   = (int)$nc->get('max_refuzate', 1);
$status_notify  = (int)$nc->get('status_notify', 22);
$status_refused = (int)$nc->get('status_refused', 19);

// Pending statuses: stored as comma-separated string or array (multiselect)
$pending_raw = $nc->get('status_pending', '1,22');
if (is_array($pending_raw)) {
    $pending_ids = array_map('intval', $pending_raw);
} else {
    $pending_ids = array_map('intval', array_map('trim', explode(',', $pending_raw)));
}
$pending_ids   = array_filter($pending_ids);
$pending_sql   = implode(',', $pending_ids);

if (!$pending_sql) {
    exit(0);
}

// ─── Audio/confirm message templates ─────────────────────────────────────────

function buildAudioText(string $total): string {
    return 'Bună ziua! Vă contactăm pentru confirmarea comenzii plasate pe gave punct ro în valoare de '
        . $total . ' lei. Termenul standard de livrare este de o zi până la două zile lucrătoare. '
        . 'Pentru detalii suplimentare ne găsiți la numărul afișat pe site. '
        . 'Apăsați tasta 1 pentru confirmare sau tasta 9 pentru anulare. Mulțumim!';
}

function buildWappText(int $order_id, string $total): string {
    return 'Comanda ' . $order_id . ' în valoare de ' . $total
        . ' lei a fost înregistrată cu succes pe GAVE. '
        . 'Livrare se va face prin CURIER rapid în 1-2 zile lucrătoare de la plasare. '
        . 'Confirmă comanda plasată cu textul DA';
}

function buildSmsText(int $order_id, string $total, string $token): string {
    return 'Ai facut alegerea perfecta! Comanda GAVE ' . $order_id
        . ' a fost inregistrata cu succes. Total de plata ' . $total
        . ' lei. Confirma apasand pe urmatorul link: gave.ro/c/' . $token;
}

// ─── Main loop ────────────────────────────────────────────────────────────────

$orderConfirmation = new OrderConfirmation($db);

$orders = $db->query("
    SELECT o.order_id, o.telephone, o.date_added, o.total, o.confirmata,
           a.id            AS audio_rec_id,
           a.called, a.sms, a.whatsapp, a.result,
           a.call_date, a.sms_date, a.whatsapp_date
    FROM `" . DB_PREFIX . "order` o
    LEFT JOIN `" . DB_PREFIX . "audio` a ON a.order_id = o.order_id
    WHERE o.order_status_id IN (" . $pending_sql . ")
      AND (o.confirmata IS NULL OR o.confirmata != 1)
      AND o.telephone != '0700000000'
");

foreach ($orders->rows as $order) {
    // Repair phone number if malformed
    if (strlen($order['telephone']) !== 10) {
        $order['telephone'] = $nc->repairPhone($order['telephone'], (int)$order['order_id']);
    }

    $diff = ($timestamp - strtotime($order['date_added'])) / 60;
    if ($diff < $min_age) {
        continue;
    }

    // Skip phone numbers with too many cancellations
    $refuzate = $db->query("
        SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "order`
        WHERE order_status_id = '" . $status_refused . "'
          AND telephone       = '" . $db->escape($order['telephone']) . "'
    ");
    if ((int)$refuzate->row['cnt'] > $max_refuzate) {
        continue;
    }

    $order_id = (int)$order['order_id'];
    $phone    = $order['telephone'];
    $total    = str_replace('.', ',', round($order['total'], 2));

    // ── STEP 1: No audio record → voice call (within call window) ────────────
    if (empty($order['audio_rec_id'])) {
        if ($hour < $call_start || $hour >= $call_end) {
            continue;
        }
        $audio_text = buildAudioText($total);
        $audio_id   = $nc->sendAudio($audio_text, $phone);

        $db->query("INSERT INTO `" . DB_PREFIX . "audio` SET
            order_id  = '" . $order_id . "',
            audio_id  = '" . $audio_id . "',
            called    = 1,
            text      = '" . $db->escape($audio_text) . "',
            call_date = NOW()
        ");
        $nc->addOrderHistory($order_id, $status_notify, 'Client notificat prin apel vocal');
        $nc->log('call', 'Step1 call order=' . $order_id . ' audio_id=' . $audio_id);
        continue;
    }

    // ── STEP 2: 15min after call, no WhatsApp, not confirmed → WhatsApp ──────
    if ($order['called'] == 1
        && $order['whatsapp'] == 0
        && $order['result'] != 1
        && $order['call_date']
        && ($timestamp - strtotime($order['call_date'])) / 60 >= $delay_wapp
    ) {
        $wapp_text = buildWappText($order_id, $total);
        $nc->sendWhatsapp($wapp_text, $phone);
        $db->query("UPDATE `" . DB_PREFIX . "audio` SET whatsapp = 1, whatsapp_date = NOW() WHERE id = '" . (int)$order['audio_rec_id'] . "'");
        $nc->log('whatsapp', 'Step2 wapp order=' . $order_id);
        continue;
    }

    // ── STEP 3: 15min after WhatsApp, no SMS → SMS with confirmation link ────
    if ($order['whatsapp'] == 1
        && $order['sms'] == 0
        && $order['whatsapp_date']
        && ($timestamp - strtotime($order['whatsapp_date'])) / 60 >= $delay_sms
    ) {
        $token    = $orderConfirmation->assignTokenToOrder($order_id);
        $sms_text = buildSmsText($order_id, $total, $token);
        $nc->sendSms($sms_text, $phone);
        $db->query("UPDATE `" . DB_PREFIX . "audio` SET sms = 1, sms_date = NOW() WHERE id = '" . (int)$order['audio_rec_id'] . "'");
        $db->query("UPDATE `" . DB_PREFIX . "order` SET sms_sent = 1 WHERE order_id = '" . $order_id . "'");
        $nc->addOrderHistory($order_id, $status_notify, 'Client notificat prin SMS');
        $nc->log('sms', 'Step3 sms order=' . $order_id);
        continue;
    }

    // ── STEP 4: 15min after SMS, recall (result=-1 sentinel prevents re-trigger) ──
    if ($order['sms'] == 1
        && ($order['result'] === null || $order['result'] === '0' || $order['result'] === '9')
        && $order['sms_date']
        && ($timestamp - strtotime($order['sms_date'])) / 60 >= $delay_recall
        && $hour >= $call_start && $hour < $call_end
    ) {
        $audio_text = buildAudioText($total);
        $audio_id   = $nc->sendAudio($audio_text, $phone);
        $db->query("UPDATE `" . DB_PREFIX . "audio` SET
            audio_id  = '" . $audio_id . "',
            call_date = NOW(),
            result    = -1
            WHERE id  = '" . (int)$order['audio_rec_id'] . "'");
        $nc->addOrderHistory($order_id, $status_notify, 'Client notificat prin recall vocal');
        $nc->log('recall', 'Step4 recall order=' . $order_id . ' audio_id=' . $audio_id);
    }
}
