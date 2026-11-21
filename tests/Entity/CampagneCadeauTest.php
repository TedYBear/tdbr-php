<?php

namespace App\Tests\Entity;

use App\Entity\CampagneCadeau;
use PHPUnit\Framework\TestCase;

class CampagneCadeauTest extends TestCase
{
    public function testTriggers(): void
    {
        $premiere = (new CampagneCadeau())->setNom('Bienvenue')->setDeclencheur(CampagneCadeau::DECLENCHEUR_PREMIERE_COMMANDE);
        $this->assertTrue($premiere->matchesOrder(10.0, 1));
        $this->assertFalse($premiere->matchesOrder(10.0, 2));

        $toutes = (new CampagneCadeau())->setNom('Noël')->setDeclencheur(CampagneCadeau::DECLENCHEUR_TOUTES_COMMANDES);
        $this->assertTrue($toutes->matchesOrder(10.0, 1));
        $this->assertTrue($toutes->matchesOrder(10.0, 5));

        $montant = (new CampagneCadeau())->setNom('Panier 50+')->setDeclencheur(CampagneCadeau::DECLENCHEUR_MONTANT_MIN)->setMontantMinimum(50.0);
        $this->assertFalse($montant->matchesOrder(49.99, 1));
        $this->assertTrue($montant->matchesOrder(50.0, 1));
        $this->assertTrue($montant->matchesOrder(80.0, 3));

        $nbCmd = (new CampagneCadeau())->setNom('Fidélité')->setDeclencheur(CampagneCadeau::DECLENCHEUR_NOMBRE_COMMANDES)->setNombreCommandes(3);
        $this->assertFalse($nbCmd->matchesOrder(25.0, 2));
        $this->assertTrue($nbCmd->matchesOrder(25.0, 3));
        $this->assertTrue($nbCmd->matchesOrder(25.0, 4));
    }

    public function testAmountComputationAndFiftyCentsRounding(): void
    {
        $fixe = (new CampagneCadeau())->setNom('Fixe')->setTypeReduction('fixe')->setValeur(7.5);
        $this->assertSame(7.5, $fixe->computeAmount(40.0));

        $pctArrondi = (new CampagneCadeau())->setNom('Noël')->setTypeReduction('pourcentage')->setValeur(5.0)->setArrondi50Centimes(true);
        $this->assertSame(1.0, $pctArrondi->computeAmount(20.0));
        $this->assertSame(1.5, $pctArrondi->computeAmount(21.0));
        $this->assertSame(2.0, $pctArrondi->computeAmount(35.0));
        $this->assertSame(2.5, $pctArrondi->computeAmount(49.90));

        $pctExact = (new CampagneCadeau())->setNom('Exact')->setTypeReduction('pourcentage')->setValeur(5.0)->setArrondi50Centimes(false);
        $this->assertSame(1.05, $pctExact->computeAmount(21.0));
    }

    public function testValidityModesAndActivationEndDate(): void
    {
        $now = new \DateTimeImmutable('2026-12-01 10:00:00');

        $c6 = (new CampagneCadeau())->setNom('6m')->setTypeValidite(CampagneCadeau::VALIDITE_DUREE_6_MOIS);
        $this->assertSame('2027-06-01', $c6->computeExpirationDate($now)?->format('Y-m-d'));

        $c12 = (new CampagneCadeau())->setNom('12m')->setTypeValidite(CampagneCadeau::VALIDITE_DUREE_12_MOIS);
        $this->assertSame('2027-12-01', $c12->computeExpirationDate($now)?->format('Y-m-d'));

        $fixeDate = new \DateTimeImmutable('2027-06-30 23:59:59');
        $cFixe = (new CampagneCadeau())->setNom('Fixe')->setTypeValidite(CampagneCadeau::VALIDITE_DATE_FIXE)->setDateFinValiditeFixe($fixeDate);
        $this->assertSame('2027-06-30', $cFixe->computeExpirationDate($now)?->format('Y-m-d'));

        $cAucune = (new CampagneCadeau())->setNom('Aucune')->setTypeValidite(CampagneCadeau::VALIDITE_AUCUNE);
        $this->assertNull($cAucune->computeExpirationDate($now));

        $cFin = (new CampagneCadeau())->setNom('Noël')->setActif(true)->setDateFinActivation(new \DateTimeImmutable('2027-01-05 23:59:59'));
        $this->assertTrue($cFin->isEnCours(new \DateTimeImmutable('2027-01-05 23:59:59')));
        $this->assertFalse($cFin->isEnCours(new \DateTimeImmutable('2027-01-06 00:00:00')));
    }
}
