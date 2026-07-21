<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private function createVerifiedUser(string $email, string $plainPassword): User
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setBirthDate(new \DateTime('1990-01-01'));
        $user->setAdress('1 rue de Test');
        $user->setCP('75000');
        $user->setCity('Paris');
        $user->setCountry('France');
        $user->setVerified(true);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function testLoginWithValidCredentialsSucceeds(): void
    {
        $client = static::createClient();
        $email = 'login.ok+'.uniqid().'@example.com';
        $this->createVerifiedUser($email, 'MotDePasse123');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $email,
            '_password' => 'MotDePasse123',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        $client = static::createClient();
        $email = 'login.ko+'.uniqid().'@example.com';
        $this->createVerifiedUser($email, 'MotDePasse123');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $email,
            '_password' => 'MauvaisMotDePasse',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/login');
    }
}
