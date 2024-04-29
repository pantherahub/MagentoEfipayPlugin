<?php

namespace EfipayPayment\Embebed\Api;

use http\Env\Request;

/**
 * interface webhookPaymentInterface
 */
interface WebhookPaymentInterface
{
    /**
     * @param Request $request
     * @return array
     */
    public function updateStatusOrder(Request $request) : array;
}
