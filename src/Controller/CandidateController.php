<?php

namespace App\Controller;

use App\Entity\Candidacy;
use App\Entity\Candidate;
use App\Entity\User;
use App\Form\CreateCandidateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Exception;


#[Route('/candidate')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateController extends AbstractController
{
    #[Route('/profil/{id<\d+>}', name: 'app_candidate_profil')]
    public function profil(
        $id,
        Request $request,
        ManagerRegistry $doctrine,
        SluggerInterface $slugger,
        LoggerInterface $logger
    ): Response {
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(User::class);

        $user = $repo->findOneBy(['id' => $id]);
        $userConnect = $this->getUser();

        // On vérifie que l'utilisateur existe
        if (empty($user)) {
            throw $this->createNotFoundException('Cet utlilisateur n\'existe pas');
        }

        // On vérifie que l'utilisateur connecté peut accéder à la page profil ou qu'il est consultant
        if ($userConnect->getId() != $user->getId() && $this->denyAccessUnlessGranted('ROLE_CONSULTANT')) {
            throw $this->createNotFoundException('Vous ne pouvez pas accéder à cette page');
        }

        $candidate = $user->getCandidate();
        $form = $this->createForm(CreateCandidateType::class, $candidate, [
            'validation_groups' => ['Default'],
        ]);
        $form->remove('user');
        $form->handleRequest($request);

        // Path du CV
        $cvDirectory = $this->getParameter('cv_directory');
        $currentCv = $user->getCandidate()->getCv();
        $pathCv = $cvDirectory . '/' . $currentCv;


        if ($form->isSubmitted() && $form->isValid()) {
            $cv = $form->get('cv')->getData();
            if ($cv) {
                $originalFilename = pathinfo($cv->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $cv->guessExtension();

                try {
                    $cv->move(
                        $cvDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    $errorNumber = uniqid();
                    $logger->error('Erreur d\'ajout de CV', [
                        'errorNumber' => $errorNumber,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }

                // Suppression de l'ancien CV du serveur.
                if (file_exists($pathCv) && $currentCv != null) {
                    unlink($pathCv);
                }

                $candidate->setCV($newFilename);
            }

            try {
                $em->flush();

                // On affich un message de succes
                $this->addFlash(
                    'success',
                    'Votre compte a bien été modifié'
                );
            } catch (\Exception $e) {
                $errorNumber = uniqid();
                $logger->error('Erreur modification du compte candidat', [
                    'errorNumber' => $errorNumber,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $this->addFlash(
                    'exception',
                    'Une erreur est survenue lors de la modification de votre compte'
                );
            }
        }

        return $this->render('candidate/profil.html.twig', [
            'registrationForm' => $form->createView(),
            'candidate' => $candidate
        ]);
    }


    #[Route('/profil/{id<\d+>}/activate', name: 'app_candidate_activate')]
    #[IsGranted('ROLE_CONSULTANT')]
    public function activateCandidate(
        $id,
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        MailerInterface $mailer,
        Request $request
    ): Response {
        $submittedToken = $request->get('csrf_token');

        if ($this->isCsrfTokenValid('activate', $submittedToken)) {
            $em = $doctrine->getManager();
            $repo = $doctrine->getRepository(User::class);

            $user = $repo->findOneBy(['id' => $id]);

            // On vérifie que l'utilisateur existe
            if (empty($user)) {
                throw $this->createNotFoundException('Cet utlilisateur n\'existe pas');
            }

            // On vérifie que l'utilisateur connecté peut accéder à la page profil ou qu'il est consultant
            if ($this->denyAccessUnlessGranted('ROLE_CONSULTANT')) {
                throw $this->createNotFoundException('Vous ne pouvez pas accéder à cette page');
            }

            $user->setActive(1);

            try {
                $em->persist($user);
                $em->flush();

                $sendEmail = new TemplatedEmail();
                $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
                $sendEmail->to($user->getEmail());
                $sendEmail->replyTo('noreply@trtconseil.com');
                $sendEmail->subject('Votre compte est activé');
                $sendEmail->text('Votre compte candidat a bien été activé, vous pouvez vous connecter pour entrer en contact avec des recruteurs.');
                $mailer->send($sendEmail);

                $this->addFlash(
                    'success',
                    'Le compte a bien été activé'
                );
            } catch (\Exception $e) {
                $errorNumber = uniqid();
                $logger->error('Erreur d\'activation candidat', [
                    'errorNumber' => $errorNumber,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $this->addFlash(
                    'exception',
                    'Une erreur est survenue lors de l\'activation du compte'
                );
            }
        } else {
            throw new Exception('CSRF Token Invalid');
        }


        return $this->redirectToRoute('app_candidate_profil', ['id' => $id]);
    }


    #[Route('/my-candidacies', name: 'app_my_candidacies')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function myCandidacies(
        ManagerRegistry $doctrine,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $repo = $doctrine->getRepository(Candidacy::class);

        $candidate = $this->getUser()->getCandidate();
        $data = $repo->findBy(['candidate' => $candidate->getId()]);

        $myCandidacies = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('candidate/my-candidacies.html.twig', [
            'myCandidacies' => $myCandidacies
        ]);
    }


    #[Route('/delete/{id<\d+>}', name: 'app_delete_candidate')]
    #[IsGranted('ROLE_CONSULTANT')]
    public function deleteCandidate(
        $id,
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        Request $request,
        MailerInterface $mailer
    ): Response {
        // Vérification du token
        $submittedToken = $request->get('csrf_token');

        if ($this->isCsrfTokenValid('delete-candidate', $submittedToken)) {
            $em = $doctrine->getManager();
            $repo = $doctrine->getRepository(Candidate::class);
            $candidate = $repo->findOneBy(['id' => $id]);

            // On vérifie que l'annonce existe
            if (empty($candidate)) {
                throw $this->createNotFoundException('Ce candidat n\'existe pas');
            }

            $user = $candidate->getUser();
            $candidacies = $candidate->getCandidacies();

            try {
                foreach($candidacies as $candidacy) {
                    $candidate->removeCandidacy($candidacy);
                }
                $em->remove($candidate);
                $em->remove($user);
                $em->flush();

                $sendEmail = new TemplatedEmail();
                $sendEmail->from('TRT Conseil <noreply@trtconseil.com>');
                $sendEmail->to($user->getEmail());
                $sendEmail->replyTo('noreply@trtconseil.com');
                $sendEmail->subject('Votre compte a été supprimé');
                $sendEmail->text('Votre compte a été supprimée');
                $mailer->send($sendEmail);

                $this->addFlash(
                    'success',
                    'Le compte a bien été supprimée'
                );
            } catch (\Exception $e) {
                $errorNumber = uniqid();
                $logger->error('Erreur de suppression du compte', [
                    'errorNumber' => $errorNumber,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $this->addFlash(
                    'exception',
                    'Une erreur est survenue lors de la suppression du compte'
                );
            }
        } else {
            throw new Exception('CSRF Token Invalid');
        }

        return $this->redirectToRoute('app_listing_candidate');

    }


}
