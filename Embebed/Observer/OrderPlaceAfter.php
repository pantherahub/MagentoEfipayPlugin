<?php
namespace EfipayPayment\Embebed\Observer;

use EfipayPayment\Embebed\Helper\Data;
use EfipayPayment\Embebed\Helper\Response;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\Manager;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;


/**
 * @property $helperResponse
 */
class OrderPlaceAfter implements ObserverInterface
{
    private Session $checkoutSession;
    private Curl $curl;
    private string $url;
    private Data $helper;
    private string $baseUrl;
    private ManagerInterface $messageManager;
    private RedirectInterface $redirect;
    private $limit_date_payment;
    private OrderRepository $orderRepository;
    /**
     * @throws LocalizedException
     */
    public function __construct(
        UrlInterface $url,
        ResponseInterface $response,
        Order $order,
        Session $checkoutSession,
        Data $helper,
        Curl $curl,
        ManagerInterface $messageManager,
        OrderManagementInterface $orderManager,
        OrderRepository $orderRepository,
        Response $helperResponse,
        RedirectInterface $redirect

    ) {
        $this->checkoutSession = $checkoutSession;
        $this->curl = $curl;
        $this->url = $url->getUrl();
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->redirect = $redirect;
        $this->limit_date_payment = $this->helper->getConfig('payment/efipay_payment/limit_date_payment');
        $this->orderRepository = $orderRepository;

        $api_key_efipay = $this->helper->getConfig('payment/efipay_payment/api_key_efipay');
        if($api_key_efipay == ''){
            $message = __('Aun no has configurado las credenciales de pago efipay.');
            $this->messageManager->addErrorMessage($message);
            throw new LocalizedException($message);
        }
        $this->baseUrl = 'https://sag.efipay.co/api/v1';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '. $this->helper->getConfig('payment/efipay_payment/api_key_efipay')
        ];
        $this->curl->setOption(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $orders = $observer->getData('order');
        $payment = $orders->getPayment();
        $ordenId = $orders->getRealOrderId();
        $items =$orders->getAllItems();
        //product Names
        $productNames = [];
        foreach ($items as $item) {
            $productNames[]= $item->getName();
        }

        if (count($productNames)>1) {
            $description = $ordenId;
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
        $billingpostcode = $billingaddress->getPostcode();
        $billingcountry = $billingaddress->getCountryId();
        $billingstate = $billingaddress->getRegion();

        // Set our variables in order to create Order and Charge in Efipay
        $this->checkoutSession->setAmount($total);
        $this->checkoutSession->setCurrencyCode($currency_code);
        $this->checkoutSession->setDescription($description);
        $this->checkoutSession->setStoreName($storeName);
        $this->checkoutSession->setFirstName($custFirstName);
        $this->checkoutSession->setLastName($custLastName);
        $this->checkoutSession->setPhoneNumber($billingtelephone);
        $this->checkoutSession->setEmail($customer_email);
        $this->checkoutSession->setOrderId($ordenId);
        $this->checkoutSession->setBillingCity($billingcity);
        $this->checkoutSession->setBillingStreet($billingstreet);
        $this->checkoutSession->setBillingPostCode($billingpostcode);
        $this->checkoutSession->setBillingCountryCode($billingcountry);
        $this->checkoutSession->setBillingState($billingstate);

        $responseRequestPayment = $this->sendRequestEfipay($payment);

        switch ($responseRequestPayment['status']) {
            case 'Aprobada':
                $orders->setState(Order::STATE_PROCESSING)
                    ->setStatus(Order::STATE_PROCESSING);
                break;
            case 'Iniciada':
            case 'Pendiente':
            case 'Por Pagar':
                $orders->setState(Order::STATE_PENDING_PAYMENT)
                        ->setStatus(Order::STATE_PENDING_PAYMENT);
                break;

            case 'Reversada':
            case 'Reversion Escalada':
                $orders->setState(Order::STATE_CLOSED)
                        ->setStatus(Order::STATE_CLOSED);
                break;

            default:
                $orders->setState(Order::STATE_CANCELED)
                    ->setStatus(Order::STATE_CANCELED);
                break;
        }

        if($responseRequestPayment['status'] === 'Aprobada'){
            $this->messageManager->addSuccessMessage(__('¡Transacción '. $responseRequestPayment['status'] .'!'));
            return 'Payment Successful';
        }elseif($responseRequestPayment['status'] === 401){
            $message = __('Tus credenciales de acceso a efipay son erroneas, revisa tu configuracion e intenta de nuevo');
            $this->messageManager->addErrorMessage($message);
            throw new LocalizedException($message);
        }else{
            $message = __('¡Transacción '. $responseRequestPayment['status'] .'! Por favor, inténtalo de nuevo.');
            $this->messageManager->addErrorMessage($message);
            throw new LocalizedException($message);
        }
    }

    /**
     * Send request to another URL
     * @param $payment
     * @return array|string|null
     * @throws Exception
     */
    protected function sendRequestEfipay($payment): array|string|null
    {
        $sucursalIdEfipay = $this->helper->getConfig('payment/efipay_payment/sucursal_id_efipay');
        $url = $this->baseUrl.'/payment/generate-payment';
        $urlStore = $this->url;
        $requestData = [
            "payment" => [
                "description" => 'Pago del pedido Magento: '.$this->checkoutSession->getOrderId(),
                "amount" => $this->checkoutSession->getAmount(),
                "currency_type" => $this->checkoutSession->getCurrencyCode(),
                "checkout_type" => "api"
            ],
            "advanced_options" => [
                "references" => [
                    $this->checkoutSession->getOrderId(),
                    $this->checkoutSession->getEmail(),
                    "Plugin Magento"
                ],
                "result_urls" => [
                    "webhook" => $urlStore.'rest/V1/efipay/webhook'
                ],
                "has_comments" => true,
                "comment_label" => $this->checkoutSession->getDescription()
            ],
            "office" => (int)$sucursalIdEfipay
        ];

        if($this->limit_date_payment === 1 || $this->limit_date_payment === '1'){
            $requestData['advanced_options']['limit_date'] = date('Y-m-d', strtotime('+1 day'));
        }

        $this->curl->post($url, json_encode($requestData));
        $responsePayment = json_decode($this->curl->getBody());
        $statusCode = $this->curl->getStatus();
        if ($statusCode === 200) {
            $responseCheckout = $this->checkPaymentCheckout($responsePayment, $payment);
            return [
                'status' => $responseCheckout['status'],
                'response' => $responseCheckout
            ];

        }else{
            return [
                'status' => $statusCode,
                'response' => $responsePayment,
                'requestData' => $requestData
            ];
        }
    }

    /**
     * @throws Exception
     */
    public function checkPaymentCheckout($responsePayment, $payment): ?array
    {

        $url = $this->baseUrl.'/payment/transaction-checkout';
        $name = $this->checkoutSession->getFirstName() . $this->checkoutSession->getLastName();
        $data = $payment->get('additional_data')['additional_information'];
        $expirationDate = date("Y-m", strtotime($data['cc_exp_year']."-".$data['cc_exp_month']));
        $postCode = str_replace('-', '', $this->checkoutSession->getBillingPostCode());
        $zipCode = intval($postCode);
        $countryInfo = $this->getCountryInfo($this->checkoutSession->getBillingCountryCode());

        $requestData = [
            "payment" => [
                "id" => $responsePayment->payment_id,
                "token" => $responsePayment->token
            ],
            "customer_payer" => [
                "name" => $name,
                "email" => $this->checkoutSession->getEmail(),
                'address_1' => $this->checkoutSession->getFirstName(),
                'address_2' => $this->checkoutSession->getLastName(),
                'city' => $this->checkoutSession->getBillingCity(),
                'state' => $this->checkoutSession->getBillingState(),
                'zip_code'  => $zipCode,
                'country'  => $countryInfo['iso3_code'],
            ],
            "payment_card" => [
                "number" => intval($data['cc_number']),
                "name" => $name,
                "expiration_date" => $expirationDate,
                "cvv" => $data['cc_cid'],
                "identification_type" => "Otro",
                "id_number" => "000000000",
                "installments" => "1",
                "dialling_code" => $countryInfo['dialling_code'],
                "cellphone" => $this->checkoutSession->getPhoneNumber()
            ]
        ];

        $this->curl->post($url, json_encode($requestData));
        $responseCheckout = json_decode($this->curl->getBody());
        $statusCode = $this->curl->getStatus();
        return [
            'response' => $responseCheckout,
            'status' => $responseCheckout?->transaction->status,
            'log' => $statusCode === 200 ? 'success' : 'failed'.' response checkout',
            'request' => $requestData
        ];
    }

    public function getCountryInfo($code)
    {
        $url = $this->baseUrl.'/resources/get-countries';
        $this->curl->get($url);
        $responseCountry = json_decode($this->curl->getBody(), true);
        return $responseCountry[$code];
    }
}
