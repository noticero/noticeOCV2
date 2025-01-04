<?php

class SMSNotification
{
    private $registry;
    private $api_endpoint = 'https://notice-api.test/api/v1';

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function getSMSTemplates($api_key = null, $template_id = null)
    {
        if (!$api_key) {
            return [];
        }

        $api_key = $this->registry->get('config')->get('sms_notification_api_key');

        if (!$api_key) {
            return [];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            // Ignore SSL Certificate errors
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $this->api_endpoint . '/templates' . ($template_id ? '/' . $template_id : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $api_key,
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['error' => $err];
        }

        return json_decode($response, true);
    }

    public function sendSMS($payload)
    {
        $api_key = $this->registry->get('config')->get('sms_notification_api_key');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_endpoint .'/sms-out',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $api_key,
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            $this->log('SMS Send Error: ' . $err);
            return false;
        }

        if ($httpCode !== 200) {
            $this->log('SMS Send Failed. Response: ' . $response);
            return false;
        }

        $this->log('SMS Send Failed. Response: ' . json_encode($response));

        return true;
    }

    public function handleWebhook($data)
    {
        // Log incoming webhook data
        $this->log('SMS Webhook Received: ' . json_encode($data));

        // Process different webhook events
        switch ($data['event']) {
            case 'delivery_status':
                $this->processDeliveryStatus($data);
                break;
            case 'reply':
                $this->processCustomerReply($data);
                break;
        }
    }

    private function processDeliveryStatus($data)
    {
        $this->log('SMS Delivery Status: ' . json_encode($data));
    }

    private function processCustomerReply($data)
    {
        $this->log('Customer SMS Reply: ' . json_encode($data));
    }

    private function log($message)
    {
        $log = new Log('sms_notification.log');
        $log->write($message);
    }
}
