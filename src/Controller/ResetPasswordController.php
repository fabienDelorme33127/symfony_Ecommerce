<?php

namespace App\Controller;

use DateTime;
use App\Classe\Mail;
use DateTimeImmutable;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ResetPasswordRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    private $manager;
    private $transportInterface;

    public function __construct(EntityManagerInterface $manager, TransportInterface $transportInterface){
        $this->manager = $manager;
        $this->transportInterface = $transportInterface;
    }

    #[Route('/mot-de-passe-oublie', name: 'app_reset_password')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        //si déjà co, redirect vers HomePage
        if($this->getUser()){
            return $this->redirectToRoute('home');
        }

        if($request->get('email')){
            $user = $userRepository->findOneByEmail($request->get('email'));

            if($user){
                // 1 : enregistrer en bdd la demande de reset password avec user,token,createdAt (entity ResetPassword)
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user);
                $resetPassword->setCreatedAt(new DateTimeImmutable());
                $resetPassword->setToken(uniqid());

                $this->manager->persist($resetPassword);
                $this->manager->flush();

                // 2 : envoyer un email à l'utilisateur avec un lien permettant de reset password et le token lié à l'utilisateur 'user_id'
                //génération de l'url sur laquelle l'utilisateur cliquera dans le mail Ex: /modifier-mon-mot-de-passe/63f6462e5ace5
                $url = $this->generateUrl('app_update_password', [
                    'token' => $resetPassword->getToken()
                ]);

                // build de l'email avec l'utlilisation d'un template twig : ->htmlTemplate('emails/reset_password.html.twig')
                //et envoie des paramètres 'url' et 'firstname' pour la vue twig afin de les mettre dans le mail
                $email = (new TemplatedEmail())
                    ->from('fabdelorme33@outlook.fr')
                    ->to('fabdelorme33@outlook.fr')
                    ->subject('Réinitialiser votre mot de passe sur La Boutique Française')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'url' => $url,
                        'firstname' => $user->getFirstname(),
                    ])
                ;

                try {
                    //envoi du mail
                    $this->transportInterface->send($email);
                } catch (TransportExceptionInterface $e) {
                    // some error prevented the email sending; display an
                    // error message or try to resend the message
                }
                //msg all good, email envoyé
                $this->addFlash('notice', 'Vous allez recevoir dans quelques secondes un mail avec la procédure pour réinitialiser votre mot de passe.');
            }else{
                //msg error, utilisateur pas trouvé en bdd avec cet email
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }

        //affichage de la vue pour mettre son email et envoyer le reset password par mail
        return $this->render('reset_password/index.html.twig');
    }

    #[Route('/modifier-mon-mot-de-passe/{token}', name: 'app_update_password')]
    public function update($token, ResetPasswordRepository $resetPasswordRepository, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        //on récupère l'entity ResetPassword via le token et donc l'utilisateur car on a un paramètre 'user' dans 'resetPassword'
        $resetPassword = $resetPasswordRepository->findOneByToken($token);

        if(!$resetPassword){
            //si on rentre un autre token ds l'url ou token error =>redirect sur la page de demande de reset password 
            return $this->redirectToRoute('app_reset_password');
        }

        //check token tjr valide (on décide du tps de validité dans notre cas de figure, pas de dateLimite en bdd lié au token)
        //s'il n'est plus valide, msg erreur et redirect vers la route demande de reset password
        $now = new DateTime();
        if($now > $resetPassword->getCreatedAt()->modify('+ 3 hour')){
            $this->addFlash('notice', 'Votre demande de mot de passe a expiré. Merci de la renouveller.');
            return $this->redirectToRoute('app_reset_password');
        }

        //si on est là, le token est bon et on affichage le formulaire de nouveau password 
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        //formulaire envoyé ? => hashpassword + flush en bdd(pas de persist vue que la donnée existe déjà) + addFlash(+ redirect vers gestion du compte)
        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->getData()['new_password'];

            $hashedPassword = $passwordHasher->hashPassword($resetPassword->getUser(), $newPassword);
            $resetPassword->getUser()->setPassword($hashedPassword);

            $this->manager->flush();

            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_account');
        }

        //affichage formulaire de nouveau password
        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
