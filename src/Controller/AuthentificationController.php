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

class AuthentificationController extends AbstractController
{
    private EntityManagerInterface $entity_manager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
        $this->entity_manager = $entity_manager;
        $this->serializer = $serializer;
    }

    #[Route('/api/signup', name:'signup_user', methods: ['POST'])]
    public function createUser(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        $user = new Users();
        // $user->setId(Uuid::v4());
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setRole('user'); // Standard Wert ist User, Admins müssen manuell hinzugefügt werden
        $user->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => 'Invalid data', 'errors' => (string) $errors], 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['mesage' => 'User created successfully'], 201);
    }
}

// use Symfony\Component\Uid\Uuid;

// public function findUserById(string $id): ?Users
// {
//     return $this->find(Uuid::fromString($id));
// }