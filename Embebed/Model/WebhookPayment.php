<?php

namespace EfipayPayment\Embebed\Model;

use EfipayPayment\Embebed\Api\WebhookPaymentInterface;

/**
 * Class WebhookPayment
 * @package EfipayPayment\Embebed\Model
 */
class WebhookPayment
{
    /**
     * @param mixed $transaction
     * @param mixed $checkout
     * @return array
     */
    public function updateStatusOrder(mixed $transaction, mixed $checkout): array
    {
        // procesar orden en magento
        $webhook = $this->helper->getConfig('payment/efipay_payment/webhook');

        return ['success' => true, 'status' => $transaction, 'webhook' => $webhook];
    }
}
