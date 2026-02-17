<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\ArticleImage;
use App\Entity\Caracteristique;
use App\Entity\CaracteristiqueValeur;
use App\Entity\Category;
use App\Entity\Commande;
use App\Entity\Message;
use App\Entity\ProductCollection;
use App\Entity\User;
use App\Entity\Variante;
use App\Entity\VarianteTemplate;
use App\Service\MongoDBService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-mongo-to-mysql',
    description: 'Migre les données MongoDB vers MySQL',
)]
class MigrateMongoToMysqlCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private MongoDBService $mongo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans écrire en base')
            ->addOption('only', null, InputOption::VALUE_REQUIRED, 'Migrer seulement: categories|collections|articles|commandes|messages|users|caracteristiques|templates')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $only = $input->getOption('only');

        if ($dryRun) {
            $io->warning('Mode DRY-RUN : aucune donnée ne sera écrite');
        }

        $io->title('Migration MongoDB → MySQL');

        $steps = $only ? [$only] : ['categories', 'collections', 'articles', 'commandes', 'messages', 'users', 'caracteristiques', 'templates'];

        foreach ($steps as $step) {
            match ($step) {
                'categories' => $this->migrateCategories($io, $dryRun),
                'collections' => $this->migrateCollections($io, $dryRun),
                'articles' => $this->migrateArticles($io, $dryRun),
                'commandes' => $this->migrateCommandes($io, $dryRun),
                'messages' => $this->migrateMessages($io, $dryRun),
                'users' => $this->migrateUsers($io, $dryRun),
                'caracteristiques' => $this->migrateCaracteristiques($io, $dryRun),
                'templates' => $this->migrateTemplates($io, $dryRun),
                default => $io->error("Étape inconnue: $step"),
            };
        }

        $io->success('Migration terminée !');
        return Command::SUCCESS;
    }

    private function migrateCategories(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Catégories');
        $collection = $this->mongo->getCollection('categories');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $cat = new Category();
            $cat->setNom((string)($doc['nom'] ?? ''));
            $cat->setSlug((string)($doc['slug'] ?? $this->slugify($doc['nom'] ?? '')));
            $cat->setDescription(isset($doc['description']) ? (string)$doc['description'] : null);
            $cat->setImage(isset($doc['image']) ? (string)$doc['image'] : null);
            $cat->setOrdre((int)($doc['ordre'] ?? 0));
            $cat->setActif((bool)($doc['actif'] ?? true));

            if (!$dryRun) {
                $this->em->persist($cat);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(Category::class);
        }

        $io->success("$count catégorie(s) migrée(s)");
    }

    private function migrateCollections(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Collections');
        $collection = $this->mongo->getCollection('collections');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $coll = new ProductCollection();
            $coll->setNom((string)($doc['nom'] ?? ''));
            $coll->setSlug((string)($doc['slug'] ?? $this->slugify($doc['nom'] ?? '')));
            $coll->setDescription(isset($doc['description']) ? (string)$doc['description'] : null);
            $coll->setImage(isset($doc['image']) ? (string)$doc['image'] : null);
            $coll->setOrdre((int)($doc['ordre'] ?? 0));
            $coll->setActif((bool)($doc['actif'] ?? true));

            // Lier à la catégorie par slug
            if (isset($doc['categorieSlug'])) {
                $cat = $this->em->getRepository(Category::class)->findOneBy(['slug' => (string)$doc['categorieSlug']]);
                if ($cat) $coll->setCategorie($cat);
            } elseif (isset($doc['categorie'])) {
                $cat = $this->em->getRepository(Category::class)->findOneBy(['nom' => (string)$doc['categorie']]);
                if ($cat) $coll->setCategorie($cat);
            }

            if (!$dryRun) {
                $this->em->persist($coll);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(ProductCollection::class);
        }

        $io->success("$count collection(s) migrée(s)");
    }

    private function migrateArticles(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Articles');
        $collection = $this->mongo->getCollection('articles');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $article = new Article();
            $article->setNom((string)($doc['nom'] ?? ''));
            $article->setSlug((string)($doc['slug'] ?? $this->slugify($doc['nom'] ?? '')));
            $article->setDescription(isset($doc['description']) ? (string)$doc['description'] : null);
            $article->setPrixBase((float)($doc['prixBase'] ?? $doc['prix'] ?? 0));
            $article->setActif((bool)($doc['actif'] ?? true));
            $article->setEnVedette((bool)($doc['enVedette'] ?? false));
            $article->setPersonnalisable((bool)($doc['personnalisable'] ?? false));
            $article->setOrdre((int)($doc['ordre'] ?? 0));

            // Lier à la collection par slug
            if (isset($doc['collectionSlug'])) {
                $coll = $this->em->getRepository(ProductCollection::class)->findOneBy(['slug' => (string)$doc['collectionSlug']]);
                if ($coll) $article->setCollection($coll);
            } elseif (isset($doc['collection'])) {
                $coll = $this->em->getRepository(ProductCollection::class)->findOneBy(['nom' => (string)$doc['collection']]);
                if ($coll) $article->setCollection($coll);
            }

            // Images
            $images = $doc['images'] ?? [];
            $ordre = 0;
            foreach ($images as $imgData) {
                $img = new ArticleImage();
                $url = is_array($imgData) ? ($imgData['url'] ?? $imgData) : (string)$imgData;
                $img->setUrl((string)$url);
                $img->setAlt(is_array($imgData) ? ($imgData['alt'] ?? $article->getNom()) : $article->getNom());
                $img->setOrdre($ordre++);
                $article->addImage($img);
            }

            // Variantes
            $variantes = $doc['variantes'] ?? [];
            foreach ($variantes as $varData) {
                $v = new Variante();
                $v->setNom((string)($varData['nom'] ?? 'Standard'));
                $v->setSku(isset($varData['sku']) ? (string)$varData['sku'] : null);
                $v->setPrix(isset($varData['prix']) ? (float)$varData['prix'] : null);
                $v->setStock((int)($varData['stock'] ?? 0));
                $v->setActif((bool)($varData['actif'] ?? true));
                $article->addVariante($v);
            }

            if (!$dryRun) {
                $this->em->persist($article);
            }
            $count++;

            // Flush par batch de 50
            if ($count % 50 === 0 && !$dryRun) {
                $this->em->flush();
                $this->em->clear(Article::class);
                $this->em->clear(ArticleImage::class);
                $this->em->clear(Variante::class);
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success("$count article(s) migré(s)");
    }

    private function migrateCommandes(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Commandes');
        $collection = $this->mongo->getCollection('commandes');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $commande = new Commande();
            $commande->setNumero((string)($doc['numero'] ?? 'CMD-' . strtoupper(uniqid())));
            $commande->setStatut((string)($doc['statut'] ?? 'en_attente'));
            $commande->setTotal((float)($doc['total'] ?? $doc['montantTotal'] ?? 0));
            $commande->setModePaiement(isset($doc['modePaiement']) ? (string)$doc['modePaiement'] : null);
            $commande->setNotes(isset($doc['notes']) ? (string)$doc['notes'] : null);

            // Client (JSON)
            $client = $doc['client'] ?? [];
            $commande->setClient(is_array($client) ? $client : (array)$client);

            // Adresse livraison (JSON)
            $adresse = $doc['adresseLivraison'] ?? $doc['adresse'] ?? [];
            $commande->setAdresseLivraison(is_array($adresse) ? $adresse : (array)$adresse);

            // Articles (JSON)
            $articles = $doc['articles'] ?? $doc['lignes'] ?? [];
            $articlesArray = [];
            foreach ($articles as $a) {
                $articlesArray[] = is_array($a) ? $a : (array)$a;
            }
            $commande->setArticles($articlesArray);

            // Dates
            if (isset($doc['createdAt'])) {
                $dt = $doc['createdAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? \DateTimeImmutable::createFromMutable($doc['createdAt']->toDateTime())
                    : new \DateTimeImmutable($doc['createdAt']);
                $commande->setCreatedAt($dt);
            }

            if (!$dryRun) {
                $this->em->persist($commande);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(Commande::class);
        }

        $io->success("$count commande(s) migrée(s)");
    }

    private function migrateMessages(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Messages');
        $collection = $this->mongo->getCollection('messages');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $msg = new Message();
            $msg->setNom((string)($doc['nom'] ?? ''));
            $msg->setEmail((string)($doc['email'] ?? ''));
            $msg->setSujet(isset($doc['sujet']) ? (string)$doc['sujet'] : null);
            $msg->setMessage((string)($doc['message'] ?? ''));
            $msg->setLu((bool)($doc['lu'] ?? false));

            if (isset($doc['createdAt'])) {
                $dt = $doc['createdAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? \DateTimeImmutable::createFromMutable($doc['createdAt']->toDateTime())
                    : new \DateTimeImmutable($doc['createdAt']);
                $msg->setCreatedAt($dt);
            }

            if (!$dryRun) {
                $this->em->persist($msg);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(Message::class);
        }

        $io->success("$count message(s) migré(s)");
    }

    private function migrateUsers(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Utilisateurs');
        $collection = $this->mongo->getCollection('utilisateurs');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $user = new User();
            $user->setEmail((string)($doc['email'] ?? ''));
            $user->setPassword((string)($doc['password'] ?? ''));
            $user->setPrenom(isset($doc['prenom']) ? (string)$doc['prenom'] : null);
            $user->setNom(isset($doc['nom']) ? (string)$doc['nom'] : null);
            $user->setTelephone(isset($doc['telephone']) ? (string)$doc['telephone'] : null);

            $role = $doc['role'] ?? 'ROLE_USER';
            $user->setRoles([$role]);

            if (!$dryRun) {
                $this->em->persist($user);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(User::class);
        }

        $io->success("$count utilisateur(s) migré(s)");
    }

    private function migrateCaracteristiques(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Caractéristiques');
        $collection = $this->mongo->getCollection('caracteristiques');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $carac = new Caracteristique();
            $carac->setNom((string)($doc['nom'] ?? ''));
            $carac->setType((string)($doc['type'] ?? 'text'));
            $carac->setObligatoire((bool)($doc['obligatoire'] ?? false));

            $valeurs = $doc['valeurs'] ?? [];
            foreach ($valeurs as $valeur) {
                $v = new CaracteristiqueValeur();
                $v->setValeur((string)$valeur);
                $v->setCaracteristique($carac);
                $carac->getValeurs()->add($v);
            }

            if (!$dryRun) {
                $this->em->persist($carac);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(Caracteristique::class);
        }

        $io->success("$count caractéristique(s) migrée(s)");
    }

    private function migrateTemplates(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Templates variantes');
        $collection = $this->mongo->getCollection('variante_templates');
        $docs = $collection->find();
        $count = 0;

        foreach ($docs as $doc) {
            $template = new VarianteTemplate();
            $template->setNom((string)($doc['nom'] ?? ''));
            $template->setDescription(isset($doc['description']) ? (string)$doc['description'] : null);

            // Lier caractéristiques par nom
            $caracNoms = $doc['caracteristiques'] ?? [];
            foreach ($caracNoms as $nom) {
                $carac = $this->em->getRepository(Caracteristique::class)->findOneBy(['nom' => (string)$nom]);
                if ($carac) {
                    $template->addCaracteristique($carac);
                }
            }

            if (!$dryRun) {
                $this->em->persist($template);
            }
            $count++;
        }

        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear(VarianteTemplate::class);
        }

        $io->success("$count template(s) migré(s)");
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u', 'e', $text);
        $text = preg_replace('/[ìíîï]/u', 'i', $text);
        $text = preg_replace('/[òóôõö]/u', 'o', $text);
        $text = preg_replace('/[ùúûü]/u', 'u', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text ?? '');
        return trim($text, '-');
    }
}
