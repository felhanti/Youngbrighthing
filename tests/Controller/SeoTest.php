<?php

namespace App\Tests\Controller;

use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SeoTest extends WebTestCase
{
    use EntityFactory;

    public function testRobotsHidesPrivatePagesAndPointsToSitemap(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertStringContainsString('Disallow: /admin', $body);
        self::assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $body);
    }

    public function testSitemapListsEveryProduct(): void
    {
        $client = static::createClient();
        $product = $this->createProduct();

        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        $xml = simplexml_load_string($client->getResponse()->getContent());
        self::assertNotFalse($xml, 'Le sitemap doit être un XML valide.');
        self::assertStringContainsString('/product/'.$product->getId().'</loc>', $client->getResponse()->getContent());
    }
}
