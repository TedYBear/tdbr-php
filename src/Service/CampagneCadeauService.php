<?php

namespace App\Service;

use App\Entity\CodeReduction;
use App\Entity\Commande;
use App\Entity\User;
use App\Repository\CampagneCadeauRepository;
use App\Repository\CodeReductionRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Évalue toutes les campagnes codes cadeaux actives en simultané après paiement d'une commande
 * et génère / envoie les bons d'achat correspondants.
 */
class CampagneCadeauService
{
    public function __construct(
        private CampagneCadeauRepository $campagneRepo,
        private CodeReductionRepository $codeReductionRepo,
        private CommandeRepository $commandeRepo,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
    ) {
    }

    /**
     * Traite une commande payée contre l'ensemble des campagnes en cours.
     * Retourne le nombre de bons d'achat générés.
     */
    public function processOrder(Commande $commande, ?User $fallbackUser = null): int
    {
        $campagnes = $this->campagneRepo->findEnCours();
        if (empty($campagnes)) {
            return 0;
        }

        $clientEmail = strtolower(trim((string) ($commande->getClient()['email'] ?? '')));
        if ($clientEmail === '') {
            return 0;
        }

        $paidCount = $this->commandeRepo->countPaidByEmail($clientEmail);
        $orderTotal = $commande->getTotal();
        $targetUser = $commande->getUser() ?? $fallbackUser;
        $generated = 0;

        foreach ($campagnes as $campagne) {
            if (!$campagne->isEnCours()) {
                continue;
            }

            if (!$campagne->matchesOrder($orderTotal, $paidCount)) {
                continue;
            }

            if ($campagne->isUneFoisParClient() && $this->codeReductionRepo->hasForCampagneAndEmail($campagne, $clientEmail)) {
                continue;
            }

            $maxBenef = $campagne->getMaxBeneficiaires();
            if ($maxBenef !== null && $this->codeReductionRepo->countByCampagne($campagne) >= $maxBenef) {
                continue;
            }

            $montant = $campagne->computeAmount($orderTotal);
            if ($montant < 0.01) {
                continue;
            }

            $dateExpiration = $campagne->computeExpirationDate();
            $codeStr = $campagne->getCodeNom() ?? ('CADEAU-' . strtoupper(bin2hex(random_bytes(4))));

            $giftCode = new CodeReduction();
            $giftCode->setCode($codeStr);
            $giftCode->setMontant($montant);
            $giftCode->setStatut('actif');
            $giftCode->setDateExpiration($dateExpiration);
            $giftCode->setIsCampaignGift(true);
            $giftCode->setCampagne($campagne);
            $giftCode->setRecipientEmail($clientEmail);
            if ($targetUser instanceof User) {
                $giftCode->setUser($targetUser);
            }

            $this->em->persist($giftCode);
            $this->em->flush();

            try {
                $this->mailerService->sendGiftCode(
                    $clientEmail,
                    $giftCode->getCode(),
                    $montant,
                    $campagne->getTypeReduction(),
                    $dateExpiration,
                    $campagne->getNom()
                );
            } catch (\Throwable $e) {
                // L'échec d'envoi d'email ne bloque pas les autres campagnes
            }

            $generated++;
        }

        return $generated;
    }
}
