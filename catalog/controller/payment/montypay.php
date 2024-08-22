<?php
namespace Opencart\Catalog\Controller\Extension\montypay\Payment;
class montypay extends \Opencart\System\Engine\Controller {
	private $red_url;
	public $currencies_3dotexponent = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];
    public $currencies_noexponent = [
        //'CLP', 
        'VND', 
        'ISK', 
        'UGX', 
        //'KRW', 
        //'JPY'
    ];
	function get_hash($formula, $order_id, $amount, $currency, $payer_email, $description){

		$merchant_password = $this->config->get('payment_montypay_merchant_password');
		
		$str_to_hash = $order_id . $amount . $currency . $description . $merchant_password;
		$hash = sha1(md5(strtoupper($str_to_hash)));    
		
		return $hash;
	}
	

	public function callback(): void {
	    file_put_contents('./log_callback.log', json_encode($_POST), FILE_APPEND);

		if (isset($this->request->post['order_number'])) {
			$order_id = $this->request->post['order_number'];
		} else {
			$order_id = 0;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);
		if ($order_info) {

				$order_status_id = $this->config->get('config_order_status_id');

				switch($this->request->post['status']) {
					case 'Canceled_Reversal':
						$order_status_id = $this->config->get('payment_montypay_canceled_reversal_status_id');
						break;
					case 'success':
						$total_paid_match = ((float)$this->request->post['order_amount'] == $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false));

						if ($total_paid_match) {
							$order_status_id = $this->config->get('payment_montypay_processed_status_id');
						}
						
						if (!$total_paid_match) {
							$this->log->write('montypay :: TOTAL PAID MISMATCH! ' . $this->request->post['order_amount']);
						}
						break;
					case 'Denied':
						$order_status_id = $this->config->get('payment_montypay_denied_status_id');
						break;
					case 'Expired':
						$order_status_id = $this->config->get('payment_montypay_expired_status_id');
						break;
					case 'failed':
						$order_status_id = $this->config->get('payment_montypay_failed_status_id');
						break;
					case 'Pending':
						$order_status_id = $this->config->get('payment_montypay_pending_status_id');
						break;
					case 'Processed':
						$order_status_id = $this->config->get('payment_montypay_processed_status_id');
						break;
					case 'Refunded':
						$order_status_id = $this->config->get('payment_montypay_refunded_status_id');
						break;
					case 'Reversed':
						$order_status_id = $this->config->get('payment_montypay_reversed_status_id');
						break;
					case 'Voided':
						$order_status_id = $this->config->get('payment_montypay_voided_status_id');
						break;
				}

				$this->model_checkout_order->addHistory($order_id, $order_status_id,'',true);
		}
	}

	public function index(): string {
		$this->load->language('extension/montypay/payment/montypay');

		if (isset($this->session->data['payment_method'])) {
			$data['logged'] = $this->customer->isLogged();
			$data['subscription'] = $this->cart->hasSubscription();

			$data['months'] = [];
			
			
			foreach (range(1, 12) as $month) {
				$data['months'][] = date('m', mktime(0, 0, 0, $month, 1));
			}

			$data['years'] = [];

			foreach (range(date('Y'), date('Y', strtotime('+10 year'))) as $year) {
				$data['years'][] = $year;
			}

			$data['language'] = $this->config->get('config_language');
		}


		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		// print_r($order_info);
		// exit;

		$total = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);


		########################################MontyPay########################################

		$order_id = $this->session->data['order_id'];
		$first_name = html_entity_decode($order_info['firstname'], ENT_QUOTES, 'UTF-8');
		$last_name = html_entity_decode($order_info['lastname'], ENT_QUOTES, 'UTF-8');
		$country = $order_info['payment_iso_code_2'];
		$city = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
		$address = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
		$zip = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
		$description = 'Payment Order # ' . $order_id .' in the store ';
		$currency = $order_info['currency_code'];
		$total = $order_info['total'];

		if (in_array($currency, $this->currencies_noexponent)) {
			$amount = number_format($total, 0, '.', '');
		}elseif (in_array($currency, $this->currencies_3dotexponent)) {
			$amount = number_format($total, 3, '.', '');
		}else{
			$amount = number_format($total, 2, '.', '');
		}
		
		$customer = array(
			'name' => $first_name . ' ' . $last_name,
			'email' => $order_info['email'],
		);

		$billing_address = array(
			'country' => $country ? $country : 'NA',
			'city' => $city ? $city : 'NA',
			'address' => $address ? $address : 'NA',
			'zip' => $zip ? $zip : 'NA',
			// 'phone' => $order->get_billing_phone() ? $order->get_billing_phone() : 'NA',
		);

		$order_json = array(
			'number' => "$order_id",
			'description' => $description,
			'amount' => $amount, //may troubles
			'currency' => $currency,
		);
		
		$methods = $this->config->get('payment_montypay_method');


		$request_data = [
			'merchant_key' => $this->config->get('payment_montypay_merchant_key'),
			'operation'    => 'purchase', //m subs purchase
			'order'        => $order_json,
			'customer'     => $customer,
			'billing_address' => $billing_address,
			'success_url' => $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true),
			'cancel_url'   => $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true), //
			'hash'         => $this->get_hash('F_hosted', $order_id, $amount, $currency, $customer['email'], $description),
		];
		$fields  = json_encode($request_data);

		$getter = curl_init('https://checkout.montypay.com/api/v1/session'); //init curl
		curl_setopt($getter, CURLOPT_POST, 1); //post
		curl_setopt($getter, CURLOPT_POSTFIELDS, $fields); //json
		curl_setopt($getter, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); //header
		curl_setopt($getter, CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($getter);
		// print_r($res);
		$err = curl_error($getter);
		$httpcode = curl_getinfo($getter, CURLINFO_HTTP_CODE);

		$json = json_decode((string) $res);

		

		if(isset($json->redirect_url) && $json->redirect_url){
			$redirect_url = $json->redirect_url;
			$data['redirect_url'] = $redirect_url;
			$data['result'] = 'success';
			$this->session->data['montypay_redirect_url'] = $redirect_url;
		}else{

			foreach($json->errors as $error){
				$data['errors'][] = array(
					'error_code'     => $error->error_code,
					'error_message'    => $error->error_message,
				);
			}
			
			
			$data['result'] = 'failed';
			$data['error_message'] = $json->error_message;
		}
		
		$data['httpcode'] = $httpcode;
		$data['json'] = json_decode($res);
		
		$callback_url = str_replace('/admin', '',$this->url->link('extension/montypay/payment/montypay.callback', '', true));
		
		$data['notification_url'] = $callback_url; 

		//file_put_contents('./log_1.log', json_encode($data), FILE_APPEND);

		return $this->load->view('extension/montypay/payment/montypay', $data);
		// return '';
	}

	public function confirm(): void {
		$this->load->language('extension/montypay/payment/montypay');

		$json = [];

		if (isset($this->session->data['order_id'])) {
			$order_id = $this->session->data['order_id'];
		} else {
			$order_id = 0;
		}

		$keys = [
			'card_name',
			'card_number',
			'card_expire_month',
			'card_expire_year',
			'card_cvv',
			'store'
		];

		foreach ($keys as $key) {
			if (!isset($this->request->post[$key])) {
				$this->request->post[$key] = '';
			}
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			$json['error']['warning'] = $this->language->get('error_order');
		}

		if (!$this->config->get('payment_montypay_status') || !isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] !== 'montypay.montypay') {
			$json['error']['warning'] = $this->language->get('error_payment_method');
		}

		if (!$json) {
				$this->load->model('checkout/order');

				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_montypay_pending_status_id'), '', true);
				// Retrieve the redirect URL from the session
				$red_url = isset($this->session->data['montypay_redirect_url']) ? $this->session->data['montypay_redirect_url'] : '';
				$json['redirect'] = $red_url;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}