<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StripeController extends AbstractController
{
    #[Route('/commande/create-sesion/{reference}', name: 'app_stripe_create_session')]
    public function index($reference, OrderRepository $orderRepo, ProductRepository $productRepo, EntityManagerInterface $manager)
    {
 
        Stripe::setApiKey('sk_test_51MZEHEGRoSwmqZSihQRJDRvFieW04RUpxdGZcUteql9KuH2BbFr7KFpCk6H7Y1PXht75RGNLIIcuKJ6Q0Yvk4GZs00QOrrWEUe');

        $YOUR_DOMAIN = 'https://localhost';

        $product_for_stripe = [];

        $order =  $orderRepo->findOneByReference($reference);

        if(!$order){
            return $this->redirectToRoute('order');
        }

        //enregistrer mes produits : OrderDetail
        foreach ($order->getOrderDetails()->getValues() as $product) {
            $product_object =  $productRepo->findOneByName($product->getProduct());
            $product_for_stripe[] =[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product->getPrice(),
                    'product_data' => [
                        'name' => $product_object->getName(),
                        'images' => [$YOUR_DOMAIN."/uploads/". $product_object->getillustration()], 
                        //fonctionne pas avec localhost vue que stripe va chercher l'url de notre site et on est en localhost
                        //ça fonctionnera en prod et en changeant la variable $YOUR_DOMAIN = 'https://laboutiquefrancaise';
                    ],
                ],
                'quantity' => $product->getQuantity(),
            ];
        }

        $product_for_stripe[] =[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName(),
                    'images' => [$YOUR_DOMAIN], 
                    //fonctionne pas avec localhost vue que stripe va chercher l'url de notre site et on est en localhost
                    //ça fonctionnera en prod et en changeant la variable $YOUR_DOMAIN = 'https://laboutiquefrancaise';
                ],
            ],
            'quantity' => 1,
        ];

        $checkout_session = Session::create([
            'line_items' => [$product_for_stripe],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
        ]);

        $order->setStripeSessionId($checkout_session->id);
        $manager->flush();

        return $this->redirect($checkout_session->url);
    }
}
