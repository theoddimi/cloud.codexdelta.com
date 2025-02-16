<?php

namespace Codexdelta\App\Repositories;

use Codexdelta\App\Entities\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    protected EntityManager $entityManager;
    protected EntityRepository $userRepository;

    public function __construct()
    {
        $this->entityManager = application()->getEntityManager();
        $this->userRepository =  $this->entityManager->getRepository(User::class);
        parent::__construct($this->entityManager, $this->entityManager->getClassMetadata(User::class));
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