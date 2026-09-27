<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ouvre chaque page du site pour détecter un template ou une requête cassée.
 */
class PagesSmokeTest extends WebTestCase
{
    use EntityFactory;

    public function testPublicPagesRender(): void
    {
        $client = static::createClient();
        $product = $this->createProduct();
        $category = (new Category())->setName('Capsule test '.uniqid())->setLast(false);
        $product->addCategory($category);
        $this->em()->persist($category);
        $this->em()->flush();

        $urls = ['/', '/collection/all', '/campagne', '/histoire', '/product/'.$product->getId(),
            '/drop/'.rawurlencode($category->getName()), '/login', '/register', '/reset-password',
            '/cgv/cgu', '/cgv/confidentialite', '/cgv/mentions-legales', '/cgv/retour'];

        foreach ($urls as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }

        $client->request('GET', '/product/999999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testCustomerPagesRender(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $order = $this->createOrder($user, $this->createProduct());
        $client->loginUser($user);

        foreach (['/account', '/cart', '/orders', '/order/summary/'.$order->getId()] as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }
    }

    public function testAdminPagesRender(): void
    {
        $client = static::createClient();
        $customer = $this->createUser();
        $product = $this->createProduct();
        $order = $this->createOrder($customer, $product);
        $category = (new Category())->setName('Capsule admin '.uniqid())->setLast(false);
        $this->em()->persist($category);
        $this->em()->flush();

        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $urls = ['/admin/dashboard',
            '/admin/product', '/admin/product/new', '/admin/product/'.$product->getId(), '/admin/product/'.$product->getId().'/edit',
            '/admin/category', '/admin/category/new', '/admin/category/'.$category->getId(), '/admin/category/'.$category->getId().'/edit',
            '/admin/user', '/admin/user/new', '/admin/user/'.$customer->getId(), '/admin/user/'.$customer->getId().'/edit',
            '/admin/order', '/admin/order/'.$order->getId()];

        foreach ($urls as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }
    }

    public function testAdminIsForbiddenToCustomers(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser());

        $client->request('GET', '/admin/dashboard');

        self::assertResponseStatusCodeSame(403);
    }
}
