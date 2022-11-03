<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Doctrine\Persistence\ManagerRegistry;


#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/create-consultant', name: 'app_admin_create_consultant')]
    public function createConsultant(
        Request $request,
        MailerInterface $mailer, 
        ManagerRegistry $doctrine,
        LoggerInterface $logger
    ): Response
    {
        $em = $doctrine->getManager();
        $repoUser = $doctrine->getRepository(User::class);
        $user = new User();
        $user->setRoles(['ROLE_CONSULTANT']);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->remove('plainPassword');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $checkEmail = $repoUser->findOneBy(['email' => $data->getEmail()]);

            if (!empty($checkEmail)) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } else {
                $token = rand(1, 1000) . uniqid();
                $user->setToken($token);
                $user->setEmail($data->getEmail());

                try {
                    $em->persist($user);
                    $em->flush();
    
                    // On envoi un email au consultant pour qu'il créait son MDP
                    $sendEmail = new TemplatedEmail();
                    $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
                    $sendEmail->to($user->getEmail());
                    $sendEmail->replyTo('noreply@trtconseil.com');
                    $sendEmail->subject('Créer votre mot de passe');
                    $sendEmail->context([
                        'user' => $user
                    ]);
                    $sendEmail->htmlTemplate('registration/create-password_email.html.twig');
                    $mailer->send($sendEmail);
    
                    // On affich un message de succes
                    $this->addFlash(
                        'success',
                        'Le consultant a bien été créé'
                    );
                } catch (\Exception $e) {
                    $errorNumber = uniqid();
                    $logger->error('Erreur création compte consultant', [
                        'errorNumber' => $errorNumber,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    $this->addFlash(
                        'exception',
                        'Une erreur est survenue lors de la création du consultant'
                    );
                }                    
            }
        }

        return $this->render('admin/create-consultant.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

}
