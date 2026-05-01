<?php
class ModelExtensionModuleNoticeconfirm extends Model {

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "audio` (
                `id`             int(11)      NOT NULL AUTO_INCREMENT,
                `order_id`       int(11)      NOT NULL,
                `audio_id`       int(11)      DEFAULT 0,
                `called`         tinyint(4)   DEFAULT 0,
                `sms`            tinyint(4)   DEFAULT 0,
                `whatsapp`       tinyint(4)   DEFAULT 0,
                `text`           text,
                `result`         int(11)      DEFAULT NULL,
                `call_date`      datetime     DEFAULT NULL,
                `sms_date`       datetime     DEFAULT NULL,
                `whatsapp_date`  datetime     DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "audio`");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'noticeconfirm'");
    }
}
