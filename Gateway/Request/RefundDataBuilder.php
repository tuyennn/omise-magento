<?php

namespace Omise\Payment\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Sales\Model\Order\Payment;
use Omise\Payment\Helper\OmiseHelper;

class RefundDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var OmiseHelper
     */
    protected $omiseHelper;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param OmiseHelper $omiseHelper
     */
    public function __construct(
        SubjectReader $subjectReader,
        OmiseHelper $omiseHelper
    ) {
        $this->subjectReader = $subjectReader;
        $this->omiseHelper = $omiseHelper;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $currency = $order->getOrderCurrency();

        return [
            'store_id' => $order->getStore()->getId(),
            'transaction_id' => $payment->getParentTransactionId(),
            PaymentDataBuilder::AMOUNT => $this->omiseHelper->omiseAmountFormat(
                $currency->getCode(),
                $order->getTotalOnlineRefunded()
            )
        ];
    }
}
