<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Problems;
use App\Entity\Solutions;
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

class SolutionsController extends AbstractController
{
    private EntityManagerInterface $entity_manager;
    private SerializerInterface $serializer;
    private string $pasetoKeyPath = '/../../config/paseto_key.txt';

    public function __construct(EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
        $this->entity_manager = $entity_manager;
        $this->serializer = $serializer;
        $this->pasetoKeyPath = realpath(__DIR__ . '/../../config/paseto_key.txt');
    }

    #[Route('/api/solutions', name: 'get_solutions', methods: ['GET'])]
    public function getSolutions(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $solutions = $entityManager->getRepository(Solutions::class)->findAll();

        // Wenn keine Lösungen gefunden werden, wird eine leere Liste zurückgegeben
        if (!$solutions) {
            return new JsonResponse([], 200);
        }

        // Serialisierung der Lösungen in JSON
        $data = $serializer->serialize($solutions, 'json', ['groups' => ['solution']]);

        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/api/solutions', name:'create_solution', methods: ['POST'])]
    public function createSolution(Request $request, ValidatorInterface $validator): JsonResponse
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

        // Find user with UUID
        $user = $this->entity_manager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
        
        // Check data
        if (!isset($data['description'], $data['problem'])) {
            return new JsonResponse(['error' => 'Invalid input'], 400);
        }

        // Find and check problem with UUID
        try {
            $problemId = Uuid::fromString($data['problem']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Invalid UUID format'], 400);
        }

        $problem = $this->entity_manager->getRepository(Problems::class)->find(Uuid::fromString($problemId));
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found']);
        }

        // make new solution
        $solution = new solutions();
        $solution->setProblem($problem);
        $solution->setUser($user);
        $solution->setDescription($data['description']);
        $solution->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($solution);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => (string) $errors], 400);
        }

        $this->entity_manager->persist($solution);
        $this->entity_manager->flush();

        return new JsonResponse(['message' => 'solution created successfully'], 201);
    }
}