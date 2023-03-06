<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     */
    public function index(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $notification = null;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            //check si l'email existe déjà
            //s'il n'existe pas => inscription + notification Inscription OK
            $search_email = $userRepository->findOneByEmail($user->getEmail());
            if(!$search_email){
                $plaintextPassword = $form->get("password")->getData();

                $hashedPassword = $passwordHasher->hashPassword($user,$plaintextPassword);
                $user->setPassword($hashedPassword);
    
                $manager->persist($user);
                $manager->flush();

                $mail = new Mail();
                $content = "Bonjour ".$user->getFirstname()."<br/>Bienvenue sur la première boutique fiqzohfjqizo hfjqoizfh qzoihf qoifhqzoifhqzoi fhoqiz hfoqizhfoqizfhoqizhfoqzhfqzoifh qzoifh oqzhfoqizfh</br>dqzidjqz<br/>dqzdiqzhddpqzjidpqzjdpq djqz podjqp ozdjq pzdjq dpozj";
                $mail->send($user->getEmail(), $user->getFirstname(), 'Bienvenue sur la boutique française', $content);

                $notification = "Votre inscription s'est correctement déroulée. Vous pouvez dès à présent vous connecter à votre compte.";
            }else{
                $notification = "L'email que vous avez renseigné existe déjà.";
            }
            
        }

        return $this->render('register/register.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
