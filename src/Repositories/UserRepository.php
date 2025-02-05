<?php

namespace Codexdelta\App\Repositories;

use Codexdelta\App\Entities\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class UserRepository
{
    protected EntityManager $entityManager;
    protected EntityRepository $userRepository;

    protected function __construct()
    {
        $this->entityManager = application()->getEntityManager();
        $this->userRepository =  $this->entityManager->getRepository(User::class);
    }

    public static function get()
    {
        return new self;
    }

    /**
     * @return array
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * @param User $user
     * @return void
     */
    public function save(User $user): void
    {
        $this->userRepository->getEntityManager()->persist($user);
    }
}