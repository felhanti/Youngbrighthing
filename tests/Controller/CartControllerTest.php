<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    private function createUser(EntityManagerInterface $entityManager): User
    {
        $user = new User();
        $user->setEmail('cart.user+'.uniqid().'@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('irrelevant-for-this-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setBirthDate(new \DateTime('1990-01-01'));
        $user->setAdress('1 rue de Test');
        $user->setCP('75000');
        $user->setCity('Paris');
        $user->setCountry('France');
        $user->setVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createProduct(EntityManagerInterface $entityManager, bool $available): Product
    {
        $product = new Product();
        $product->setName('Produit de test '.uniqid());
        $product->setDescription('Pièce unique de test.');
        $product->setPrice('50.00');
        $product->setIssold($available);
        $product->setSize('M');

        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }

    public function testAddingAnAvailableProductToCartSucceeds(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser($entityManager);
        $product = $this->createProduct($entityManager, true);

        $client->loginUser($user);
        $client->request('POST', '/cart/cart/add/'.$product->getId());

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
    }

    public function testAddingAnAlreadySoldProductIsRejected(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser($entityManager);
        $product = $this->createProduct($entityManager, false);

        $client->loginUser($user);
        $client->request('POST', '/cart/cart/add/'.$product->getId());

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($data['success']);
    }

    public function testAnonymousUserCannotAddToCart(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $product = $this->createProduct($entityManager, true);

        $client->request('POST', '/cart/cart/add/'.$product->getId());

        self::assertResponseRedirects('/login');
    }
}
