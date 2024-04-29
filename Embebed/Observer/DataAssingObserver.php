<?php

declare(strict_types=1);

namespace EfipayPayment\Embebed\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssingObserver extends AbstractDataAssignObserver
{
    /**
     * @var Curl
     */
    protected Curl $curl;

    private const cc_cid = 'cc_cid';
    private const cc_exp_month = 'cc_exp_month';
    private const cc_exp_year = 'cc_exp_year';
    private const cc_number = 'cc_number';
    private const cc_ss_issue = 'cc_ss_issue';
    private const cc_ss_start_month = 'cc_ss_start_month';
    private const cc_ss_start_year = 'cc_ss_start_year';
    private const cc_type = 'cc_type';
    /**
     * @var array
     */
    protected array $addInformationList = [
        self::cc_cid,
        self::cc_exp_month,
        self::cc_exp_year,
        self::cc_number,
        self::cc_ss_issue,
        self::cc_ss_start_month,
        self::cc_ss_start_year,
        self::cc_type
    ];
    /**
     * DataAssingObserver constructor.
     * @param Curl $curl
     */
    public function __construct(
        Curl $curl
    ) {
        $this->curl = $curl;
    }

    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->addInformationList as $addInformationKey) {
            if (isset($additionalData[$addInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $addInformationKey,
                    ($additionalData[$addInformationKey]) ?: null
                );
            }
        }
    }

}
