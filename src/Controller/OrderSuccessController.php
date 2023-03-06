<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Classe\Mail;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderSuccessController extends AbstractController
{
    #[Route('/commande/merci/{stripeSessionId}', name: 'app_order_validate')]
    public function index($stripeSessionId, OrderRepository $orderRepository, EntityManagerInterface $manager, Cart $cart)
    {
        $order = $orderRepository->findOneByStripeSessionId($stripeSessionId);

        if(!$order || $order->getUser() != $this->getUser()){
            return $this->redirectToRoute('home');
        }

        //si isPaid n'est pas à 1, on met à 1 et on "valide" le paiement
        //il a payé donc on vide le panier
        if(!$order->getIsPaid()){
        
            $cart->remove();
            $order->setState(1);
            $manager->flush();

            //envoyer un mail au client pour lui confirmer sa commande/paiement
            $mail = new Mail();
            $content = "Bonjour ".$order->getUser()->getFirstname()."<br/>Merci pour votre commande.<br/><br/>blablalb blab la";
            $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande sur La Boutique Française est bien validée', $content);
        }

        
        return $this->render('order_success/index.html.twig', [
            'order' => $order
        ]);
    }
}
