<?php

namespace App\Controller;

use App\Entity\Features;
use App\Entity\PropertyList;
use App\Entity\User;
use App\Repository\FeaturesRepository;
use App\Repository\PropertyRepository;
use App\Service\CloudinaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/listing')]
final class ListingController extends AbstractController
{
    // ─────────────────────────────────────────────
    // POST /api/listing  — create listing
    // ─────────────────────────────────────────────
    // ─────────────────────────────────────────────
    // PATCH /api/listing/:id  — update own listing
    // ─────────────────────────────────────────────
    #[Route('/{id}', name: 'listing_update', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        int $id,
        Request $request,
        PropertyRepository $propertyRepository
    ): JsonResponse {
        /** @var User $user */
        $user    = $this->getUser();
        $listing = $propertyRepository->find($id);

        if (!$listing) {
            return $this->json(['status' => false, 'message' => 'Listing not found'], 404);
        }

        if ($listing->getOwner()->getId() !== $user->getId()) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        

        if (isset($data['title']))          $listing->setTitle($data['title']);
        if (isset($data['description']))    $listing->setDescription($data['description']);
        if (isset($data['location']))       $listing->setLocation($data['location']);
        if (isset($data['state']))          $listing->setState($data['state']);
        if (isset($data['lga']))            $listing->setLga($data['lga']);
        if (isset($data['type']))           $listing->setType($data['type']);
        if (isset($data['price']))          $listing->setPrice((float) $data['price']);
        if (isset($data['rooms']))          $listing->setRooms((int) $data['rooms']);
        if (isset($data['bathrooms']))      $listing->setBathrooms((int) $data['bathrooms']);
        if (isset($data['toilets']))        $listing->setToilets((int) $data['toilets']);
        if (isset($data['parking_space']))  $listing->setParkingSpace((bool) $data['parking_space']);
        if (isset($data['images']))         $listing->setPropertyImages($data['images']);
        if (isset($data['status']))         $listing->setStatus($data['status']);
        if (isset($data['available_from'])) $listing->setAvailableFrom(new \DateTime($data['available_from']));

        $listing->touch();
        $propertyRepository->save($listing);

        return $this->json(['status' => true, 'data' => $this->serializeListing($listing)]);
    }

    // ─────────────────────────────────────────────
    // DELETE /api/listing/:id  — soft-delete (archive)
    // ─────────────────────────────────────────────
    #[Route('/{id}', name: 'listing_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        int $id,
        PropertyRepository $propertyRepository
    ): JsonResponse {
        /** @var User $user */
        $user    = $this->getUser();
        $listing = $propertyRepository->find($id);

        if (!$listing) {
            return $this->json(['status' => false, 'message' => 'Listing not found'], 404);
        }

        if ($listing->getOwner()->getId() !== $user->getId()) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $listing->setStatus(PropertyList::STATUS_ARCHIVED);
        $listing->touch();
        $propertyRepository->save($listing);

        return $this->json(['status' => true, 'data' => $this->serializeListing($listing)]);
    }

    // ─────────────────────────────────────────────
    // GET /api/listing  — all active listings (paginated)
    // ─────────────────────────────────────────────
    #[Route('', name: 'listing_index', methods: ['GET'])]
    public function index(
        Request $request,
        PropertyRepository $propertyRepository,
        CacheInterface $cache
    ): JsonResponse {
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $cacheKey = 'listings_active_p' . $page . '_l' . $limit;

        $listings = $cache->get($cacheKey, function (ItemInterface $item) use ($propertyRepository, $page, $limit) {
            $item->expiresAfter(300);
            return $propertyRepository->findActive($page, $limit);
        });

        $total = $cache->get('listings_active_count', function (ItemInterface $item) use ($propertyRepository) {
            $item->expiresAfter(300);
            return $propertyRepository->countActive();
        });

        $limitss = 10;
        $properties = $propertyRepository->findLatest($limitss);
        dd($properties);

        return $this->json([
            'status' => true,
            'data'   => array_map([$this, 'serializeListing'], $listings),
            'meta'   => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/listing/me  — current user's listings
    // ─────────────────────────────────────────────
    #[Route('/me', name: 'listing_mine', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mine(
        Request $request,
        PropertyRepository $propertyRepository
    ): JsonResponse {
        /** @var User $user */
        $user  = $this->getUser();
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $listings = $propertyRepository->findByOwnerPaginated($user, $page, $limit);
        $total    = $propertyRepository->countByOwner($user);

        return $this->json([
            'status' => true,
            'data'   => array_map([$this, 'serializeListing'], $listings),
            'meta'   => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/listing/search  — filtered search
    // ─────────────────────────────────────────────
    #[Route('/search', name: 'listing_search', methods: ['GET'])]
    public function search(
        Request $request,
        PropertyRepository $propertyRepository
    ): JsonResponse {
        $filters = [
            'state'         => $request->query->get('state'),
            'lga'           => $request->query->get('lga'),
            'type'          => $request->query->get('type'),
            'min_price'     => $request->query->get('min_price'),
            'max_price'     => $request->query->get('max_price'),
            'rooms'         => $request->query->get('rooms'),
            'toilets'       => $request->query->get('toilets'),
            'parking_space' => $request->query->get('parking_space'),
            'q'             => $request->query->get('q'),
        ];

        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $listings = $propertyRepository->searchListings($filters, $page, $limit);
        $total    = $propertyRepository->countSearch($filters);

        return $this->json([
            'status' => true,
            'data'   => array_map([$this, 'serializeListing'], $listings),
            'meta'   => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/listing/:id  — single listing
    // ─────────────────────────────────────────────
    #[Route('/{id}', name: 'listing_show', methods: ['GET'])]
    public function show(int $id, PropertyRepository $propertyRepository): JsonResponse
    {
        $listing = $propertyRepository->find($id);

        if (!$listing) {
            return $this->json(['status' => false, 'message' => 'Listing not found'], 404);
        }

        return $this->json(['status' => true, 'data' => $this->serializeListing($listing)]);
    }

    //listing/search
    // ─────────────────────────────────────────────
    // POST /api/listing/:id/upload  — upload image via Cloudinary
    // ─────────────────────────────────────────────
    #[Route('/create', name: 'listing_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function create(
    Request $request,
    PropertyRepository $propertyRepository,
    CloudinaryService $cloudinary
): JsonResponse {
    /** @var User $user */
    $user = $this->getUser();

    // Handle form-data OR raw JSON
    $data = $request->request->all();

    if (empty($data)) {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['status' => false, 'message' => 'Invalid JSON'], 400);
        }
    }

    // ───────── VALIDATION ─────────
    if (!isset($data['title']) || trim($data['title']) === '') {
        return $this->json(['status' => false, 'message' => 'Title is required'], 400);
    }

    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        return $this->json(['status' => false, 'message' => 'Invalid price'], 400);
    }

    // ───────── CREATE LISTING ─────────
    $listing = new PropertyList();
    $listing->setTitle(trim($data['title']));
    $listing->setDescription($data['description'] ?? null);
    $listing->setLocation($data['location'] ?? '');
    $listing->setState($data['state'] ?? null);
    $listing->setLga($data['lga'] ?? null);
    $listing->setType($data['type'] ?? null);
    $listing->setPrice((float) $data['price']);
    $listing->setRooms((int) ($data['rooms'] ?? 1));
    $listing->setBathrooms((int) ($data['bathrooms'] ?? 1));
    $listing->setToilets((int) ($data['toilets'] ?? 1));
    $listing->setParkingSpace(
        filter_var($data['parking_space'] ?? false, FILTER_VALIDATE_BOOLEAN)
    );
    $listing->setStatus(PropertyList::STATUS_DRAFT);
    $listing->setOwner($user);

    // Safe date parsing
    if (!empty($data['available_from'])) {
        try {
            $listing->setAvailableFrom(new \DateTime($data['available_from']));
        } catch (\Exception $e) {
            return $this->json(['status' => false, 'message' => 'Invalid date'], 400);
        }
    }

    // ───────── HANDLE IMAGE UPLOAD ─────────
    $uploadedImages = [];

    $files = $request->files->all()['images'] ?? [];

    if (!is_array($files)) {
        $files = [$files];
    }

    foreach ($files as $file) {
        if (!$file) continue;

        if (!$file->isValid()) {
            return $this->json(['status' => false, 'message' => 'Invalid image upload'], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['status' => false, 'message' => 'Invalid image type'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['status' => false, 'message' => 'Image too large'], 400);
        }

        try {
            $url = $cloudinary->upload($file);
            $uploadedImages[] = $url;
        } catch (\Throwable $e) {
            return $this->json(['status' => false, 'message' => 'Upload failed'], 500);
        }
    }

    // Limit images
    if (count($uploadedImages) > 10) {
        return $this->json(['status' => false, 'message' => 'Max 10 images allowed'], 400);
    }

    $listing->setPropertyImages($uploadedImages);

    // ───────── SAVE ─────────
    $propertyRepository->save($listing);

    return $this->json([
        'status' => true,
        'data'   => $this->serializeListing($listing)
    ], 201);
}

    // ─────────────────────────────────────────────
    // Serializer helper
    // ─────────────────────────────────────────────
    private function serializeListing(PropertyList $l): array
    {
        return [
            'id'             => $l->getId(),
            'title'          => $l->getTitle(),
            'description'    => $l->getDescription(),
            'location'       => $l->getLocation(),
            'state'          => $l->getState(),
            'lga'            => $l->getLga(),
            'type'           => $l->getType(),
            'price'          => $l->getPrice(),
            'rooms'          => $l->getRooms(),
            'bathrooms'      => $l->getBathrooms(),
            'toilets'        => $l->getToilets(),
            'parking_space'  => $l->hasParkingSpace(),
            'images'         => $l->getPropertyImages(),
            'status'         => $l->getStatus(),
            'available_from' => $l->getAvailableFrom()?->format('Y-m-d'),
            'owner_id'       => $l->getOwner()->getId(),
            'created_at'     => $l->getCreatedAt()->format(\DateTime::ATOM),
            'updated_at'     => $l->getUpdatedAt()->format(\DateTime::ATOM),
        ];
    }
}
