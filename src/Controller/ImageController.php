<?php
namespace App\Controller;

use App\Service\CloudinaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{
    #[Route('/upload', name: 'image_upload')]
    public function upload(Request $request, CloudinaryService $cloudinaryService): Response
    {
        $file = $request->files->get('image'); // name="image" in your form
        if ($file) {
            $url = $cloudinaryService->upload($file);
            return new Response('Uploaded: ' . $url);
        }

        return new Response('No file uploaded');
    }
}
