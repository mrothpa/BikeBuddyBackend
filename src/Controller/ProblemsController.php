<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Problems;
use App\Entity\Solutions;
use App\Entity\Upvotes;
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

    #[Route('/api/problems/{id}', name: 'get_problem_by_id', methods: ['GET'])]
    public function getProblemById(string $id, SerializerInterface $serializer): JsonResponse
    {
        // Find problem with UUID
        $problem = $this->entity_manager->getRepository(Problems::class)->find(Uuid::fromString($id));

        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }

        $json = $serializer->serialize($problem, 'json', ['groups' => 'problem_read']);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/admin/problems', name: 'get_problems_admin', methods: ['POST'])]
    public function getProblemsAdmin(Request $request, SerializerInterface $serializer): JsonResponse
    {
        // Token aus Header extrahieren
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

            $userRole = $parsedToken->get('role');
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        // Nur wenn Rolle "admin" ist, fortfahren
        if ($userRole !== 'admin') {
            return new JsonResponse(['error' => 'Permission denied'], 403);
        }

        // URL-Parameter auslesen
        $category = $request->query->get('category');
        $status = $request->query->get('status');

        $criteria = [];

        if ($category) {
            $criteria['category'] = $category;
        }

        if ($status) {
            $criteria['status'] = $status;
        }

        // Probleme filtern
        $problems = $this->entity_manager->getRepository(Problems::class)->findBy($criteria);
        $json = $serializer->serialize($problems, 'json', ['groups' => 'problem_read_admin']);

        return new JsonResponse($json, 200, [], true);
    }


    #[Route('/api/problems/{id}', name: 'update_problem_status', methods: ['PATCH'])]
    public function updateProblemStatus(string $id, Request $request): JsonResponse
    {
        // Extract Token from Header
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Missing token'], 401);
        }

        $tokenString = substr($authHeader, 7); // remove "Bearer "
        $secretKey = new SymmetricKey(base64_decode(file_get_contents($this->pasetoKeyPath)));

        try {
            $parsedToken = (new Parser())
                ->setKey($secretKey)
                ->setPurpose(Purpose::local())
                ->parse($tokenString);

            $userId = $parsedToken->get('user_id');
            $userRole = $parsedToken->get('role');  // Role from Token
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        // Find user with UUID
        $user = $this->entity_manager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Test, if user has the rights
        if (!in_array($userRole, ['admin', 'city_admin'])) { // Erneut testen, wenn admin oder city_admin vorhanden
            echo $userRole;
            return new JsonResponse(['error' => 'Permission denied'], 403); // if rights aren't there
        }

        // Find problem with UUID
        $problem = $this->entity_manager->getRepository(Problems::class)->find(Uuid::fromString($id));
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }

        // get Status from Request
        $data = json_decode($request->getContent(), true);

        if (!isset($data['status']) || !in_array($data['status'], ['in Bearbeitung', 'erledigt'])) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }

        // Change Status
        $problem->setStatus($data['status']);
        $this->entity_manager->flush();

        return new JsonResponse(['message' => 'Problem status updated successfully'], 200);
    }

    #[Route('/api/problems/{id}', name: 'delete_problem', methods: ['DELETE'])]
    public function deleteProblem(string $id, Request $request): JsonResponse
    {
        // Extract Token from Header
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Missing token'], 401);
        }

        $tokenString = substr($authHeader, 7); // remove "Bearer "
        $secretKey = new SymmetricKey(base64_decode(file_get_contents($this->pasetoKeyPath)));

        try {
            $parsedToken = (new Parser())
                ->setKey($secretKey)
                ->setPurpose(Purpose::local())
                ->parse($tokenString);

            $userId = $parsedToken->get('user_id');
            $userRole = $parsedToken->get('role');  // Role from Token
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        // Find user with UUID
        $user = $this->entity_manager->getRepository(Users::class)->find(Uuid::fromString($userId));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Test, if user has the rights
        if (!in_array($userRole, ['admin', 'city_admin'])) { // Erneut testen, wenn admin oder city_admin vorhanden
            echo $userRole;
            return new JsonResponse(['error' => 'Permission denied'], 403); // if rights aren't there
        }

        // Find problem with UUID
        $problem = $this->entity_manager->getRepository(Problems::class)->find(Uuid::fromString($id));
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }

        // Deleta all upvotes
        $upvotes = $this->entity_manager->getRepository(Upvotes::class)->findBy(['problem' => $problem]);
        foreach ($upvotes as $upvote) {
            $this->entity_manager->remove($upvote);
        }

        // Deleta all solutions
        $solutions = $this->entity_manager->getRepository(Solutions::class)->findBy(['problem' => $problem]);
        foreach ($solutions as $solution) {
            $this->entity_manager->remove($solution);
        }

        // delete problem
        $this->entity_manager->remove($problem);
        $this->entity_manager->flush();

        return new JsonResponse(['message' => 'Problem deleted successfully'], 200);
    }
}