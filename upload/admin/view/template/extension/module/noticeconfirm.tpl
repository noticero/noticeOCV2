<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-noticeconfirm" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
        <a href="<?php echo $cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i> Cancel</a>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $bc) { ?>
        <li><a href="<?php echo $bc['href']; ?>"><?php echo $bc['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>

  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?></div>
    <?php } ?>
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?></div>
    <?php } ?>

    <form action="<?php echo $action; ?>" method="post" id="form-noticeconfirm" class="form-horizontal">
      <!-- API Settings -->
      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-phone"></i> API Settings</h3></div>
        <div class="panel-body">

          <div class="form-group">
            <label class="col-sm-2 control-label">Status</label>
            <div class="col-sm-10">
              <select name="noticeconfirm_status" class="form-control">
                <option value="1"<?php if ($noticeconfirm_status == '1') echo ' selected'; ?>>Enabled</option>
                <option value="0"<?php if ($noticeconfirm_status == '0') echo ' selected'; ?>>Disabled</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Bearer Token</label>
            <div class="col-sm-10">
              <input type="password" name="noticeconfirm_bearer" value="<?php echo htmlspecialchars($noticeconfirm_bearer); ?>" class="form-control" placeholder="notice.ro bearer token" />
              <span class="help-block">From your api.notice.ro account</span>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Callback URL</label>
            <div class="col-sm-10">
              <input type="text" name="noticeconfirm_callback_url" value="<?php echo htmlspecialchars($noticeconfirm_callback_url); ?>" class="form-control" readonly />
              <span class="help-block">notice.ro POSTs here when a call is answered</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Timing -->
      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-clock-o"></i> Timing</h3></div>
        <div class="panel-body">

          <div class="form-group">
            <label class="col-sm-2 control-label">Call window</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="number" name="noticeconfirm_call_hour_start" value="<?php echo (int)$noticeconfirm_call_hour_start; ?>" class="form-control" min="0" max="23" />
                <span class="input-group-addon">h</span>
              </div>
            </div>
            <label class="col-sm-1 control-label" style="text-align:center">–</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="number" name="noticeconfirm_call_hour_end" value="<?php echo (int)$noticeconfirm_call_hour_end; ?>" class="form-control" min="0" max="23" />
                <span class="input-group-addon">h</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Min. order age before first call</label>
            <div class="col-sm-3">
              <div class="input-group">
                <input type="number" name="noticeconfirm_min_age" value="<?php echo (int)$noticeconfirm_min_age; ?>" class="form-control" min="1" />
                <span class="input-group-addon">min</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Call → WhatsApp delay</label>
            <div class="col-sm-3">
              <div class="input-group">
                <input type="number" name="noticeconfirm_delay_wapp" value="<?php echo (int)$noticeconfirm_delay_wapp; ?>" class="form-control" min="1" />
                <span class="input-group-addon">min</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">WhatsApp → SMS delay</label>
            <div class="col-sm-3">
              <div class="input-group">
                <input type="number" name="noticeconfirm_delay_sms" value="<?php echo (int)$noticeconfirm_delay_sms; ?>" class="form-control" min="1" />
                <span class="input-group-addon">min</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">SMS → Recall delay</label>
            <div class="col-sm-3">
              <div class="input-group">
                <input type="number" name="noticeconfirm_delay_recall" value="<?php echo (int)$noticeconfirm_delay_recall; ?>" class="form-control" min="1" />
                <span class="input-group-addon">min</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Skip if previous cancellations ></label>
            <div class="col-sm-3">
              <input type="number" name="noticeconfirm_max_refuzate" value="<?php echo (int)$noticeconfirm_max_refuzate; ?>" class="form-control" min="0" />
            </div>
          </div>

        </div>
      </div>

      <!-- Order Status Mapping -->
      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list-alt"></i> Order Status Mapping</h3></div>
        <div class="panel-body">

          <div class="form-group">
            <label class="col-sm-2 control-label">Trigger on status(es)</label>
            <div class="col-sm-10">
              <select name="noticeconfirm_status_pending[]" class="form-control" multiple size="6">
                <?php $pending_ids = array_map('trim', explode(',', $noticeconfirm_status_pending)); ?>
                <?php foreach ($order_statuses as $os) { ?>
                <option value="<?php echo $os['order_status_id']; ?>"<?php if (in_array($os['order_status_id'], $pending_ids)) echo ' selected'; ?>><?php echo htmlspecialchars($os['name']); ?> (<?php echo $os['order_status_id']; ?>)</option>
                <?php } ?>
              </select>
              <span class="help-block">Hold Ctrl/Cmd to select multiple. Orders in these statuses will enter the confirmation flow.</span>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Confirmed status (voice key 1)</label>
            <div class="col-sm-4">
              <select name="noticeconfirm_status_confirmed" class="form-control">
                <?php foreach ($order_statuses as $os) { ?>
                <option value="<?php echo $os['order_status_id']; ?>"<?php if ($os['order_status_id'] == $noticeconfirm_status_confirmed) echo ' selected'; ?>><?php echo htmlspecialchars($os['name']); ?> (<?php echo $os['order_status_id']; ?>)</option>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Cancelled status (voice key 9)</label>
            <div class="col-sm-4">
              <select name="noticeconfirm_status_cancelled" class="form-control">
                <?php foreach ($order_statuses as $os) { ?>
                <option value="<?php echo $os['order_status_id']; ?>"<?php if ($os['order_status_id'] == $noticeconfirm_status_cancelled) echo ' selected'; ?>><?php echo htmlspecialchars($os['name']); ?> (<?php echo $os['order_status_id']; ?>)</option>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">History/notify status</label>
            <div class="col-sm-4">
              <select name="noticeconfirm_status_notify" class="form-control">
                <?php foreach ($order_statuses as $os) { ?>
                <option value="<?php echo $os['order_status_id']; ?>"<?php if ($os['order_status_id'] == $noticeconfirm_status_notify) echo ' selected'; ?>><?php echo htmlspecialchars($os['name']); ?> (<?php echo $os['order_status_id']; ?>)</option>
                <?php } ?>
              </select>
              <span class="help-block">Status used when logging "notified via call/WhatsApp/SMS" in order history.</span>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Refused/not-picked-up status</label>
            <div class="col-sm-4">
              <select name="noticeconfirm_status_refused" class="form-control">
                <?php foreach ($order_statuses as $os) { ?>
                <option value="<?php echo $os['order_status_id']; ?>"<?php if ($os['order_status_id'] == $noticeconfirm_status_refused) echo ' selected'; ?>><?php echo htmlspecialchars($os['name']); ?> (<?php echo $os['order_status_id']; ?>)</option>
                <?php } ?>
              </select>
              <span class="help-block">Orders from this phone number with this status will be skipped if count > max above.</span>
            </div>
          </div>

        </div>
      </div>

    </form>
  </div>
</div>
<?php echo $footer; ?>
