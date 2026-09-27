<?php

namespace App\Tests\Controller;

use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    use EntityFactory;

    /** Ajoute au panier comme le fait le navigateur : jeton lu dans la page produit. */
    private function addToCart(KernelBrowser $client, int $productId, ?string $token = null): void
    {
        if (null === $token) {
            $crawler = $client->request('GET', '/product/'.$productId);
            $token = $crawler->filter('meta[name="csrf-cart"]')->attr('content');
        }

        $client->request('POST', '/cart/add/'.$productId, server: ['HTTP_X_CSRF_TOKEN' => $token]);
    }

    public function testAddingAnAvailableProductToCartSucceeds(): void
    {
        $client = static::createClient();
        $product = $this->createProduct();
        $client->loginUser($this->createUser());

        $this->addToCart($client, $product->getId());

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertSame(1, $data['cartCount']);
    }

    public function testAddingAnAlreadySoldProductIsRejected(): void
    {
        $client = static::createClient();
        $product = $this->createProduct(available: false);
        $client->loginUser($this->createUser());

        $this->addToCart($client, $product->getId());

        self::assertResponseStatusCodeSame(400);
        self::assertFalse(json_decode($client->getResponse()->getContent(), true)['success']);
    }

    public function testAddingWithoutValidCsrfTokenIsRejected(): void
    {
        $client = static::createClient();
        $product = $this->createProduct();
        $client->loginUser($this->createUser());

        $this->addToCart($client, $product->getId(), token: 'jeton-invalide');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousUserCannotAddToCart(): void
    {
        $client = static::createClient();
        $product = $this->createProduct();

        $client->request('POST', '/cart/add/'.$product->getId());

        self::assertResponseRedirects('/login');
    }

    public function testCartPageWorksForUserWithoutCart(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser());

        $client->request('GET', '/cart');

        self::assertResponseIsSuccessful();
    }

    public function testClearingTheCartRequiresPost(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser());

        $client->request('GET', '/cart/clear');

        self::assertResponseStatusCodeSame(405);
    }
}
