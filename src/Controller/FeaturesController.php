<?php

namespace App\Controller;

use App\Entity\Features;
use App\Repository\FeaturesRepository;
use App\Repository\PropertyListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FeaturesController extends AbstractController
{
    #[Route('/api/features', name: 'create_features', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        FeaturesRepository $featuresRepo, // ✅ ADD THIS
        PropertyListRepository $propertyRepo
    ): JsonResponse {

        // Decode JSON request
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid JSON'
            ], 400);
        }

        // Validate required fields
        if (empty($data['more_features']) || empty($data['house_rules']) || empty($data['property_id'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Missing required fields'
            ], 400);
        }

        // Find Property
        $property = $propertyRepo->find($data['property_id']);

        if (!$property) {
            return $this->json([
                'status' => 'error',
                'message' => 'Property not found'
            ], 404);
        }

        $existing = $featuresRepo->findOneByProperty($data['property_id']);
       if ($existing) {
         return $this->json([
        'status' => 'error',
        'message' => 'Features already exist for this property'
       ], 400);
      }

        // Create Features entity
        $features = new Features();
        $features->setMoreFeatures($data['more_features']);
        $features->setHouseRules($data['house_rules']);
        $features->setBills($data['bills'] ?? []);
        $features->setProperty($property);

        // Save to DB
        try {
            $featuresRepo->save($features); // ✅ NOW WORKS

            return $this->json([
                'status' => 'success',
                'message' => 'Features saved successfully',
                'data' => [
                    'id' => $features->getId()
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    #[Route('/feature-details/{pid}', name: 'app_property_features', methods: ['GET'])]
    public function detail(
        int $pid,
        FeaturesRepository $featuresRepository
    ): JsonResponse {
    
        // Get features by property
        $feature = $featuresRepository->findOneByProperty($pid);
    
        if (!$feature) {
            return $this->json([
                'status' => false,
                'message' => 'Feature(s) not listed yet'
            ], 404);
        }
    
        return $this->json([
            'status' => true,
            'data' => [
                'id' => $feature->getId(),
                'more_features' => $feature->getMoreFeatures(),
                'bills' => $feature->getBills(),
                'house_rules' => $feature->getHouseRules(),
                'created_at' => $feature->getCreatedAt()->format('Y-m-d H:i:s'),
                'property_id' => $feature->getProperty()->getId()
            ]
        ]);
    }

}