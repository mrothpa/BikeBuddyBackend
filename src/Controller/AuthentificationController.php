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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Protocol\Version4;
use ParagonIE\Paseto\Keys\SymmetricKey;
use ParagonIE\Paseto\Purpose;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthentificationController extends AbstractController
{
    private EntityManagerInterface $entity_manager;
    private SerializerInterface $serializer;
    private string $pasetoKeyPath = 'config/paseto_key.txt';

    public function __construct(EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
        $this->entity_manager = $entity_manager;
        $this->serializer = $serializer;
        $this->pasetoKeyPath = realpath(__DIR__ . '/../../config/paseto_key.txt');
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

    #[Route('/api/login', name:'login_user', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error'=> 'Missing Credentials'], 400);
        }

        // Search User in Database
        $user = $entityManager->getRepository(Users::class)->findOneBy(['email' => $data['email']]);
        if(!$user) {
            return new JsonResponse(['error' => 'Invalid Username'], 401);
        }

        if (!password_verify($data['password'], $user->getPassword())) {
            return new JsonResponse(['error' => 'Invalid Password'], 401);
        }

        // Paseto load key
        if (!file_exists($this->pasetoKeyPath)) {
            return new JsonResponse(['error' => 'Not found Paseto-Key'], 500);
        }

        $secretKey = new SymmetricKey(base64_decode(file_get_contents($this->pasetoKeyPath)));

        // Token generate
        $token = (new Builder())
            ->setKey($secretKey) // Schlüssel setzen
            ->setVersion(new Version4()) // Paseto Version 4 verwenden
            ->setPurpose(Purpose::local())
            ->setExpiration((new \DateTime())->modify('+1 hour')) // Ablaufzeit (1 Stunde)
            ->setIssuedAt() // Erstellungszeitpunkt setzen
            ->setNotBefore() // Token ist sofort gültig
            ->setSubject('user-auth') // Token-Subjekt
            ->set('user_id', $user->getId()) // Benutzer-ID als Claim setzen
            ->set('role', $user->getRole()) // Benutzerrolle setzen
            ->toString(); // Token als String generieren

        return new JsonResponse(['token' => $token]);
    }
}

// use Symfony\Component\Uid\Uuid;

// public function findUserById(string $id): ?Users
// {
//     return $this->find(Uuid::fromString($id));
// }