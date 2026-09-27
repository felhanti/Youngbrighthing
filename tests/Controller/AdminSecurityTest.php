<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminSecurityTest extends WebTestCase
{
    use EntityFactory;

    public function testUploadingAPhpScriptAsProductImageIsRejected(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $script = tempnam(sys_get_temp_dir(), 'ybt').'.php';
        file_put_contents($script, '<?php echo "pwned";');

        $crawler = $client->request('GET', '/admin/product/new');
        $form = $crawler->filter('form[name="product"]')->form([
            'product[name]' => 'Produit piégé',
            'product[description]' => 'Test',
            'product[price]' => '10',
            'product[size]' => 'M',
            'product[add_date]' => date('Y-m-d'),
        ]);
        $form['product[imageFile]']->upload(new UploadedFile($script, 'shell.php', 'application/x-php', test: true));
        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'JPEG, PNG ou WebP');
        $uploaded = glob(static::getContainer()->getParameter('kernel.project_dir').'/public/images/product/shell*.php');
        self::assertSame([], $uploaded, 'Aucun fichier .php ne doit atterrir dans public/.');
    }

    public function testCustomerWithOrdersCannotBeDeleted(): void
    {
        $client = static::createClient();
        $customer = $this->createUser();
        $this->createOrder($customer, $this->createProduct());
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $crawler = $client->request('GET', '/admin/user/'.$customer->getId());
        $client->submit($crawler->filter('form[action$="/admin/user/'.$customer->getId().'"]')->form());

        self::assertResponseRedirects('/admin/user');
        $this->em()->clear();
        self::assertNotNull($this->em()->find(User::class, $customer->getId()), 'Le client et son historique doivent être conservés.');
    }
}
