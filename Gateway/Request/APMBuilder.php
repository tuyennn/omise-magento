<?php
namespace Omise\Payment\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

use Omise\Payment\Model\Config\Alipay;
use Omise\Payment\Model\Config\Conveniencestore;
use Omise\Payment\Model\Config\Fpx;
use Omise\Payment\Model\Config\Pointsciti;
use Omise\Payment\Model\Config\Internetbanking;
use Omise\Payment\Model\Config\Installment;
use Omise\Payment\Model\Config\Tesco;
use Omise\Payment\Model\Config\Paynow;
use Omise\Payment\Model\Config\Promptpay;
use Omise\Payment\Model\Config\Truemoney;
use Omise\Payment\Model\Config\Alipayplus;
use Omise\Payment\Model\Config\Mobilebanking;
use Omise\Payment\Model\Config\Rabbitlinepay;
use Omise\Payment\Model\Config\Ocbcpao;
use Omise\Payment\Model\Config\Grabpay;
use Omise\Payment\Model\Config\Boost;
use Omise\Payment\Model\Config\DuitnowOBW;
use Omise\Payment\Model\Config\DuitnowQR;
use Omise\Payment\Model\Config\MaybankQR;
use Omise\Payment\Model\Config\Shopeepay;
use Omise\Payment\Model\Config\Touchngo;

use Omise\Payment\Observer\ConveniencestoreDataAssignObserver;
use Omise\Payment\Observer\FpxDataAssignObserver;
use Omise\Payment\Observer\DuitnowOBWDataAssignObserver;
use Omise\Payment\Observer\InstallmentDataAssignObserver;
use Omise\Payment\Observer\MobilebankingDataAssignObserver;
use Omise\Payment\Observer\InternetbankingDataAssignObserver;
use Omise\Payment\Observer\TruemoneyDataAssignObserver;
use Omise\Payment\Helper\OmiseHelper as Helper;
use Omise\Payment\Helper\ReturnUrlHelper;
use Omise\Payment\Model\Config\Config;
use Omise\Payment\Model\Capabilities;

class APMBuilder implements BuilderInterface
{

    /**
     * @var string
     */
    const SOURCE = 'source';

    /**
     * @var string
     */
    const SOURCE_TYPE = 'type';

    /**
     * @var string
     */
    const BANK = 'bank';

    /**
     * @var string
     */
    const PLATFORM_TYPE = 'platform_type';

    /**
     * @var string
     */
    const SOURCE_INSTALLMENT_TERMS = 'installment_terms';

    /**
     * @var string
     */
    const SOURCE_PHONE_NUMBER = 'phone_number';

    /**
     * @var string
     */
    const SOURCE_NAME = 'name';

    /**
     * @var string
     */
    const SOURCE_EMAIL = 'email';

    /**
     * @var string
     */
    const RETURN_URI = 'return_uri';

    /**
     * @var string
     */
    const ZERO_INTEREST_INSTALLMENTS = 'zero_interest_installments';

