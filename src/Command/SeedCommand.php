<?php

namespace App\Command;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed', description: 'Seed test data')]
class SeedCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Wipe existing data
        $this->em->getConnection()->executeStatement('DELETE FROM order_item');
        $this->em->getConnection()->executeStatement('DELETE FROM "order"');
        $this->em->getConnection()->executeStatement('DELETE FROM cart_product');
        $this->em->getConnection()->executeStatement('DELETE FROM cart');
        $this->em->getConnection()->executeStatement('DELETE FROM product_category');
        $this->em->getConnection()->executeStatement('DELETE FROM product');
        $this->em->getConnection()->executeStatement('DELETE FROM category');
        $this->em->getConnection()->executeStatement('DELETE FROM reset_password_request');
        $this->em->getConnection()->executeStatement('DELETE FROM user');

        // Admin
        $admin = new User();
        $admin->setEmail('admin@youngbrighthing.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $admin->setNom('Admin');
        $admin->setPrenom('Admin');
        $admin->setBirthDate(new \DateTime('1990-01-01'));
        $admin->setAdress('1 rue de la Paix');
        $admin->setCP('75001');
        $admin->setCity('Paris');
        $admin->setCountry('France');
        $admin->setVerified(true);
        $this->em->persist($admin);

        // User
        $user = new User();
        $user->setEmail('user@youngbrighthing.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'user'));
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setBirthDate(new \DateTime('1995-06-15'));
        $user->setAdress('12 avenue Montaigne');
        $user->setCP('75008');
        $user->setCity('Paris');
        $user->setCountry('France');
        $user->setVerified(true);
        $this->em->persist($user);

        // Cart for user
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setDate(new \DateTime());
        $this->em->persist($cart);

        // Categories
        $capsule3 = new Category();
        $capsule3->setName('CAPSULE III');
        $capsule3->setLast(true);
        $this->em->persist($capsule3);

        $capsule2 = new Category();
        $capsule2->setName('CAPSULE II');
        $capsule2->setLast(false);
        $this->em->persist($capsule2);

        $capsule1 = new Category();
        $capsule1->setName('CAPSULE I');
        $capsule1->setLast(false);
        $this->em->persist($capsule1);

        // Products
        $products = [
            ['Veste Déconstruite YBT-01', 'Veste unisexe en laine ajourée déconstruite, inspirée d\'un univers sans limites. Coupe oversize, finitions brutes et coutures apparentes. Pièce unique.', '320.00', true, 'M', $capsule3, 'turtleneckls-6728e248a036a358806097.jpg'],
            ['Hoodie Recyclé YBT-02', 'Hoodie en tissu recyclé mélangé, motifs brodés à la main. Style urbain brut avec capuche double épaisseur. Coloris anthracite.', '195.00', true, 'L', $capsule3, 'pullchemiseclubface2-6728ddb6ae960563351418.jpg'],
            ['Pantalon Maille YBT-03', 'Pantalon en maille déchirée, taille haute élastiquée. Coupe unisexe adaptée à toutes les morphologies. Noir intense.', '240.00', true, 'S', $capsule3, 'pullchemiseclubface2-6728ddb6ae960563351418-674eeeb14addf541848998.jpg'],
            ['T-shirt Coton Mélangé YBT-04', 'T-shirt en coton mélangé traité, col légèrement asymétrique. Logo YBT brodé en fil doré sur la poitrine. Blanc cassé.', '120.00', false, 'XL', $capsule2, 'mixed-denim-blue-sky-face-21674478683047-6728ddab6e7d5990242588.jpg'],
            ['Manteau Urban YBT-05', 'Manteau long en laine recyclée, doublure amovible. Poches intérieures multiples. Style militaire revisité. Kaki.', '480.00', true, 'M', $capsule2, 'img-20240208-162755-587-6728dd9f291ee420083656.png'],
            ['Robe Asymétrique YBT-06', 'Robe mi-longue en jersey déstructuré. Ourlet asymétrique et encolure béteau. Pièce unisexe à porter en toute occasion.', '280.00', true, 'S/M', $capsule1, 'img-20240208-162730-611-6728e1efc0794076900155.png'],
        ];

        foreach ($products as [$name, $desc, $price, $sold, $size, $cat, $image]) {
            $p = new Product();
            $p->setName($name);
            $p->setDescription($desc);
            $p->setPrice($price);
            $p->setIsSold($sold);
            $p->setSize($size);
            $p->setImageName($image);
            $p->addCategory($cat);
            $this->em->persist($p);
        }

        $this->em->flush();

        $output->writeln('<info>Seed OK !</info>');
        $output->writeln('  admin@youngbrighthing.com / admin');
        $output->writeln('  user@youngbrighthing.com  / user');
        $output->writeln('  6 produits créés, 3 catégories');

        return Command::SUCCESS;
    }
}
