<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\WaitlistSubscriber;
use App\Repository\WaitlistSubscriberRepository;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WaitlistTest extends WebTestCase
{
    use EntityFactory;

    private function subscribe(KernelBrowser $client, string $email, string $honeypot = ''): void
    {
        $crawler = $client->request('GET', '/collection/all');
        $form = $crawler->filter('#liste-attente form')->form(['email' => $email, 'website' => $honeypot]);
        $client->submit($form);
    }

    private function repository(): WaitlistSubscriberRepository
    {
        return static::getContainer()->get(WaitlistSubscriberRepository::class);
    }

    public function testVisitorCanJoinTheWaitlistFromAnyPage(): void
    {
        $client = static::createClient();
        $email = 'Fan+'.uniqid().'@Example.com';

        $this->subscribe($client, $email);

        self::assertResponseRedirects();
        self::assertStringStartsWith('/collection/all', $client->getResponse()->headers->get('Location'), 'Retour sur la page d\'origine.');
        self::assertTrue($this->repository()->isSubscribed($email));
        self::assertSame(mb_strtolower($email), $this->repository()->findOneBy(['email' => mb_strtolower($email)])->getEmail());
    }

    public function testSubscribingTwiceKeepsOneEntryAndSameMessage(): void
    {
        $client = static::createClient();
        $email = 'fan+'.uniqid().'@example.com';

        $this->subscribe($client, $email);
        $this->subscribe($client, $email);
        $client->followRedirect();

        self::assertSelectorTextContains('body', 'C\'est noté');
        self::assertSame(1, $this->repository()->count(['email' => $email]));
    }

    public function testBotsFillingTheHoneypotAreIgnored(): void
    {
        $client = static::createClient();
        $email = 'bot+'.uniqid().'@example.com';

        $this->subscribe($client, $email, honeypot: 'https://spam.example');

        self::assertFalse($this->repository()->isSubscribed($email));
    }

    public function testInvalidEmailIsRejected(): void
    {
        $client = static::createClient();

        $this->subscribe($client, 'pas-un-email');
        $client->followRedirect();

        self::assertSelectorTextContains('body', 'ne semble pas valide');
    }

    public function testUnsubscribeLinkAsksConfirmationThenRemoves(): void
    {
        $client = static::createClient();
        $subscriber = new WaitlistSubscriber('partant+'.uniqid().'@example.com');
        $this->em()->persist($subscriber);
        $this->em()->flush();
        $url = '/liste-attente/desinscription/'.$subscriber->getUnsubscribeToken();

        // Ouvrir le lien (ou le pré-chargement d'un webmail) ne désinscrit pas.
        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->repository()->isSubscribed($subscriber->getEmail()));

        $client->submit($crawler->filter('form')->form());
        self::assertFalse($this->repository()->isSubscribed($subscriber->getEmail()));
    }

    public function testAdminAnnouncesADropOnlyOnce(): void
    {
        $client = static::createClient();
        foreach (['a', 'b'] as $suffix) {
            $this->em()->persist(new WaitlistSubscriber('liste-'.$suffix.uniqid().'@example.com'));
        }
        $drop = (new Category())->setName('CAPSULE TEST '.uniqid())->setLast(true);
        $this->em()->persist($drop);
        $this->em()->flush();
        $expected = $this->repository()->count();

        $client->loginUser($this->createUser('ROLE_ADMIN'));
        $crawler = $client->request('GET', '/admin/category/'.$drop->getId());
        $client->submit($crawler->filter('form[action$="/notify-waitlist"]')->form());

        self::assertEmailCount($expected);
        $email = self::getMailerMessage();
        self::assertEmailHtmlBodyContains($email, 'desinscription');
        self::assertTrue($email->getHeaders()->has('List-Unsubscribe'));

        // La fiche n'affiche plus le bouton : pas de second envoi possible.
        $crawler = $client->request('GET', '/admin/category/'.$drop->getId());
        self::assertCount(0, $crawler->filter('form[action$="/notify-waitlist"]'));
    }

    public function testAdminCanExportTheList(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $client->request('GET', '/admin/waitlist/export.csv');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
    }
}
