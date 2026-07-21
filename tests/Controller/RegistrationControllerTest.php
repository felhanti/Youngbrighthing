<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationWithValidDataCreatesUserAndRedirectsToLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $email = 'nouveau.client+'.uniqid().'@example.com';

        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'MotDePasse123',
            'registration_form[nom]' => 'Martin',
            'registration_form[prenom]' => 'Alice',
            'registration_form[birthDate]' => '1998-04-12',
            'registration_form[adress]' => '10 rue des Fleurs',
            'registration_form[cp]' => '75011',
            'registration_form[city]' => 'Paris',
            'registration_form[country]' => 'FR',
            'registration_form[agreeTerms]' => true,
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertNotNull($user, 'L\'utilisateur doit être créé en base après une inscription valide.');
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertFalse($user->isVerified(), 'Le compte ne doit pas être vérifié avant le clic sur le lien email.');
    }

    public function testRegistrationWithoutAcceptingTermsFails(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[email]' => 'sans.cgu@example.com',
            'registration_form[plainPassword]' => 'MotDePasse123',
            'registration_form[nom]' => 'Martin',
            'registration_form[prenom]' => 'Alice',
            'registration_form[birthDate]' => '1998-04-12',
            'registration_form[adress]' => '10 rue des Fleurs',
            'registration_form[cp]' => '75011',
            'registration_form[city]' => 'Paris',
            'registration_form[country]' => 'FR',
        ]);
        $form['registration_form[agreeTerms]']->untick();

        $client->submit($form);

        self::assertResponseStatusCodeSame(422);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'sans.cgu@example.com']);
        self::assertNull($user, 'Aucun utilisateur ne doit être créé sans acceptation des CGU.');
    }
}
