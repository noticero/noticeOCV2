<?php
$_['heading_title']      = 'Notice.ro Order Confirmation';
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Settings saved successfully.';
$_['text_edit']          = 'Edit Notice.ro Confirmation';
$_['text_enabled']       = 'Enabled';
$_['text_disabled']      = 'Disabled';

// API
$_['entry_status']       = 'Status';
$_['entry_bearer']       = 'Notice.ro Bearer Token';
$_['entry_callback_url'] = 'Callback URL (auto-filled)';

// Timing
$_['entry_call_hour_start'] = 'Call window start (hour)';
$_['entry_call_hour_end']   = 'Call window end (hour)';
$_['entry_min_age']         = 'Min. minutes after order before first call';
$_['entry_delay_wapp']      = 'Delay: Call → WhatsApp (minutes)';
$_['entry_delay_sms']       = 'Delay: WhatsApp → SMS (minutes)';
$_['entry_delay_recall']    = 'Delay: SMS → Recall (minutes)';
$_['entry_max_refuzate']    = 'Max. previous cancellations before skip';

// Order status mapping
$_['entry_status_pending']   = 'Trigger on order status(es)';
$_['entry_status_confirmed'] = 'Set to status when confirmed (voice)';
$_['entry_status_cancelled'] = 'Set to status when cancelled (voice)';
$_['entry_status_notify']    = 'Add history entry with status';
$_['entry_status_refused']   = 'Refused/not-picked-up status (for skip check)';

// Help
$_['help_bearer']          = 'Your Bearer token from api.notice.ro';
$_['help_status_pending']  = 'Comma-separated status IDs, e.g. 1,22';
$_['help_callback_url']    = 'notice.ro will POST to this URL when a call is answered';
$_['help_delay']           = 'minutes';

// Errors
$_['error_permission']   = 'You do not have permission to modify this module.';
$_['error_bearer']       = 'Bearer token is required.';
