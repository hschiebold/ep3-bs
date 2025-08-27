<?php

namespace Booking\Service;

use Base\Manager\OptionManager;
use Base\Manager\ConfigManager;
use Base\Service\AbstractService;
use Base\View\Helper\DateRange;
use Booking\Entity\Booking;
use Booking\Entity\Booking\Bill;
use Booking\Manager\Booking\BillManager;
use Booking\Manager\BookingManager;
use Booking\Manager\ReservationManager;
use User\Manager\UserManager;
use DateTime;
use Exception;
use RuntimeException;
use Square\Entity\Square;
use Square\Manager\SquarePricingManager;
use User\Entity\User;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BookingService extends AbstractService
{

    protected $optionManager;
    protected $configManager;
    protected $bookingManager;
    protected $billManager;
    protected $reservationManager;
    protected $squarePricingManager;
    protected $viewHelperManager;
    protected $userManager;
    protected $connection;

    public function __construct(
        OptionManager $optionManager,
        ConfigManager $configManager,
        BookingManager $bookingManager,
        BillManager $billManager,
        ReservationManager $reservationManager,
        SquarePricingManager $squarePricingManager,
        ServiceLocatorInterface $viewHelperManager,
        UserManager $userManager,
        ConnectionInterface $connection)
    {
        $this->optionManager = $optionManager;
        $this->configManager = $configManager;
        $this->bookingManager = $bookingManager;
        $this->billManager = $billManager;
        $this->reservationManager = $reservationManager;
        $this->squarePricingManager = $squarePricingManager;
        $this->viewHelperManager = $viewHelperManager;
        $this->userManager = $userManager;
        $this->connection = $connection;
    }

    public function createSingle(User $user, Square $square, $quantity, DateTime $dateTimeStart, DateTime $dateTimeEnd, array $bills = array(), array $meta = array())
    {
        // syslog(LOG_EMERG, 'createSingle');
        
        if (! $this->connection->inTransaction()) {
            $this->connection->beginTransaction();
            $transaction = true;
        } else {
            $transaction = false;
        }

        try {

            $booking = new Booking(array(
                'uid' => $user->need('uid'),
                'sid' => $square->need('sid'),
                'status' => 'single',
                'status_billing' => 'pending',
                'visibility' => 'public',
                'quantity' => $quantity,
            ), $meta);

            if ($this->configManager->get('genDoorCode') != null && $this->configManager->get('genDoorCode') == true && $square->getMeta('square_control') == true) {
               $booking->setMeta('doorCode', rand(100000,999999));
            }

            $this->bookingManager->save($booking);

            $reservations = $this->reservationManager->createInRange($booking, $dateTimeStart, $dateTimeEnd);

            $booking->setExtra('reservations', $reservations);

            $member = 0;
            if ($user != null && $user->getMeta('member') != null) {
               $member = $user->getMeta('member');
            }

            $pricing = $this->squarePricingManager->getFinalPricingInRange($dateTimeStart, $dateTimeEnd, $square, $quantity, $member);

            if ($pricing) {
                $squareType = $this->optionManager->need('subject.square.type');
                $squareName = $this->t($square->need('name'));

                /** @var $dateRangeHelper DateRange  */
                $dateRangeHelper = $this->viewHelperManager->get('DateRange');

                $description = sprintf('%s %s, %s',
                    $squareType, $squareName,
                    $dateRangeHelper($dateTimeStart, $dateTimeEnd));

                $bookingBill = new Bill(array(
                    'description' => $description,
                    'quantity' => $quantity,
                    'time' => $pricing['seconds'],
                    'price' => $pricing['price'],
                    'rate' => $pricing['rate'],
                    'gross' => $pricing['gross'],
                ));

                array_unshift($bills, $bookingBill);
            }

            if ($bills) {
                $extraBills = array();

                foreach ($bills as $bill) {
                    if (! ($bill instanceof Bill)) {
                        throw new RuntimeException('Invalid bills array passed');
                    }

                    $bill->set('bid', $booking->need('bid'));

                    $this->billManager->save($bill);

                    $extraBills[$bill->need('bid')] = $bill;
                }

                $booking->setExtra('bills', $extraBills);
            }

            if ($transaction) {
                $this->connection->commit();
                $transaction = false;
            }

            if (!$booking->getMeta('directpay') == true) { 
                $this->getEventManager()->trigger('create.single', $booking);
            }

            return $booking;

        } catch (Exception $e) {
            if ($transaction) {
                $this->connection->rollback();
            }

            throw $e;
        }
    }

    public function updatePaymentSingle(Booking $booking)
    {
        // syslog(LOG_EMERG, 'updatePaymentSingle');        
        
        $this->bookingManager->save($booking);

        $this->getEventManager()->trigger('create.single', $booking);

        return $booking;
    }

    public function refundPayment(Booking $booking)
    {
        if ($booking->get('status_billing') == 'paid' && !$booking->getMeta('refunded') == 'true') {
            $booking->setMeta('refunded', 'true');
            $this->bookingManager->save($booking);            

            $bills = $this->billManager->getBy(array('bid' => $booking->get('bid')), 'bbid ASC');
            $total = 0;
            if ($bills) {
                foreach ($bills as $bill) {
                    $total += $bill->need('price');
                }
            }

            $user = $this->userManager->get($booking->get('uid'));
            $newbudget = ($user->getBudget()*100+$total)/100;

            $user->setBudget($newbudget);
            $this->userManager->save($user);
        }
    }

    public function cancelSingle(Booking $booking)
    {
        $booking->set('status', 'cancelled');

        $this->bookingManager->save($booking);

        $this->getEventManager()->trigger('cancel.single', $booking);

        return $booking;
    }

}
