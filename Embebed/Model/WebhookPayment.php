<?php

namespace EfipayPayment\Embebed\Model;

use Magento\Sales\Model\Order;
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
                switch ($transaction['status']) {
                    case 'Aprobada':
                        $order->setState(Order::STATE_PROCESSING)
                            ->setStatus(Order::STATE_PROCESSING);
                        break;
                    case 'Iniciada':
                    case 'Pendiente':
                    case 'Por Pagar':
                        $order->setState(Order::STATE_PENDING_PAYMENT)
                                ->setStatus(Order::STATE_PENDING_PAYMENT);
                        break;
        
                    case 'Reversada':
                    case 'Reversion Escalada':
                        $order->setState(Order::STATE_CLOSED)
                                ->setStatus(Order::STATE_CLOSED);
                        break;
        
                    default:
                        $order->setState(Order::STATE_CANCELED)
                            ->setStatus(Order::STATE_CANCELED);
                        break;
                }

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
