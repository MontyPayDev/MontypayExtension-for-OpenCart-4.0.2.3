<?php
namespace Opencart\Admin\Controller\Extension\montypay\Payment;
class montypay extends \Opencart\System\Engine\Controller { //ControllerExtensionPaymentPPStandard
	private $error = array();

	public function index(): void {
		$this->load->language('extension/montypay/payment/montypay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_montypay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['merchant_key'])) {
			$data['error_merchant_key'] = $this->error['merchant_key'];
		} else {
			$data['error_merchant_key'] = '';
		}

		if (isset($this->error['merchant_password'])) {
			$data['error_merchant_password'] = $this->error['merchant_password'];
		} else {
			$data['error_merchant_password'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/montypay/payment/montypay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['save'] = $this->url->link('extension/montypay/payment/montypay.save', 'user_token=' . $this->session->data['user_token'], true);

		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
		$data['payment_montypay_response'] = $this->config->get('payment_montypay_response');
		if (isset($this->request->post['payment_montypay_email'])) {
			$data['payment_montypay_email'] = $this->request->post['payment_montypay_email'];
		} else {
			$data['payment_montypay_email'] = $this->config->get('payment_montypay_email');
		}

		if (isset($this->request->post['payment_montypay_merchant_key'])) {
			$data['payment_montypay_merchant_key'] = $this->request->post['payment_montypay_merchant_key'];
		} else {
			$data['payment_montypay_merchant_key'] = $this->config->get('payment_montypay_merchant_key');
		}

		if (isset($this->request->post['payment_montypay_merchant_password'])) {
			$data['payment_montypay_merchant_password'] = $this->request->post['payment_montypay_merchant_password'];
		} else {
			$data['payment_montypay_merchant_password'] = $this->config->get('payment_montypay_merchant_password');
		}

		if (isset($this->request->post['payment_montypay_method'])) {
			file_put_contents('./log_3.log', json_encode($this->request->post['payment_montypay_method']), FILE_APPEND);
			$data['payment_montypay_method'] = $this->request->post['payment_montypay_method'];
		} else {
			$data['payment_montypay_method'] = $this->config->get('payment_montypay_method');
		}

		if (isset($this->request->post['payment_montypay_canceled_reversal_status_id'])) {
			$data['payment_montypay_canceled_reversal_status_id'] = $this->request->post['payment_montypay_canceled_reversal_status_id'];
		} else {
			$data['payment_montypay_canceled_reversal_status_id'] = $this->config->get('payment_montypay_canceled_reversal_status_id');
		}

		if (isset($this->request->post['payment_montypay_completed_status_id'])) {
			$data['payment_montypay_completed_status_id'] = $this->request->post['payment_montypay_completed_status_id'];
		} else {
			$data['payment_montypay_completed_status_id'] = $this->config->get('payment_montypay_completed_status_id');
		}

		if (isset($this->request->post['payment_montypay_denied_status_id'])) {
			$data['payment_montypay_denied_status_id'] = $this->request->post['payment_montypay_denied_status_id'];
		} else {
			$data['payment_montypay_denied_status_id'] = $this->config->get('payment_montypay_denied_status_id');
		}

		if (isset($this->request->post['payment_montypay_expired_status_id'])) {
			$data['payment_montypay_expired_status_id'] = $this->request->post['payment_montypay_expired_status_id'];
		} else {
			$data['payment_montypay_expired_status_id'] = $this->config->get('payment_montypay_expired_status_id');
		}

		if (isset($this->request->post['payment_montypay_failed_status_id'])) {
			$data['payment_montypay_failed_status_id'] = $this->request->post['payment_montypay_failed_status_id'];
		} else {
			$data['payment_montypay_failed_status_id'] = $this->config->get('payment_montypay_failed_status_id');
		}

		if (isset($this->request->post['payment_montypay_pending_status_id'])) {
			$data['payment_montypay_pending_status_id'] = $this->request->post['payment_montypay_pending_status_id'];
		} else {
			$data['payment_montypay_pending_status_id'] = $this->config->get('payment_montypay_pending_status_id');
		}

		if (isset($this->request->post['payment_montypay_processed_status_id'])) {
			$data['payment_montypay_processed_status_id'] = $this->request->post['payment_montypay_processed_status_id'];
		} else {
			$data['payment_montypay_processed_status_id'] = $this->config->get('payment_montypay_processed_status_id');
		}

		if (isset($this->request->post['payment_montypay_refunded_status_id'])) {
			$data['payment_montypay_refunded_status_id'] = $this->request->post['payment_montypay_refunded_status_id'];
		} else {
			$data['payment_montypay_refunded_status_id'] = $this->config->get('payment_montypay_refunded_status_id');
		}

		if (isset($this->request->post['payment_montypay_reversed_status_id'])) {
			$data['payment_montypay_reversed_status_id'] = $this->request->post['payment_montypay_reversed_status_id'];
		} else {
			$data['payment_montypay_reversed_status_id'] = $this->config->get('payment_montypay_reversed_status_id');
		}

		if (isset($this->request->post['payment_montypay_voided_status_id'])) {
			$data['payment_montypay_voided_status_id'] = $this->request->post['payment_montypay_voided_status_id'];
		} else {
			$data['payment_montypay_voided_status_id'] = $this->config->get('payment_montypay_voided_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['methods'] = array('card', 'applepay');


		if (isset($this->request->post['payment_montypay_status'])) {
			$data['payment_montypay_status'] = $this->request->post['payment_montypay_status'];
		} else {
			$data['payment_montypay_status'] = $this->config->get('payment_montypay_status');
		}

		if (isset($this->request->post['payment_montypay_sort_order'])) {
			$data['payment_montypay_sort_order'] = $this->request->post['payment_montypay_sort_order'];
		} else {
			$data['payment_montypay_sort_order'] = $this->config->get('payment_montypay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$str=

		$data['notification_url'] = str_replace('/admin', '',$this->url->link('extension/montypay/payment/montypay.callback', '', true));// $this->url->link('extension/payment/montypay/callback', '', true);
		
		$this->response->setOutput($this->load->view('extension/montypay/payment/montypay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/montypay/payment/montypay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_montypay_email']) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (!$this->request->post['payment_montypay_merchant_key']) {
			$this->error['merchant_key'] = $this->language->get('error_merchant_key');
		}

		if (!$this->request->post['payment_montypay_merchant_password']) {
			$this->error['merchant_password'] = $this->language->get('error_merchant_password');
		}

		return !$this->error;
	}

	public function save(): void {
		$this->load->language('extension/montypay/payment/montypay');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/montypay/payment/montypay')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_montypay', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}

	public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/payment')) {
			$this->load->model('extension/montypay/payment/montypay');

			$this->model_extension_montypay_payment_montypay->install();
		}
	}
	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'extension/payment')) {
			$this->load->model('extension/montypay/payment/montypay');

			$this->model_extension_montypay_payment_montypay->uninstall();
		}
	}

}