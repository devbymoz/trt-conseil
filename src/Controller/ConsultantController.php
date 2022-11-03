<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\Company;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\Persistence\ManagerRegistry;

#[Route('/consultant')]
#[IsGranted('ROLE_CONSULTANT')]
class ConsultantController extends AbstractController
{
    #[Route('/', name: 'app_consultant')]
    public function index(): Response
    {
        return $this->render('consultant/index.html.twig');
    }


    #[Route('/listing-candidate', name: 'app_listing_candidate')]
    public function listingCandidate(ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Candidate::class);
        $candidates = $repo->findAll();

        return $this->render('consultant/listing-candidate.html.twig', [
            'candidates' => $candidates
        ]);
    }


    #[Route('/listing-company', name: 'app_listing_company')]
    public function listingCompany(ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Company::class);
        $companies = $repo->findAll();

        return $this->render('consultant/listing-company.html.twig', [
            'companies' => $companies
        ]);
    }


}
