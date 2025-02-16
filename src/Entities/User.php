<?php

namespace Codexdelta\App\Entities;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string')]

    private string $name;

    #[ORM\Column(type: 'string')]

    private string $email;

    #[ORM\Column(type: 'string')]

    private string $password;

    #[ORM\Column(type: 'datetime')]

    private string $email_verified_at;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEmailVerifiedAt(): string
    {
        return $this->email_verified_at;
    }

    public function setEmailVerifiedAt(string $email_verified_at): void
    {
        $this->email_verified_at = $email_verified_at;
    }
}