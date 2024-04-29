<?php

namespace EfipayPayment\Embebed\Model;

use EfipayPayment\Embebed\Helper\Data;
use Magento\Sales\Model\OrderRepository;

/**
 * Class WebhookPayment
 * @package EfipayPayment\Embebed\Model
 */
class WebhookPayment
{
    private OrderRepository $orderRepository;
    private Data $helper;

    public function __construct(OrderRepository $orderRepository, Data $helper)
    {
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    /**
     * @param mixed $transaction
     * @param mixed $checkout
     * @return true
     */
    public function updateStatusOrder(mixed $transaction, mixed $checkout)
    {
        try {
            $webhookToken = $this->helper->getConfig('payment/efipay_payment/webhook');

            // validar la firma del token
            $orderId = $checkout['payment_gateway']['advanced_option']['references'][0];
            $order = $this->orderRepository->get($orderId);
            if($order){
                $order->setState('processing');
                $order->setStatus('success');
                $this->orderRepository->save($order);
                return true;
            }else{
                return 'la orden no existe';
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
