<?php
class ControllerModuleSMSNotification extends Controller
{
    public function afterOrderPlaced($order_id)
    {
        $this->load->library('smsnotification');
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        // Send SMS only if phone number is available
        if (!empty($order_info['telephone'])) {
            $payload = [
                'number' => $order_info['telephone'],
                'template_id' => $this->config->get('sms_notification_template'),
                'variables' => [
                    'order_id' => $order_id,
                    'name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
                    'total' => intval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
                    'site' => $this->config->get('config_meta_title'),
                ]
            ];

            $this->smsnotification->sendSMS($payload);
        }
    }

    public function webhook()
    {
        // Basic webhook handling
        $api_key = $this->config->get('sms_notification_api_key');

        // Verify API key
        if (!isset($this->request->server['HTTP_X_API_KEY']) ||
            $this->request->server['HTTP_X_API_KEY'] !== $api_key) {
            http_response_code(401);
            exit('Unauthorized');
        }

        $webhook_data = $this->request->post;

        $this->load->library('smsnotification');

        $this->smsnotification->handleWebhook($webhook_data);

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit();
    }
}
