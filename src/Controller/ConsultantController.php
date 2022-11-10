<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\Company;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

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
    public function listingCandidate(
        Request $request,
        PaginatorInterface $paginator,
        ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Candidate::class);
        $data = $repo->findAll();

        $candidates = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('consultant/listing-candidate.html.twig', [
            'candidates' => $candidates
        ]);
    }


    #[Route('/listing-company', name: 'app_listing_company')]
    public function listingCompany(
        ManagerRegistry $doctrine,
        Request $request,
        PaginatorInterface $paginator
    ): Response
    {
        $repo = $doctrine->getRepository(Company::class);
        $data = $repo->findAll();

        $companies = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('consultant/listing-company.html.twig', [
            'companies' => $companies
        ]);
    }




    
}
