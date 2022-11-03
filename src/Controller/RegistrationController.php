<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\Company;
use App\Entity\User;
use App\Form\CreateCandidateType;
use App\Form\CreateCompanyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register-candidate', name: 'app_register_candidate')]
    public function registerCandidate(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $user = new User();
        $repoUser = $entityManager->getRepository(User::class);

        $candidate = new Candidate();
        $form = $this->createForm(CreateCandidateType::class, $candidate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $checkEmail = $repoUser->findOneBy(['email' => $form->get('user')->get('email')->getData()]);

            if (!empty($checkEmail)) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } else {
                $user->setRoles(['ROLE_CANDIDATE']);
                $user->setEmail($form->get('user')->get('email')->getData());

                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('user')->get('plainPassword')->getData()
                    )
                );
    
                $candidate->setUser($user);

                try {
                    $entityManager->persist($user);
                    $entityManager->persist($candidate);
                    $entityManager->flush();
    
                    // On envoi un email à un consultant pour qu'il valide le compte
                    $sendEmail = new TemplatedEmail();
                    $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
                    $sendEmail->to('mz.24@outlook.fr');
                    $sendEmail->replyTo('noreply@trtconseil.com');
                    $sendEmail->subject('Un nouveau candidat doit être validé');
                    $sendEmail->context([
                        'user' => $user,
                    ]);
                    $sendEmail->htmlTemplate('registration/confirmation_email.html.twig');
                    $mailer->send($sendEmail);
    
                    // On affich un message de succes
                    $this->addFlash(
                        'success',
                        'Votre compte a bien été créé, il doit être validé par un admin du site'
                    );
    
                    return $this->redirectToRoute('app_login');
                } catch (\Exception $e) {
                    $errorNumber = uniqid();
                    $logger->error('Erreur création compte candidat', [
                        'errorNumber' => $errorNumber,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    $this->addFlash(
                        'exception',
                        'Une erreur est survenue lors de la création de votre compte'
                    );
                }
            }
        }

        return $this->render('registration/register-candidate.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }


    #[Route('/register-company', name: 'app_register_company')]
    public function registerCompany(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $user = new User();
        $repoUser = $entityManager->getRepository(User::class);

        $company = new Company();
        $form = $this->createForm(CreateCompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $checkEmail = $repoUser->findOneBy(['email' => $form->get('user')->get('email')->getData()]);

            if (!empty($checkEmail)) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } else {
                $user->setRoles(['ROLE_COMPANY']);
                $user->setEmail($form->get('user')->get('email')->getData());

                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('user')->get('plainPassword')->getData()
                    )
                );
    
                $company->setUser($user);

                try {
                    $entityManager->persist($user);
                    $entityManager->persist($company);
                    $entityManager->flush();
    
                    // On envoi un email à un consultant pour qu'il valide le compte
                    $sendEmail = new TemplatedEmail();
                    $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
                    $sendEmail->to('mz.24@outlook.fr');
                    $sendEmail->replyTo('noreply@trtconseil.com');
                    $sendEmail->subject('Un nouveau recruteur doit être validé');
                    $sendEmail->context([
                        'userID' => $user->getID(),
                    ]);
                    $sendEmail->htmlTemplate('registration/confirmation_email.html.twig');
                    $mailer->send($sendEmail);
    
                    // On affich un message de succes
                    $this->addFlash(
                        'success',
                        'Votre compte a bien été créé, il doit être validé par un admin du site'
                    );
    
                    return $this->redirectToRoute('app_login');
                } catch (\Exception $e) {
                    $errorNumber = uniqid();
                    $logger->error('Erreur création compte recruteur', [
                        'errorNumber' => $errorNumber,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    $this->addFlash(
                        'exception',
                        'Une erreur est survenue lors de la création de votre compte'
                    );
                }
            }
        }

        return $this->render('registration/register-company.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }


}
