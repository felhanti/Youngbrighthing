<?php

namespace App\Exception;

use App\Entity\Product;

/**
 * Une ou plusieurs pièces uniques du panier ont été vendues entre-temps.
 */
final class ProductsUnavailableException extends \RuntimeException
{
    /**
     * @param Product[] $products
     */
    public function __construct(private readonly array $products)
    {
        parent::__construct(sprintf(
            'Désolé, ces pièces viennent d\'être vendues et ont été retirées de votre panier : %s',
            implode(', ', array_map(static fn (Product $p) => $p->getName(), $products)),
        ));
    }

    /** @return Product[] */
    public function getProducts(): array
    {
        return $this->products;
    }
}
