<?php

namespace Codexdelta\App\Entities;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string')]

    private string $name;

    #[ORM\Column(type: 'string')]

    private string $email;
}