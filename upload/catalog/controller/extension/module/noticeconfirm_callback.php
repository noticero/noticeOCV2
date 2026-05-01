<?php
// result values in ockg_audio.result:
//   1  = confirmed (key 1)
//   9  = cancelled (key 9)
//   0  = no_answer / no_response / failed / unknown
//  -1  = recall in progress (cron sentinel, overwritten here)
// Route: extension/module/noticeconfirm_callback/callback

class ControllerExtensionModuleNoticeconfirmCallback extends Controller {

    public function callback() {
        $this->load->library('noticeconfirm');

        $nc       = new NoticeConfirm($this->registry);
        $audio_id = isset($this->request->post['audio_id']) ? (int)$this->request->post['audio_id'] : 0;
        $status   = isset($this->request->post['status'])   ? (string)$this->request->post['status'] : '';

        if (!$audio_id || !$status) {
            $this->jsonResponse(['error' => true, 'message' => 'missing params']);
            return;
        }

        $audio = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "audio`
            WHERE audio_id = '" . $audio_id . "' LIMIT 1
        ");

        if (!$audio->num_rows) {
            $this->jsonResponse(['error' => true, 'message' => 'not found']);
            return;
        }

        $rec      = $audio->row;
        $order_id = (int)$rec['order_id'];

        $result = match ($status) {
            'confirmed' => 1,
            'cancelled' => 9,
            default     => 0,
        };

        $this->db->query("
            UPDATE `" . DB_PREFIX . "audio`
            SET result = '" . $result . "'
            WHERE id   = '" . (int)$rec['id'] . "'
        ");

        $status_confirmed = (int)$nc->get('status_confirmed', 20);
        $status_cancelled = (int)$nc->get('status_cancelled', 7);

        if ($status === 'confirmed') {
            $nc->addOrderHistory($order_id, $status_confirmed, 'Comanda confirmata prin apel vocal', true);
            $nc->log('callback', 'Order ' . $order_id . ' confirmed by voice (audio_id=' . $audio_id . ')');
        } elseif ($status === 'cancelled') {
            $nc->addOrderHistory($order_id, $status_cancelled, 'Comanda anulata de client prin apel vocal');
            $nc->log('callback', 'Order ' . $order_id . ' cancelled by voice (audio_id=' . $audio_id . ')');
        }

        $this->jsonResponse(['status' => 'ok']);
    }

    private function jsonResponse(array $data): void {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }
}
