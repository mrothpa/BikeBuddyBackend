<?php

namespace App\Controller;

use App\Entity\Problems;
use App\Entity\Upvotes;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Protocol\Version4;
use ParagonIE\Paseto\Keys\SymmetricKey;
use ParagonIE\Paseto\ProtocolCollection;
use ParagonIE\Paseto\Purpose;

class UpvotesController extends AbstractController
{
    private string $pasetoKeyPath = '/../../config/paseto_key.txt';

    public function __construct() {
        $this->pasetoKeyPath = realpath(__DIR__ . '/../../config/paseto_key.txt');
    }

    #[Route('/api/problems/{id}/upvote', name: "api_problem_upvote", methods: ["POST"])]
    public function upvoteProblem(string $id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator): JsonResponse
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
        $user = $entityManager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Find problem
        $problem = $entityManager->getRepository(Problems::class)->find(Uuid::fromString($id));
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }

        // check if user already upvoted problem
        $existingUpvote = $entityManager->getRepository(Upvotes::class)->findOneBy([
            'user' => $user,
            'problem' => $problem,
        ]);

        if ($existingUpvote) {
            return new JsonResponse(['error' => 'Problem already upvoted'], Response::HTTP_CONFLICT);
        }

        // make upvote
        $upvote = new Upvotes();
        $upvote->setUser($user);
        $upvote->setProblem($problem);
        $upvote->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($upvote);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => (string) $errors], 400);
        }

        $entityManager->persist($upvote);
        $entityManager->flush();

        // Upvote-Zähler im Problem erhöhen (optional)
        $problem->setUpvotesInt($problem->getUpvotesInt() + 1);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Problem upvoted successfully'], Response::HTTP_CREATED);
    }

    #[Route('/api/problems/{id}/downvote', name: "api_problem_downvote", methods: ["DELETE"])]
    public function downvoteProblem(string $id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator): JsonResponse
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
        $user = $entityManager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Find problem
        $problem = $entityManager->getRepository(Problems::class)->find(Uuid::fromString($id));
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }

        // check if user already upvoted problem
        $existingUpvote = $entityManager->getRepository(Upvotes::class)->findOneBy([
            'user' => $user,
            'problem' => $problem,
        ]);

        if (!$existingUpvote) {
            return new JsonResponse(['error' => 'Upvote not found'], Response::HTTP_NOT_FOUND);
        }

        // make upvote
        $upvote = new Upvotes();
        $upvote->setUser($user);
        $upvote->setProblem($problem);
        $upvote->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($upvote);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => (string) $errors], 400);
        }

        // remove upvote
        $entityManager->remove($existingUpvote);
        $entityManager->flush();

        // Upvote-Zähler im Problem verringern (optional)
        $problem->setUpvotesInt($problem->getUpvotesInt() - 1);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Problem removed successfully'], Response::HTTP_OK);
    }

    #[Route('/api/problems/{id}/upvoted', name: 'api_problem_upvoted', methods: ['GET'])]
    public function checkUpvoteStatus(string $id, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        // Token aus dem Authorization-Header extrahieren
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Missing token'], 401);
        }

        $tokenString = substr($authHeader, 7);
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

        // Nutzer laden
        $user = $entityManager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Problem laden
        $problem = $entityManager->getRepository(Problems::class)->find(Uuid::fromString($id));
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }

        // Prüfung, ob ein Upvote existiert
        $existingUpvote = $entityManager->getRepository(Upvotes::class)->findOneBy([
            'user' => $user,
            'problem' => $problem,
        ]);

        return new JsonResponse(['upvoted' => $existingUpvote !== null], Response::HTTP_OK);
    }
}