<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PropertyList;
use App\Repository\PropertyRepository;
use App\Service\CloudinaryService;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PropertyController extends AbstractController
{

    #[Route('/api/post', name: 'upload_property', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function upload(
        Request $request,
        PropertyRepository $propertyRepository,
        SluggerInterface $slugger
    ): JsonResponse {
    
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw new \Exception("User not authenticated");
        }
        
        $user_id = $user->getId();

        $data = json_decode($request->getContent(), true);
    
        // ✅ Validate required fields
        $requiredFields = ['title', 'location', 'price', 'property_images'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'status' => false,
                    'message' => ucfirst($field) . ' is required'
                ], 400);
            }
        }

        // $images = $data['property_images'];
        // if (!is_array($images)) {
        //  $images = [$images]; // wrap single string in array
        // }

    
        try {
            // ✅ Create Property
            $property = new PropertyList();
            $property->setTitle($data['title']);
            $property->setDescription($data['description'] ?? null);
            $property->setLocation($data['location']);
            $property->setLga($data['lga'] ?? null);
            $property->setPrice((float) $data['price']);
            $property->setRooms((int) ($data['rooms'] ?? 1));
            $property->setBathrooms((int) ($data['bathrooms'] ?? 1));
            $property->setPropertyImages($data['property_images'] ?? []);
            
            $property->setFeatured(!empty($data['featured']));
            $property->setOwner($user) ;
    
            // ✅ Slug
            $slug = strtolower($slugger->slug($property->getTitle()));
    
            // Save property
            $propertyRepository->save($property, true);
    
            return $this->json([
                'status' => true,
                'message' => 'Property posted successfully',
                'slug' => $slug
            ]);
    
        } catch (\Exception $e) {
            return $this->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ============================
    // PROPERTY DETAILS
    // ============================

    #[Route('/property-details/{id}', name: 'app_property_details', methods: ['GET'])]
    public function detail(int $id, PropertyRepository $propertyRepository): JsonResponse
    {
        $property = $propertyRepository->find($id);

        if (!$property) {
            return $this->json([
                'status' => false,
                'message' => 'Property not found'
            ], 404);
        }

        return $this->json([
            'id' => $property->getId(),
            'title' => $property->getTitle(),
            'location' => $property->getLocation(),
            'price' => $property->getPrice(),
            'rooms' => $property->getRooms(),
            'bathrooms' => $property->getBathrooms(),
            'image' => $property->getPropertyImages(),
        ]);
    }

    // ============================
    // LIST PROPERTIES + CACHE
    // ============================

    #[Route('/properties', name: 'all_properties', methods: ['GET'])]
    public function properties(
        Request $request,
        PropertyRepository $propertyRepository,
        CacheInterface $cache
    ): JsonResponse {

        $filters = [
            'q' => $request->query->get('q'),
            'location' => $request->query->get('location'),
        ];

        $page  = $request->query->getInt('page', 1);
        $limit = 10;

        $cacheKey = 'properties_' . md5(json_encode($filters) . '_page_' . $page);

        $properties = $cache->get($cacheKey, function (ItemInterface $item) use ($propertyRepository, $filters, $page, $limit) {
            $item->expiresAfter(3600);

            return $propertyRepository->searchProperties($filters, $page, $limit);
        });

        return $this->json([
            'status' => true,
            'data' => array_map(function(PropertyList $property) {
        return [
            'id' => $property->getId(),
            'title' => $property->getTitle(),
            'propertyImages' => $property->getPropertyImages(),
            'description' => $property->getDescription(),
            'location' => $property->getLocation(),
            'lga' => $property->getLga(),
            'price' => $property->getPrice(),
            'rooms' => $property->getRooms(),
            'bathrooms' => $property->getBathrooms(),
            'featured' => $property->isFeatured(),
            'createdAt' => $property->getCreatedAt()->format(\DateTime::ATOM),    
         
        ];
    }, $properties)
        ]);
    }

    // ============================
    // TEST CACHE
    // ============================

}