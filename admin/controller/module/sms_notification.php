<?php
class ControllerModuleSMSNotification extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('module/sms_notification');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('sms_notification', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_webhook'] = $this->language->get('text_webhook');
        $data['text_template_text'] = $this->language->get('text_template_text');
        $data['text_change_order_status'] = $this->language->get('text_change_order_status');
        $data['text_change_order_status_text'] = $this->language->get('text_change_order_status_text');
        $data['text_change_order_status_canceled'] = $this->language->get('text_change_order_status_canceled');
        $data['text_activate_trigger'] = $this->language->get('text_activate_trigger');
        $data['text_cancel_trigger'] = $this->language->get('text_cancel_trigger');
        $data['text_order_status_text'] = $this->language->get('text_order_status_text');
        $data['entry_api_key'] = $this->language->get('entry_api_key');
        $data['entry_template'] = $this->language->get('entry_template');
        $data['token'] = $this->session->data['token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('module/sms_notification', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['action'] = $this->url->link('module/sms_notification', 'token=' . $this->session->data['token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

        // API Key
        if (isset($this->request->post['sms_notification_api_key'])) {
            $data['sms_notification_api_key'] = $this->request->post['sms_notification_api_key'];
        } else {
            $data['sms_notification_api_key'] = $this->config->get('sms_notification_api_key');
        }

        // SMS Template name
        if (isset($this->request->post['sms_notification_template'])) {
            $data['sms_notification_template'] = $this->request->post['sms_notification_template'];
        } else {
            $data['sms_notification_template'] = $this->config->get('sms_notification_template');
        }

        // SMS Template text
        if (isset($this->request->post['sms_notification_template'])) {
            $data['sms_notification_template_text'] = $this->request->post['sms_notification_template_text'];
        } else {
            $data['sms_notification_template_text'] = $this->config->get('sms_notification_template_text');
        }

         // SMS Order Status
         if (isset($this->request->post['sms_notification_order_status'])) {
            $data['sms_notification_order_status'] = $this->request->post['sms_notification_order_status'];
        } else {
            $data['sms_notification_order_status'] = $this->config->get('sms_notification_order_status');
        }

         // SMS Order Status
         if (isset($this->request->post['sms_notification_order_status_cancel'])) {
            $data['sms_notification_order_status_cancel'] = $this->request->post['sms_notification_order_status_cancel'];
        } else {
            $data['sms_notification_order_status_cancel'] = $this->config->get('sms_notification_order_status_cancel');
        }

         // SMS Order Status Cancel
         if (isset($this->request->post['sms_notification_order_status_cancel'])) {
            $data['sms_notification_order_status_cancel'] = $this->request->post['sms_notification_order_status_cancel'];
        } else {
            $data['sms_notification_order_status_cancel'] = $this->config->get('sms_notification_order_status_cancel');
        }

         // SMS Order Trigger Keywords
         if (isset($this->request->post['sms_notification_trigger_text'])) {
            $data['sms_notification_trigger_text'] = $this->request->post['sms_notification_trigger_text'];
        } else {
            $data['sms_notification_trigger_text'] = $this->config->get('sms_notification_trigger_text');
        }
        $data['sms_notification_trigger_activate'] = null;
        // SMS Order Trigger active
        if (isset($this->request->post['sms_notification_trigger_activate'])) {
            $data['sms_notification_trigger_activate'] = $this->request->post['sms_notification_trigger_activate'];
        } else {
            $data['sms_notification_trigger_activate'] = $this->config->get('sms_notification_trigger_activate');
        }

        // SMS Order Trigger cancel
        if (isset($this->request->post['sms_notification_trigger_cancel'])) {
            $data['sms_notification_trigger_cancel'] = $this->request->post['sms_notification_trigger_cancel'];
        } else {
            $data['sms_notification_trigger_cancel'] = $this->config->get('sms_notification_trigger_cancel');
        }

        // SMS Order Trigger cancel
        if (isset($this->request->post['sms_notification_trigger_cancel_text'])) {
            $data['sms_notification_trigger_cancel_text'] = $this->request->post['sms_notification_trigger_cancel_text'];
        } else {
            $data['sms_notification_trigger_cancel_text'] = $this->config->get('sms_notification_trigger_cancel_text');
        }

        // Fetch SMS Templates
        $this->load->library('smsnotification');
        $data['sms_templates'] = $this->smsnotification->getSMSTemplates(
            $data['sms_notification_api_key']
        );

        //get all order statuses
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('module/sms_notification.tpl', $data));
    }

    public function getTemplatesText()
    {
        $this->load->library('smsnotification');
        $templates = $this->smsnotification->getSMSTemplates(
            $this->config->get('sms_notification_api_key'),
            $this->request->post['template_id']
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($templates));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/sms_notification')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
