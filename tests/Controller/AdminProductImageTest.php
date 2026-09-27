<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminProductImageTest extends WebTestCase
{
    use EntityFactory;

    public function testUploadedPhotoIsResizedAndConvertedToWebp(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        // Photo « smartphone » : 3000 px de large.
        $jpeg = sys_get_temp_dir().'/ybt-photo-'.uniqid().'.jpg';
        $image = imagecreatetruecolor(3000, 2000);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 60));
        imagejpeg($image, $jpeg, 95);

        $name = 'Veste photo '.uniqid();
        $crawler = $client->request('GET', '/admin/product/new');
        $form = $crawler->filter('form[name="product"]')->form([
            'product[name]' => $name,
            'product[description]' => 'Test',
            'product[price]' => '10',
            'product[size]' => 'M',
            'product[add_date]' => date('Y-m-d'),
        ]);
        $form['product[imageFile]']->upload(new UploadedFile($jpeg, 'photo.jpg', 'image/jpeg', test: true));
        $client->submit($form);

        self::assertResponseRedirects('/admin/product');
        $product = $this->em()->getRepository(Product::class)->findOneBy(['Name' => $name]);
        self::assertStringEndsWith('.webp', $product->getImageName());

        $path = static::getContainer()->getParameter('kernel.project_dir').'/public/images/product/'.$product->getImageName();
        try {
            self::assertFileExists($path);
            [$width, , $type] = getimagesize($path);
            self::assertSame(IMAGETYPE_WEBP, $type);
            self::assertSame(1600, $width);
            self::assertSame(filesize($path), $product->getImageSize());
        } finally {
            @unlink($path);
        }
    }
}
