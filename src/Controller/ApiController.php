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

class ApiController extends AbstractController
{
    private EntityManagerInterface $entity_manager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
        $this->entity_manager = $entity_manager;
        $this->serializer = $serializer;
    }

    #[Route('/api/users', name: 'get_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $users = $entityManager->getRepository(Users::class)->findAll();

        $json = $serializer->serialize($users, 'json', ['groups' => 'user_read']);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/users', name:'create_user', methods: ['POST'])]
    public function createUser(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['role'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        $user = new Users();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setRole($data['role']);
        $user->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => 'Invalid data', 'errors' => (string) $errors], 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['mesage' => 'User created successfully'], 201);
    }

    #[Route('/api/problems', name: 'get_problems', methods: ['GET'])]
    public function getProblems(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $problems = $entityManager->getRepository(Problems::class)->findAll();
        
        $json = $serializer->serialize($problems, 'json', ['groups' => 'problem_read']);

        return new JsonResponse($json,200, [], true);
    }

    #[Route('/api/problems', name:'create_user', methods: ['POST'])]
    public function createProblem(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id'], $data['title'], $data['description'], $data['latitude'], $data['longitude'], $data['category'], $data['status'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        $user = $entityManager->getRepository(Users::class)->find($data['user_id']);
        if (!$user) {
            return $this->json(['error'=> 'User not found'], 404);
        }

        $problem = new Problems();
        $problem->setUserId($user);
        $problem->setTitle($data['title']);
        $problem->setDescription($data['description']);
        $problem->setLatitude($data['latitude']);
        $problem->setLongitude($data['longitude']);
        $problem->setCategory($data['category']);
        $problem->setStatus($data['status']);
        $problem->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($problem);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => 'Invalid data', 'errors' => (string) $errors], 400);
        }

        $entityManager->persist($problem);
        $entityManager->flush();

        return new JsonResponse(['mesage' => 'Problem created successfully'], 201);
    }
}
