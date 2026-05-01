<?php
class ControllerExtensionModuleNoticeconfirm extends Controller {

    private $error = [];

    public function index() {
        $this->load->language('extension/module/noticeconfirm');
        $this->load->model('extension/module/noticeconfirm');
        $this->load->model('localisation/order_status');
        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $post = $this->request->post;
            if (isset($post['noticeconfirm_status_pending']) && is_array($post['noticeconfirm_status_pending'])) {
                $post['noticeconfirm_status_pending'] = implode(',', $post['noticeconfirm_status_pending']);
            }
            $this->model_setting_setting->editSetting('noticeconfirm', $post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link(
                'extension/module/noticeconfirm',
                $this->tokenParam(),
                true
            ));
        }

        $data = $this->buildFormData();
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/noticeconfirm', $data));
    }

    // AJAX endpoint: returns notice.ro templates as JSON
    public function templates() {
        $this->load->library('noticeconfirm');
        $nc = new NoticeConfirm($this->registry);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($nc->getTemplates()));
    }

    public function install() {
        $this->load->model('extension/module/noticeconfirm');
        $this->model_extension_module_noticeconfirm->install();
    }

    public function uninstall() {
        $this->load->model('extension/module/noticeconfirm');
        $this->model_extension_module_noticeconfirm->uninstall();
    }

    private function buildFormData(): array {
        $this->load->model('localisation/order_status');
        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();

        $token_param = $this->tokenParam();

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'),      'href' => $this->url->link('common/dashboard',          $token_param, true)],
            ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('extension/extension',        $token_param . '&type=module', true)],
            ['text' => $this->language->get('heading_title'),  'href' => $this->url->link('extension/module/noticeconfirm', $token_param, true)],
        ];

        $data['action']          = $this->url->link('extension/module/noticeconfirm', $token_param, true);
        $data['action_templates'] = $this->url->link('extension/module/noticeconfirm/templates', $token_param, true);
        $data['cancel']          = $this->url->link('extension/extension', $token_param . '&type=module', true);

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['success']       = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $fields = [
            'status'           => 0,
            'bearer'           => '',
            'call_hour_start'  => 10,
            'call_hour_end'    => 21,
            'min_age'          => 2,
            'delay_wapp'       => 15,
            'delay_sms'        => 15,
            'delay_recall'     => 15,
            'max_refuzate'     => 1,
            'status_pending'   => '1,22',
            'status_confirmed' => 20,
            'status_cancelled' => 7,
            'status_notify'    => 22,
            'status_refused'   => 19,
            'callback_url'     => '',
            'tpl_call'         => '',
            'tpl_wapp'         => '',
            'tpl_sms'          => '',
            'tpl_recall'       => '',
        ];

        foreach ($fields as $key => $default) {
            $post_key = 'noticeconfirm_' . $key;
            if (isset($this->request->post[$post_key])) {
                $data[$post_key] = $this->request->post[$post_key];
            } else {
                $data[$post_key] = $this->config->get($post_key) !== null
                    ? $this->config->get($post_key)
                    : $default;
            }
        }

        if (is_array($data['noticeconfirm_status_pending'])) {
            $data['noticeconfirm_status_pending'] = implode(',', $data['noticeconfirm_status_pending']);
        }

        $data['order_statuses'] = $order_statuses;

        if (empty($data['noticeconfirm_callback_url'])) {
            $store_url = rtrim((string)$this->config->get('config_url'), '/');
            $data['noticeconfirm_callback_url'] = $store_url . '/index.php?route=extension/module/noticeconfirm_callback/callback';
        }

        return $data;
    }

    private function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/module/noticeconfirm')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (empty($this->request->post['noticeconfirm_bearer'])) {
            $this->error['warning'] = $this->language->get('error_bearer');
        }
        return !$this->error;
    }

    private function tokenParam(): string {
        if (isset($this->session->data['user_token'])) {
            return 'user_token=' . $this->session->data['user_token'];
        }
        return 'token=' . $this->session->data['token'];
    }
}
