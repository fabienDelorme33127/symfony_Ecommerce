<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use App\Repository\HeaderRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(ProductRepository $productRepository, HeaderRepository $headerRepository, TransportInterface $transportInterface): Response
    {
/*          $email = (new Email())
            ->from('fabdelorme33@outlook.fr')
            ->to('fabdelorme33@outlook.fr')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabdelorme33@outlook.fr')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('objet du mail')
            //->attachFromPath('uploads/lezard.jpg', 'lezard.jpg')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        try {
            $transportInterface->send($email);

        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }  */




            
        $products = $productRepository->findByIsBest(1);
        $headers = $headerRepository->findAll();

        return $this->render('default/index.html.twig', [
            'products' => $products,
            'headers' => $headers,
        ]);
    }
}
