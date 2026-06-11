<?php

namespace App\Security;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Politique de mot de passe unique pour toute l'application
 * (inscription, invitation, changement de mot de passe).
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /**
     * @return \Symfony\Component\Validator\Constraint[]
     */
    public static function constraints(): array
    {
        return [
            new Assert\NotBlank(message: 'Le mot de passe est requis'),
            new Assert\Length(
                min: self::MIN_LENGTH,
                minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères',
            ),
            new Assert\PasswordStrength(
                minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
                message: 'Ce mot de passe est trop prévisible : mélangez majuscules, minuscules, chiffres et symboles.',
            ),
        ];
    }
}
