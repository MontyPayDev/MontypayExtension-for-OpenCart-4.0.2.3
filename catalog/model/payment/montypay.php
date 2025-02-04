<?php
namespace Opencart\Catalog\Model\Extension\montypay\Payment;
class montypay extends \Opencart\System\Engine\Model {
	public function getMethods(array $address): array {
		$this->load->language('extension/montypay/payment/montypay');

		if (!$this->config->get('config_checkout_payment_address')) {
			$status = true;
		} elseif (!$this->config->get('payment_montypay_geo_zone_id')) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_montypay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

			if ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		}

		$method_data = [];

		if ($status) {
			$option_data = [];

			$option_data['montypay'] = [
				'code' => 'montypay.montypay',
				'name' => $this->language->get('text_card_use')
			];

			$results = $this->getCreditCards($this->customer->getId());

			foreach ($results as $result) {
				$option_data[$result['credit_card_id']] = [
					'code' => 'montypay.' . $result['credit_card_id'],
					'name' => $this->language->get('text_card_use') . ' ' . $result['card_number']
				];
			}

			$method_data = [
				'code'       => 'montypay',
				'name'       => $this->language->get('text_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_montypay_sort_order')
			];
		}

		return $method_data;
	}

	public function getCreditCard(int $customer_id, int $credit_card_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "montypay` WHERE `customer_id` = '" . (int)$customer_id . "' AND `credit_card_id` = '" . (int)$credit_card_id . "'");

		return $query->row;
	}

	public function getCreditCards(int $customer_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "montypay` WHERE `customer_id` = '" . (int)$customer_id . "'");

		return $query->rows;
	}

	public function addCreditCard(int $customer_id, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "montypay` SET `customer_id` = '" . (int)$customer_id . "', `card_name` = '" . $this->db->escape($data['card_name']) . "', `card_number` = '" . $this->db->escape($data['card_number']) . "', `card_expire_month` = '" . $this->db->escape($data['card_expire_month']) . "', `card_expire_year` = '" . $this->db->escape($data['card_expire_year']) . "', `card_cvv` = '" . $this->db->escape($data['card_cvv']) . "', `date_added` = NOW()");
	}

	public function deleteCreditCard(int $customer_id, int $credit_card_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "montypay` WHERE `customer_id` = '" . (int)$customer_id . "' AND `credit_card_id` = '" . (int)$credit_card_id . "'");
	}

	public function charge(int $customer_id, int $order_id, float $amount, int $credit_card_id = 0): string {
		//$this->db->query("INSERT INTO `" . DB_PREFIX . "credit_card` SET `customer_id` = '" . (int)$customer_id . "', `card_name` = '" . $this->db->escape($data['card_name']) . "', `card_number` = '" . $this->db->escape($data['card_number']) . "', `card_expire_month` = '" . $this->db->escape($data['card_expire_month']) . "', `card_expire_year` = '" . $this->db->escape($data['card_expire_year']) . "', `card_cvv` = '" . $this->db->escape($data['card_cvv']) . "', `date_added` = NOW()");

		return $this->config->get('payment_montypay_response');
	}
}
