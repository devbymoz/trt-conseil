<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CreateCompanyType;
use App\Repository\CandidacyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;


#[Route('/company')]
#[IsGranted('ROLE_COMPANY')]
class CompanyController extends AbstractController
{
    #[Route('/profil/{id<\d+>}', name: 'app_company_profil')]
    public function profil(
        $id,
        Request $request,
        ManagerRegistry $doctrine,
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

        $company = $user->getCompany();
        $form = $this->createForm(CreateCompanyType::class, $company, [
            'validation_groups' => ['Default'],
        ]);
        $form->remove('user');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                // On affich un message de succes
                $this->addFlash(
                    'success',
                    'Votre compte a bien été modifié'
                );
            } catch (\Exception $e) {
                $errorNumber = uniqid();
                $logger->error('Erreur modification du compte recruteur', [
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

        return $this->render('company/profil.html.twig', [
            'registrationForm' => $form->createView(),
            'company' => $company
        ]);
    }




    #[Route('/profil/{id<\d+>}/activate', name: 'app_company_activate')]
    #[IsGranted('ROLE_CONSULTANT')]
    public function activateCompany(
        $id,
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        MailerInterface $mailer
    ): Response {
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
            $sendEmail->text('Votre compte recruteur a bien été activé, vous pouvez vous connecter pour entrer en contact avec des recruteurs.');
            $mailer->send($sendEmail);

            $this->addFlash(
                'success',
                'Le compte a bien été activé'
            );
        } catch (\Exception $e) {
            $errorNumber = uniqid();
            $logger->error('Erreur d\'activation recruteur', [
                'errorNumber' => $errorNumber,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->addFlash(
                'exception',
                'Une erreur est survenue lors de l\'activation du compte'
            );
        }

        return $this->redirectToRoute('app_company_profil', ['id' => $id]);
    }


    #[Route('/candidacies-listing', name: 'app_candidacies_company')]
    #[IsGranted('ROLE_COMPANY')]
    public function candidaciesListing(
        Request $request,
        PaginatorInterface $paginator,
        CandidacyRepository $candidacyRepo
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $company = $this->getUser()->getCompany();
        
        // Requete pour récupérer les candidatures de la compagnie
        $data = $candidacyRepo->findByCompanyAd($company->getId());
        
        $candidacies = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('company/candidacies-listing.html.twig', [
            'candidacies' => $candidacies
        ]);
    }

}
