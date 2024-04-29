<?php
namespace EfipayPayment\Embebed\Observer;

use EfipayPayment\Embebed\Helper\Data;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Status;

class OrderPlaceAfter implements ObserverInterface
{
    private \Magento\Checkout\Model\Session $checkoutSession;
    private Curl $curl;
    private string $url;
    private Data $helper;
    private string $baseUrl;
    private OrderManagementInterface $orderManagement;
    private OrderRepository $orderRepository;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        Data $helper,
        Curl $curl,
        Manager $messageManager,
        OrderManagementInterface $orderManager,
        OrderRepository $orderRepository,
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->curl = $curl;
        $this->url = $url->getUrl();
        $this->helper = $helper;
        $this->baseUrl = 'https://sag.efipay.co/api/v1/payment';
        $this->orderManagement = $orderManager;
        $this->orderRepository = $orderRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orders = $observer->getData('order');
        $payment = $orders->getPayment();
        $ordernId = $orders->getRealOrderId();
        $items =$orders->getAllItems();
        //product Names
        $productNames = [];
        foreach ($items as $item) {
            $productNames[]= $item->getName();
        }

        if (count($productNames)>1) {
            $description = $ordernId;
        } else {
            $description = $productNames[0];
        }

        $total = $orders->getGrandTotal();

        $currency_code = $orders->getOrderCurrencyCode();

        $custFirstName = $orders->getCustomerFirstname();
        $custLastName = $orders->getCustomerLastname();
        $customer_email = $orders->getCustomerEmail();

        $storeName = $orders->getStoreName();

        $billingaddress = $orders->getBillingAddress();
        $billingcity = $billingaddress->getCity();
        $billingstreet = $billingaddress->getStreet();
        $billingtelephone = $billingaddress->getTelephone();

        // Set our variables in order to create Order and Charge in Efipay
        $this->checkoutSession->setAmount($total);
        $this->checkoutSession->setCurrencyCode($currency_code);
        $this->checkoutSession->setDescription($description);
        $this->checkoutSession->setStoreName($storeName);
        $this->checkoutSession->setFirstName($custFirstName);
        $this->checkoutSession->setLastName($custLastName);
        $this->checkoutSession->setPhoneNumber($billingtelephone);
        $this->checkoutSession->setEmail($customer_email);
        $this->checkoutSession->setOrderId($ordernId);
        $this->checkoutSession->setBillingCity($billingcity);
        $this->checkoutSession->setBillingStreet($billingstreet);
        $this->checkoutSession->setCountryCode('CO');

        $response = $this->sendRequestEfipay($payment);
        if($response['status'] === 200){
            return $this->markOrderAsPaid($ordernId);
        }
    }

    public function markOrderAsPaid($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            $order->setState('complete');
            $order->setStatus('complete');

            // Guardar el cambio en la base de datos
            $this->orderRepository->save($order);

            return true; // Pedido marcado como pagado correctamente
        } catch (\Exception $e) {
            return false; // Error al marcar el pedido como pagado
        }
    }


    /**
     * Send request to another URL
     * @param $payment
     * @return array|string
     */
    protected function sendRequestEfipay($payment): array|string
    {

        try {
            $apiKeyEfipay = $this->helper->getConfig('payment/efipay_payment/api_key_efipay');
            $sucursalIdEfipay = $this->helper->getConfig('payment/efipay_payment/sucursal_id_efipay');

            $url = $this->baseUrl.'/generate-payment';
            $urlStore = $this->url;
            $requestData = [
                "payment" => [
                    "description" => 'Pago Plugin Magento',
                    "amount" => $this->checkoutSession->getAmount(),
                    "currency_type" => $this->checkoutSession->getCurrencyCode(),
                    "checkout_type" => "api"
                ],
                "advanced_options" => [
                    "limit_date" => date('Y-m-d', strtotime('+1 day')),
                    "references" => [
                        $this->checkoutSession->getOrderId()
                    ],
                    "result_urls" => [
                        "webhook" => $urlStore.'rest/V1/efipay/webhook'
                    ],
                    "has_comments" => true,
                    "comment_label" => "Pago Plugin Magento"
                ],
                "office" => $sucursalIdEfipay
            ];
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer '. $apiKeyEfipay
            ];
            $this->curl->setOption(CURLOPT_HTTPHEADER, $headers);
            $this->curl->post($url, json_encode($requestData));
            $responsePayment = json_decode($this->curl->getBody());
            $statusCode = $this->curl->getStatus();
            if ($statusCode === 200) {
                return $this->checkPaymentCheckout($responsePayment, $payment);
            } else {
                $errorMessage = 'los datos proporcionados en tu configuración son erroneos o el pago fue rechazado';
                throw new \Exception($errorMessage);
            }
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function checkPaymentCheckout($responsePayment, $payment): array
    {

        $url = $this->baseUrl.'/transaction-checkout';
        $apiKeyEfipay = $this->helper->getConfig('payment/efipay_payment/api_key_efipay');
        $name = $this->checkoutSession->getFirstName() . $this->checkoutSession->getLastName();
        $data = $payment->get('additional_data')['additional_information'];
        $expirationDate = date("Y-m", strtotime($data['cc_exp_year']."-".$data['cc_exp_month']));;
        $requestData = [
            "payment" => [
                "id" => $responsePayment->payment_id,
                "token" => $responsePayment->token
            ],
            "customer_payer" => [
                "name" => $name,
                "email" => $this->checkoutSession->getEmail()
            ],
            "payment_card" => [
                "number" => intval($data['cc_number']),
                "name" => $name,
                "expiration_date" => $expirationDate,
                "cvv" => $data['cc_cid'],
                "identification_type" => "CC",
                "id_number" => "342343243",
                "installments" => "1",
                "dialling_code" => "+57",
                "cellphone" => $this->checkoutSession->getPhoneNumber()
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '. $apiKeyEfipay
        ];

        $this->curl->setOption(CURLOPT_HTTPHEADER, $headers);
        $this->curl->post($url, json_encode($requestData));
        $responseCheckout = json_decode($this->curl->getBody());
        $statusCode = $this->curl->getStatus();
        return [
            'response' => $responseCheckout,
            'status' => $statusCode
        ];
    }
}