    /**
     * @var \Omise\Payment\Helper\ReturnUrlHelper
     */
    protected $returnUrl;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param $helper    \Omise\Payment\Helper\OmiseHelper
     * @param $returnUrl \Omise\Payment\Helper\ReturnUrl
     */
    public function __construct(
        Helper $helper,
        ReturnUrlHelper $returnUrl,
        Config $config,
        Capabilities $capabilities
    ) {
        $this->helper = $helper;
        $this->returnUrl = $returnUrl;
        $this->config = $config;
        $this->capabilities = $capabilities;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $returnUrl = $this->returnUrl->create('omise/callback/offsite');
        $payment = $buildSubject['payment']->getPayment();
        $payment->setAdditionalInformation('token', $returnUrl['token']);

        $paymentInfo = [ self::RETURN_URI => $returnUrl['url'] ];

        $payment = SubjectReader::readPayment($buildSubject);
        $method  = $payment->getPayment();

        switch ($method->getMethod()) {
            case Alipay::CODE:
                $paymentInfo[self::SOURCE] = Alipay::getSourceData();
                break;
            case Tesco::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'bill_payment_tesco_lotus'
                ];
                break;
            case Internetbanking::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => $method->getAdditionalInformation(InternetbankingDataAssignObserver::OFFSITE)
                ];
                break;
            case Installment::CODE:
                $installmentId = $method->getAdditionalInformation(InstallmentDataAssignObserver::OFFSITE);
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE              => $installmentId,
                    self::SOURCE_INSTALLMENT_TERMS => $method->getAdditionalInformation(
                        InstallmentDataAssignObserver::TERMS
                    ),
                    self::ZERO_INTEREST_INSTALLMENTS => ('installment_mbb' === $installmentId)
                ];
                break;
            case Truemoney::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE         => 'truemoney',
                    self::SOURCE_PHONE_NUMBER => $method->getAdditionalInformation(
                        TruemoneyDataAssignObserver::PHONE_NUMBER
                    ),
                ];
                break;
            case Conveniencestore::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE         => 'econtext',
                    self::SOURCE_PHONE_NUMBER => $method->getAdditionalInformation(
                        ConveniencestoreDataAssignObserver::PHONE_NUMBER
                    ),
                    self::SOURCE_EMAIL        => $method->getAdditionalInformation(
                        ConveniencestoreDataAssignObserver::EMAIL
                    ),
                    self::SOURCE_NAME         => $method->getAdditionalInformation(
                        ConveniencestoreDataAssignObserver::CUSTOMER_NAME
                    )
                ];
                break;
            case Paynow::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'paynow'
                ];
                break;
            case Promptpay::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'promptpay'
                ];
                break;
            case Pointsciti::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'points_citi'
                ];
                break;
            case Fpx::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'fpx',
                    self::BANK => $method->getAdditionalInformation(
                        FpxDataAssignObserver::BANK
                    )
                ];
                break;
            case Alipayplus::ALIPAY_CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE   => 'alipay_cn',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Alipayplus::ALIPAYHK_CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE   => 'alipay_hk',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Alipayplus::DANA_CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE   => 'dana',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Alipayplus::GCASH_CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE   => 'gcash',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Alipayplus::KAKAOPAY_CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE   => 'kakaopay',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Touchngo::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE   => 'touch_n_go',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Mobilebanking::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => $method->getAdditionalInformation(MobilebankingDataAssignObserver::OFFSITE),
                    self::PLATFORM_TYPE => $this->helper->getPlatformType()
                ];
                break;
            case Rabbitlinepay::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'rabbit_linepay'
                ];
                break;
            case Ocbcpao::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'mobile_banking_ocbc_pao',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Grabpay::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'grabpay',
                    self::PLATFORM_TYPE => $this->helper->getPlatformType(),
                ];
                break;
            case Boost::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'boost',
                ];
                break;
            case DuitnowOBW::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'duitnow_obw',
                    self::BANK => $method->getAdditionalInformation(
                        DuitnowOBWDataAssignObserver::BANK
                    )
                ];
                break;
            case DuitnowQR::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'duitnow_qr',
                ];
                break;
            case MaybankQR::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => 'maybank_qr',
                ];
                break;
            case Shopeepay::CODE:
                $paymentInfo[self::SOURCE] = [
                    self::SOURCE_TYPE => $this->getShopeepaySource()
                ];
                break;
        }

        return $paymentInfo;
    }

    private function getShopeepaySource()
    {
        $isShopeepayJumpAppEnabled = $this->capabilities->isBackendEnabled(Shopeepay::JUMPAPP_ID);
        $isShopeepayEnabled = $this->capabilities->isBackendEnabled(Shopeepay::ID);

        // If user is in mobile and jump app is enabled then return shopeepay_jumpapp as source
        if ($this->helper->isMobilePlatform() && $isShopeepayJumpAppEnabled) {
            return Shopeepay::JUMPAPP_ID;
        }

        // If above condition fails then it means either
        //
        // Case 1.
        // User is using mobile device but jump app is not enabled.
        // This means shopeepay direct is enabled otherwise this code would not execute.
        //
        // Case 2.
        // Jump app is enabled but user is not using mobile device
        //
        // In both cases we will want to show the shopeepay MPM backend first if MPM is enabled.
        // If MPM is not enabled then it means jump app is enabled because this code would never
        // execute if none of the shopee backends were disabled.
        return $isShopeepayEnabled ? Shopeepay::ID : Shopeepay::JUMPAPP_ID;
    }
}
