<?php

namespace App\Command;

use App\Repository\ProductRepository;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Convertit en WebP les photos produits envoyées avant l'optimisation automatique.
 * Sans risque à relancer : les photos déjà en WebP sont ignorées.
 */
#[AsCommand(name: 'app:images:optimize', description: 'Redimensionne et convertit en WebP les photos produits existantes')]
class OptimizeProductImagesCommand extends Command
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ImageOptimizer $optimizer,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%/public/images/product')]
        private readonly string $directory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $before = $after = 0;
        // Une même photo peut servir à plusieurs produits : on ne la convertit qu'une fois.
        $converted = [];

        foreach ($this->products->findAll() as $product) {
            $name = $product->getImageName();
            if (!$name || str_ends_with(strtolower($name), '.webp')) {
                continue;
            }

            $path = $this->directory.'/'.$name;
            if (!isset($converted[$name])) {
                if (!is_file($path)) {
                    $io->warning(sprintf('Fichier absent pour « %s » : %s', $product->getName(), $name));
                    continue;
                }
                $size = filesize($path);
                $webp = $this->optimizer->toWebp($path);
                $converted[$name] = basename($webp);
                $before += $size;
                $after += filesize($webp);
            }

            $product->setImageName($converted[$name]);
            $product->setImageSize(filesize($this->directory.'/'.$converted[$name]) ?: null);
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            '%d photo(s) converties : %s Ko → %s Ko.',
            count($converted),
            number_format($before / 1024, 0, ',', ' '),
            number_format($after / 1024, 0, ',', ' '),
        ));

        return Command::SUCCESS;
    }
}
