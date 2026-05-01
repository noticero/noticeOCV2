# CLAUDE.md — noticeconfirm-oc-plugin

## Overview

OpenCart 2.x / 3.x plugin that implements a multi-step order confirmation flow using the **notice.ro API**:

1. **Voice call** — immediate after order, within configured hours
2. **WhatsApp** — N min after call if no confirmation
3. **SMS with link** — N min after WhatsApp if no confirmation
4. **Recall** — N min after SMS if still not confirmed

All delays, call hours, and order status mappings are configurable from the admin settings page. No hardcoded values.

---

## Directory Structure

```
upload/                                         ← drop onto any OC install root
├── admin/
│   ├── controller/extension/module/noticeconfirm.php         ← settings + install/uninstall
│   ├── model/extension/module/noticeconfirm.php              ← creates/drops ockg_audio table
│   ├── language/en-gb/extension/module/noticeconfirm.php
│   └── view/template/extension/module/
│       ├── noticeconfirm.tpl                                  ← OC 2.x
│       └── noticeconfirm.twig                                 ← OC 3.x
├── catalog/
│   └── controller/extension/module/noticeconfirm_callback.php  ← notice.ro callback
└── system/library/
    ├── noticeconfirm.php                                       ← core library
    └── noticeconfirm_cron.php                                  ← cron script
```

> **OC3 installer whitelist:** only `admin/controller/extension/`, `admin/model/extension/`, `admin/view/template/extension/`, `admin/language/`, `catalog/controller/extension/`, `system/library/` and a few others are allowed. All plugin files fall within these paths.

---

## Core Library — `system/library/noticeconfirm.php`

Instantiate with the OC registry:

```php
$this->load->library('noticeconfirm');
$nc = new NoticeConfirm($this->registry);
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `sendAudio(string $text, string $phone)` | `int` | POST to `api/v1/audio`, returns `audio_id` |
| `sendWhatsapp(string $text, string $phone)` | `array` | POST to `api/v1/whatsapp/send` |
| `sendSms(string $text, string $phone)` | `array` | POST to `api/v1/sms-out` |
| `addOrderHistory(int $order_id, int $status_id, string $comment, bool $confirmed)` | `void` | Updates order + inserts history; `$confirmed=true` sets `confirmata=1, confirm_date=NOW()` |
| `repairPhone(string $phone, int $order_id)` | `string` | Normalises phone, updates DB |
| `get(string $key, $default)` | `mixed` | Read `noticeconfirm_*` setting |
| `isEnabled()` | `bool` | Checks `noticeconfirm_status` |
| `log(string $channel, string $message)` | `void` | Appends to `DIR_LOGS/YYYY-MM-DD-noticeconfirm-{channel}.log` |

Settings are loaded once in `__construct()` from `ockg_setting WHERE code = 'noticeconfirm'`.

---

## Database

### `ockg_audio` — created by model `install()`

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK | |
| `order_id` | int | FK to `ockg_order` |
| `audio_id` | int | notice.ro Audio ID (used to match callbacks) |
| `called` | tinyint | 1 = voice call sent |
| `whatsapp` | tinyint | 1 = WhatsApp sent |
| `sms` | tinyint | 1 = SMS sent |
| `text` | text | audio message text |
| `result` | int | see sentinel table below |
| `call_date` | datetime | |
| `whatsapp_date` | datetime | |
| `sms_date` | datetime | |

### `result` values

| Value | Meaning |
|-------|---------|
| `NULL` | No callback received yet |
| `1` | Confirmed (key 1 pressed) |
| `9` | Cancelled (key 9 pressed) |
| `0` | No answer / failed / unknown |
| `-1` | Recall dispatched — awaiting callback (cron sentinel) |

---

## Settings

Stored in `ockg_setting` with `code = 'noticeconfirm'`. All read via `$nc->get('key', default)`.

| Key | Default | Description |
|-----|---------|-------------|
| `status` | 0 | Enable/disable |
| `bearer` | — | notice.ro Bearer token |
| `call_hour_start` | 10 | Call window start hour |
| `call_hour_end` | 21 | Call window end hour |
| `min_age` | 2 | Min minutes after order before first call |
| `delay_wapp` | 15 | Minutes: call → WhatsApp |
| `delay_sms` | 15 | Minutes: WhatsApp → SMS |
| `delay_recall` | 15 | Minutes: SMS → Recall |
| `max_refuzate` | 1 | Skip phone if cancellation count > this |
| `status_pending` | `1,22` | Comma-separated order status IDs that trigger the flow |
| `status_confirmed` | 20 | Status set when confirmed by voice (key 1) |
| `status_cancelled` | 7 | Status set when cancelled by voice (key 9) |
| `status_notify` | 22 | Status used in history entries (notified) |
| `status_refused` | 19 | Refused/not-picked-up status for skip check |
| `callback_url` | auto | notice.ro callback URL (auto-filled from store URL) |

---

## Cron — `system/library/noticeconfirm_cron.php`

Run every 1–5 minutes:

```bash
php -f /path/to/opencart/system/library/noticeconfirm_cron.php
```

Bootstraps the OC registry, then loops through pending orders:

```
For each order WHERE status IN (status_pending) AND confirmata != 1:
  age < min_age                                           → skip
  refusals > max_refuzate                                 → skip
  No audio record + in call window                        → STEP 1: voice call
  called=1, whatsapp=0, result≠1, Δcall ≥ delay_wapp    → STEP 2: WhatsApp
  whatsapp=1, sms=0, Δwapp ≥ delay_sms                  → STEP 3: SMS with link
  sms=1, result∈{NULL,0,9}, Δsms ≥ delay_recall, in window → STEP 4: recall (result=-1)
```

---

## Callback — `catalog/controller/extension/module/noticeconfirm_callback.php`

Route: `extension/module/noticeconfirm_callback/callback`

notice.ro POSTs here when a call ends.

**Payload:**

| Field | Type | Values |
|-------|------|--------|
| `audio_id` | int | notice.ro Audio model ID |
| `status` | string | `confirmed`, `cancelled`, `no_answer`, `no_response`, `failed`, `unknown` |

**Actions:**

| `status` | Result |
|----------|--------|
| `confirmed` | Order → `status_confirmed`, `confirmata=1`, `confirm_date=NOW()` |
| `cancelled` | Order → `status_cancelled` |
| other | `result=0` in `ockg_audio`, no order change |

---

## OC 2.x / 3.x Compatibility

| Concern | Solution |
|---------|----------|
| Admin token | Detects `user_token` (3.x) vs `token` (2.x) in session |
| Templates | Both `.tpl` (2.x) and `.twig` (3.x) provided |
| Settings API | `model_setting_setting->editSetting()` — same in both |
| Installer whitelist | All files within OC3 allowed directories |

---

## Installation

1. Upload `noticeconfirm.ocmod.zip` via **Extensions → Installer**
2. Go to **Extensions → Modifications → Refresh**
3. Go to **Extensions → Extensions → Modules**, find *Notice.ro Order Confirmation* → **Install** → **Edit**
4. Enter bearer token, configure delays and order status mappings → **Save**
5. Add cron:
   ```
   */2 * * * * php -f /var/www/html/system/library/noticeconfirm_cron.php
   ```

---

## Development

```bash
# Syntax check
find upload/ -name "*.php" | xargs php -l

# Rebuild OCMOD zip
zip -r noticeconfirm.ocmod.zip upload/
```

Zip files are in `.gitignore` — build locally, do not commit.
