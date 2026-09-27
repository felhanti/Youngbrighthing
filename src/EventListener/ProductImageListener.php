<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Service\ImageOptimizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

/**
 * Chaque photo produit envoyée depuis l'admin est aussitôt redimensionnée et convertie en WebP.
 */
#[AsEventListener(event: Events::POST_UPLOAD)]
final class ProductImageListener
{
    public function __construct(
        private readonly ImageOptimizer $optimizer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Event $event): void
    {
        $product = $event->getObject();
        if (!$product instanceof Product || !$product->getImageName()) {
            return;
        }

        $directory = $event->getMapping()->getUploadDestination().'/'.$event->getMapping()->getUploadDir($product);
        $path = rtrim($directory, '/').'/'.$product->getImageName();

        try {
            $webp = $this->optimizer->toWebp($path);
        } catch (\RuntimeException $e) {
            // On garde l'original plutôt que de perdre la photo.
            $this->logger->warning('Optimisation de la photo produit impossible.', ['error' => $e->getMessage()]);

            return;
        }

        $product->setImageName(basename($webp));
        $product->setImageSize(filesize($webp) ?: null);
    }
}
