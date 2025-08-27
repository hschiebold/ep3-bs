<?php

namespace Payment\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PaymentServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        $configManager = $sm->get('Base\Manager\ConfigManager');
        $optionManager = $sm->get('Base\Manager\OptionManager');
        $bookingManager = $sm->get('Booking\Manager\BookingManager');
        $reservationManager = $sm->get('Booking\Manager\ReservationManager');
        $payumStorage = $sm->get('payum')->getStorage('Application\Model\PaymentDetails');
        $payumTokenStorage = $sm->get('payum.options')->getTokenStorage();
        $payumTokenFactory = $sm->get('payum.security.token_factory');

        return new PaymentService($configManager, $optionManager, $bookingManager, $reservationManager, $payumStorage, $payumTokenStorage, $payumTokenFactory);
    }

}
