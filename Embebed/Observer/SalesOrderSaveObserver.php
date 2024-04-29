<?php

namespace EfipayPayment\Embebed\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderSaveObserver implements ObserverInterface
{
    public function __construct()
    {

    }

    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        dd($order);
    }
}
