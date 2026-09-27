<?php

namespace App\Entity;

use App\Repository\WaitlistSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Personne qui veut être prévenue de la sortie du prochain drop.
 */
#[ORM\Entity(repositoryClass: WaitlistSubscriberRepository::class)]
class WaitlistSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** Jeton secret du lien de désinscription présent dans chaque e-mail. */
    #[ORM\Column(length: 64, unique: true)]
    private string $unsubscribeToken;

    public function __construct(string $email)
    {
        $this->email = mb_strtolower(trim($email));
        $this->createdAt = new \DateTimeImmutable();
        $this->unsubscribeToken = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUnsubscribeToken(): string
    {
        return $this->unsubscribeToken;
    }
}
