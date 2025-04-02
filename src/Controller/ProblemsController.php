<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Problems;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Protocol\Version4;
use ParagonIE\Paseto\Keys\SymmetricKey;
use ParagonIE\Paseto\ProtocolCollection;
use ParagonIE\Paseto\Purpose;

class ProblemsController extends AbstractController
{
    private EntityManagerInterface $entity_manager;
    private SerializerInterface $serializer;
    private string $pasetoKeyPath = '/../../config/paseto_key.txt';

    public function __construct(EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
        $this->entity_manager = $entity_manager;
        $this->serializer = $serializer;
        $this->pasetoKeyPath = realpath(__DIR__ . '/../../config/paseto_key.txt');
    }

    #[Route('/api/problems', name:'create_problem', methods: ['POST'])]
    public function createProblem(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // token from header
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Missing token'], 401);
        }

        $tokenString = substr($authHeader, 7); // "Bearer " entfernen
        $secretKey = new SymmetricKey(base64_decode(file_get_contents($this->pasetoKeyPath)));

        try {
            $parsedToken = (new Parser())
                ->setKey($secretKey)
                ->setPurpose(Purpose::local())
                ->parse($tokenString);

            $userId = $parsedToken->get('user_id');
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        // Nutzer anhand der UUID finden
        $user = $this->entity_manager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
        
        // Validierung der Eingaben
        if (!isset($data['title'], $data['description'], $data['category'], $data['latitude'], $data['longitude'])) {
            return new JsonResponse(['error' => 'Invalid input'], 400);
        }

        // Neues Problem erstellen
        $problem = new Problems();
        $problem->setUser($user);
        $problem->setTitle($data['title']);
        $problem->setDescription($data['description']);
        $problem->setCategory($data['category']);
        $problem->setLatitude($data['latitude']);
        $problem->setLongitude($data['longitude']);
        $problem->setStatus('offen');
        $problem->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($problem);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => (string) $errors], 400);
        }

        $this->entity_manager->persist($problem);
        $this->entity_manager->flush();

        return new JsonResponse(['message' => 'Problem created successfully'], 201);
    }

    #[Route('/api/problems', name: 'get_problems', methods: ['GET'])]
    public function getProblems(Request $request, SerializerInterface $serializer): JsonResponse
    {
        // URL Parameter extract
        $category = $request->query->get('category');
        $status = $request->query->get('status');

        $criteria = [];

        if ($category) {
            $criteria['category'] = $category;
        }

        if ($status) {
            $criteria['status'] = $status;
        }

        // Look for problems with criterias
        $problems = $this->entity_manager->getRepository(Problems::class)->findBy($criteria);
        
        $json = $serializer->serialize($problems, 'json', ['groups' => 'problem_read']);

        return new JsonResponse($json,200, [], true);
    }
}