<?php

namespace App\Controller;

use App\Entity\Ad;
use App\Form\CreateAdType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdController extends AbstractController
{
    #[Route('/create-ad', name: 'app_create_ad')]
    #[IsGranted('ROLE_COMPANY')]
    public function createAd(
        Request $request,
        MailerInterface $mailer,
        ManagerRegistry $doctrine,
        LoggerInterface $logger
    ): Response {
        $em = $doctrine->getManager();
        $ad = new Ad();

        $form = $this->createForm(CreateAdType::class, $ad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère la compagny de l'utilisateur connecté
            $company = $this->getUser()->getCompany();

            // On vérifie que la compagny existe
            if (empty($company)) {
                throw $this->createNotFoundException('Vous ne pouvez pas déposer d\annonce');
            }

            $ad->setCompany($company);

            try {
                $em->persist($ad);
                $em->flush();

                $sendEmail = new TemplatedEmail();
                $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
                $sendEmail->to('mz.24@outlook.fr');
                $sendEmail->replyTo('noreply@trtconseil.com');
                $sendEmail->subject('Une nouvelle annonce a été déposé');
                $sendEmail->context([
                    'ad' => $ad
                ]);
                $sendEmail->htmlTemplate('ad/confirm-ad_email.html.twig');
                $mailer->send($sendEmail);

                $this->addFlash(
                    'success',
                    'L\'annonce a bien été créée'
                );
            } catch (\Exception $e) {
                $errorNumber = uniqid();
                $logger->error('Erreur de création d\'annonce', [
                    'errorNumber' => $errorNumber,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $this->addFlash(
                    'exception',
                    'Une erreur est survenue lors de la creation de l\'annonce'
                );
            }
        }

        return $this->render('ad/create-ad.html.twig', [
            'adForm' => $form->createView(),
        ]);
    }


    #[Route('/ad/{id<\d+>}', name: 'app_unique_ad')]
    public function uniqueAd(
        $id,
        ManagerRegistry $doctrine,
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $repo = $doctrine->getRepository(Ad::class);
        $ad = $repo->findOneBy(['id' => $id]);

        // On vérifie que l'annonce existe
        if (empty($ad)) {
            throw $this->createNotFoundException('Cet annonce n\'existe pas');
        }

        // Si l'utilisateur est un candidat, l'annonce doit etre actif
        if ($this->isGranted('ROLE_CANDIDATE') && $ad->isActive() == 0) {
            throw new AccessDeniedException('Vous ne pouvez pas accéder à cette annonce');
        }

        $company = $this->getUser()->getCompany();
        // Si l'annonce n'est pas actif, seul un consultant ou le propriétaire peut y accéder
        if (
            !$this->isGranted('ROLE_CONSULTANT') && 
            $ad->isActive() == 0 && 
            $ad->getcompany()->getId() != $company->getId()
            ) 
        {
            throw new AccessDeniedException('Vous ne pouvez pas accéder à cette annonce');
        }

        
        return $this->render('ad/unique-ad.html.twig', [
            'ad' => $ad
        ]);
    }


    #[Route('/ad/{id<\d+>}/activate', name: 'app_ad_activate')]
    #[IsGranted('ROLE_CONSULTANT')]
    public function activatead(
        $id,
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        MailerInterface $mailer
    ): Response {
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Ad::class);
        $ad = $repo->findOneBy(['id' => $id]);

        // On vérifie que l'annonce existe
        if (empty($ad)) {
            throw $this->createNotFoundException('Cet annonce n\'existe pas');
        }

        $ad->setActive(1);
        $user = $ad->getCompany()->getUser();

        try {
            $em->persist($user);
            $em->flush();

            $sendEmail = new TemplatedEmail();
            $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
            $sendEmail->to($user->getEmail());
            $sendEmail->replyTo('noreply@trtconseil.com');
            $sendEmail->subject('Votre annonce est activé');
            $sendEmail->text('Votre offre d\'emploi a bien été validée');
            $mailer->send($sendEmail);

            $this->addFlash(
                'success',
                'L\'annonce a bien été activée'
            );
        } catch (\Exception $e) {
            $errorNumber = uniqid();
            $logger->error('Erreur d\'activation d\'annonce', [
                'errorNumber' => $errorNumber,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->addFlash(
                'exception',
                'Une erreur est survenue lors de l\'activation de l\'annonce'
            );
        }

        return $this->redirectToRoute('app_unique_ad', ['id' => $id]);
    }


    #[Route('/ad/listing', name: 'app_ad_listing')]
    public function adListing(
        ManagerRegistry $doctrine,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $repo = $doctrine->getRepository(Ad::class);

        if ($this->isGranted('ROLE_CONSULTANT')) {
            $data = $repo->findAll();
        } else {
            $data = $repo->findBy(['active' => 1]);
        }

        $ads = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('ad/ad-listing.html.twig', [
            'ads' => $ads
        ]);
    }


    #[Route('/ad/myads', name: 'app_my_ads')]
    #[IsGranted('ROLE_COMPANY')]
    public function myAds(
        ManagerRegistry $doctrine,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $company = $this->getUser()->getCompany();

        if (empty($company)) {
            throw $this->createNotFoundException('Page non trouvée');
        }
        $companyId = $company->getId();

        $repo = $doctrine->getRepository(Ad::class);
        $data = $repo->findBy(['company' => $companyId]);

        $ads = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('ad/my-ads.html.twig', [
            'ads' => $ads
        ]);
    }




}
