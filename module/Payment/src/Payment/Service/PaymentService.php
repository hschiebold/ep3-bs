<?php

namespace Payment\Service;

use Base\Manager\ConfigManager;
use Base\Manager\OptionManager;
use Base\Service\AbstractService;
use Booking\Manager\BookingManager;
use Booking\Manager\ReservationManager;
use Payum\Core\Storage\FilesystemStorage;
use Payum\Core\Security\GenericTokenFactory;

class PaymentService extends AbstractService
{

    protected $configManager;
    protected $optionManager;
    protected $bookingManager;
    protected $reservationManager;
    protected $payumStorage;
    protected $payumTokenStorage;
    protected $payumTokenFactory;

    public function __construct(ConfigManager $configManager, OptionManager $optionManager, BookingManager $bookingManager, ReservationManager $reservationManager, FilesystemStorage $payumStorage, FilesystemStorage $payumTokenStorage, GenericTokenFactory $payumTokenFactory)
    {
        $this->configManager = $configManager;
        $this->optionManager = $optionManager;
        $this->bookingManager = $bookingManager;
        $this->reservationManager = $reservationManager;
        $this->payumStorage = $payumStorage;
        $this->payumTokenStorage = $payumTokenStorage;
        $this->payumTokenFactory = $payumTokenFactory;
    }

    public function initBookingPayment($booking, $user, $payservice, $total, $byproducts)
    {
        $basepath = $this->configManager->need('basepath');
        if (isset($basepath) && $basepath != '' && $basepath != ' ') {
            $basepath = '/'.$basepath;
        }
        $projectShort = $this->optionManager->need('client.name.short');
        $baseurl = $this->configManager->need('baseurl');
        $proxyurl = $this->configManager->need('proxyurl');
        $captureToken = null;
        $model = $this->payumStorage->create();
        $booking->setMeta('paymentMethod', $payservice);        
        $this->bookingManager->save($booking);
        $userName = $user->getMeta('firstname') . ' ' . $user->getMeta('lastname');
        $companyName = $this->optionManager->need('client.name.full');

        $locale = $this->configManager->need('i18n.locale');

        $description = $projectShort.' booking-'.$booking->get('bid');
        if (isset($locale) && ($locale == 'de-DE' || $locale == 'de_DE')) {
            $description = $projectShort.' Buchung-'.$booking->get('bid');
        }

        #paypal checkout
        if ($payservice == 'paypal') {
            $model['PAYMENTREQUEST_0_CURRENCYCODE'] = 'EUR';
            $model['PAYMENTREQUEST_0_AMT'] = $total/100;
            $model['PAYMENTREQUEST_0_BID'] = $booking->get('bid');
            $model['PAYMENTREQUEST_0_DESC'] = $description;
            $model['PAYMENTREQUEST_0_EMAIL'] = $user->get('email');
            $this->payumStorage->update($model);
            $captureToken = $this->payumTokenFactory->createCaptureToken(
                'paypal_ec', $model, $proxyurl.$basepath.'/payment/booking/done');
        }
        #paypal checkout
        #stripe checkout
        if ($payservice == 'stripe') {
            $model["payment_method_types"] = $this->configManager->need('stripePaymentMethods');
            $model["amount"] = $total;
            $model["currency"] = 'EUR';
            $model["description"] = $description;
            $model["receipt_email"] = $user->get('email');
            $model["metadata"] = array('bid' => $booking->get('bid'), 'productName' => $this->optionManager->need('subject.type'), 'locale' => $locale, 'instance' => $basepath, 'projectShort' => $projectShort, 'userName' => $userName, 'companyName' => $companyName, 'stripeDefaultPaymentMethod' => $this->configManager->need('stripeDefaultPaymentMethod'), 'stripeAutoConfirm' => var_export($this->configManager->need('stripeAutoConfirm'), true), 'stripePaymentRequest' => var_export($this->configManager->need('stripePaymentRequest'), true));
            $this->payumStorage->update($model);
            $captureToken = $this->payumTokenFactory->createCaptureToken(
                'stripe', $model, $proxyurl.$basepath.'/payment/booking/confirm');
        }
        #stripe checkout
        #klarna checkout
        if ($payservice == 'klarna') {
            $model['purchase_country'] = 'DE';
            $model['purchase_currency'] = 'EUR';
            $model['locale'] = 'de-DE';
            $this->payumStorage->update($model);
            $captureToken = $this->payumTokenFactory->createAuthorizeToken('klarna_checkout', $model, $proxyurl.$basepath.'/payment/booking/done');
            $notifyToken = $this->payumTokenFactory->createNotifyToken('klarna_checkout', $model);
        }
        #klarna checkout

        $targetUrl = str_replace($baseurl, $proxyurl, $captureToken->getTargetUrl());
        $captureToken->setTargetUrl($targetUrl);
        $this->payumTokenStorage->update($captureToken);

        #klarna checkout update merchant details
        if ($payservice == 'klarna') {
            $model['merchant'] = array(
                'terms_uri' => 'http://example.com/terms',
                'checkout_uri' => $captureToken->getTargetUrl(),
                'confirmation_uri' => $captureToken->getTargetUrl(),
                'push_uri' => $notifyToken->getTargetUrl()
            );
            $model['cart'] = array(
                'items' => array(
                    array(
                        'reference' => $booking->get('bid'),
                        'name' => $description,
                        'quantity' => 1,
                        'unit_price' => $total,
                    )
                )
            );
            $this->payumStorage->update($model);
        }
        #klarna checkout

        return $captureToken->getTargetUrl();
    }
}
