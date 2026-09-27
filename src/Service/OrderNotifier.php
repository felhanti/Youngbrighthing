<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * E-mails transactionnels liés aux commandes. Un échec d'envoi est journalisé
 * mais ne bloque jamais le paiement ni l'expédition.
 */
class OrderNotifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(SHOP_NOTIFICATION_EMAIL)%')]
        private readonly string $shopEmail,
    ) {
    }

    public function sendPaymentConfirmation(Order $order): void
    {
        if ($customer = $order->getUser()?->getEmail()) {
            $this->send((new TemplatedEmail())
                ->to($customer)
                ->subject(sprintf('Votre commande #%d est confirmée', $order->getId()))
                ->htmlTemplate('emails/order_confirmation.html.twig')
                ->context(['order' => $order]), $order);
        }

        if ('' !== $this->shopEmail) {
            $this->send((new TemplatedEmail())
                ->to($this->shopEmail)
                ->subject(sprintf('Nouvelle commande #%d : %s €', $order->getId(), number_format((float) $order->getTotal(), 2, ',', ' ')))
                ->htmlTemplate('emails/order_new_shop.html.twig')
                ->context(['order' => $order]), $order);
        }
    }

    public function sendShippingNotification(Order $order): void
    {
        if ($customer = $order->getUser()?->getEmail()) {
            $this->send((new TemplatedEmail())
                ->to($customer)
                ->subject(sprintf('Votre commande #%d est en route', $order->getId()))
                ->htmlTemplate('emails/order_shipped.html.twig')
                ->context(['order' => $order]), $order);
        }
    }

    private function send(TemplatedEmail $email, Order $order): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('E-mail de commande non envoyé.', ['order' => $order->getId(), 'subject' => $email->getSubject(), 'error' => $e->getMessage()]);
        }
    }
}
