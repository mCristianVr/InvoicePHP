<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_account')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private const MANAGEABLE_ROLES = [
        'ROLE_ADMIN',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    public private(set) string $email;

    #[ORM\Column(type: Types::JSON)]
    public private(set) array $roles = ['ROLE_USER'];

    #[ORM\Column(type: Types::STRING)]
    public private(set) string $password;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(string $email, string $password)
    {
        $this->email = strtolower(trim($email));
        $this->password = $password;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @return list<string> */
    public static function manageableRoles(): array
    {
        return self::MANAGEABLE_ROLES;
    }

    /** @param list<string> $roles */
    public function replaceManageableRoles(array $roles): void
    {
        $sanitized = [];

        foreach ($roles as $role) {
            $candidate = strtoupper(trim($role));
            if ($candidate === '' || !in_array($candidate, self::MANAGEABLE_ROLES, true)) {
                continue;
            }

            $sanitized[] = $candidate;
        }

        $this->roles = array_values(array_unique($sanitized));
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }

    public function getSalt(): ?string
    {
        return null;
    }
}
