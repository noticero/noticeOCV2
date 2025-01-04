<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-module" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-module" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-api-key"><?php echo $entry_api_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="sms_notification_api_key" value="<?php echo $sms_notification_api_key; ?>" placeholder="Enter SMS API Key" id="input-api-key" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-template"><?php echo $entry_template; ?></label>
                        <div class="col-sm-10">
                            <select name="sms_notification_template" id="input-template" class="form-control">
                                <?php foreach ($sms_templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>"
                                    <?php echo ($sms_notification_template == $template['id']) ? 'selected' : ''; ?>>
                                    <?php echo $template['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_template_text; ?></label>
                        <div class="col-sm-10">
                            <input id="template-text" name="sms_notification_template_text" type="text" readonly class="form-control" value="<?php echo $sms_notification_template_text; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Webhook URL</label>
                        <div class="col-sm-10">
                            <input type="text" readonly class="form-control"
                                   value="<?php echo HTTPS_CATALOG; ?>index.php?route=module/sms_notification/webhook" />
                            <small><?php echo $text_webhook; ?></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_activate_trigger; ?></label>
                        <div class="col-sm-10">
                            <select name="sms_notification_trigger_activate" class="form-control">
                                <option value="0" <?php echo ($sms_notification_trigger_activate) == 0 ? 'selected' : ''; ?>>Nu</option>
                                <option value="1" <?php echo ($sms_notification_trigger_activate) == 1 ? 'selected' : ''; ?>>Da</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_change_order_status; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="sms_notification_trigger_text" class="form-control" value="<?php echo $sms_notification_trigger_text; ?>"/>
                            <small><?php echo $text_change_order_status_text; ?></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_order_status_text; ?></label>
                        <div class="col-sm-10">
                            <select name="sms_notification_order_status" class="form-control">
                                <?php foreach ($order_statuses as $status): ?>
                                <option value="<?php echo $status['order_status_id']; ?>"
                                    <?php echo ($sms_notification_order_status == $status['order_status_id']) ? 'selected' : ''; ?>>
                                    <?php echo $status['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_cancel_trigger; ?></label>
                        <div class="col-sm-10">
                            <select name="sms_notification_trigger_cancel" class="form-control">
                                <option value="0" <?php echo ($sms_notification_trigger_cancel) == 0 ? 'selected' : ''; ?>>Nu</option>
                                <option value="1" <?php echo ($sms_notification_trigger_cancel) == 1 ? 'selected' : ''; ?>>Da</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_change_order_status_canceled; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="sms_notification_trigger_cancel_text" class="form-control" value="<?php echo $sms_notification_trigger_cancel_text; ?>"/>
                            <small><?php echo $text_change_order_status_text; ?></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $text_order_status_text; ?></label>
                        <div class="col-sm-10">
                            <select name="sms_notification_order_status_cancel" class="form-control">
                                <?php foreach ($order_statuses as $status): ?>
                                <option value="<?php echo $status['order_status_id']; ?>"
                                    <?php echo ($sms_notification_order_status_cancel == $status['order_status_id']) ? 'selected' : ''; ?>>
                                    <?php echo $status['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#input-template').on('change', function () {
            var templateId = $(this).val();
            $.ajax({
                url: 'index.php?route=module/sms_notification/getTemplatesText&token=<?php echo $token; ?>',
                type: 'post',
                data: {template_id: templateId},
                dataType: 'json',
                success: function (json) {
                    $('#template-text').val(json['text']);
                }
            });
        });
    });
</script>
<?php echo $footer; ?>