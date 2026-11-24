<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\TicketType;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/** Données de démonstration : php bin/console doctrine:fixtures:load */
class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [];
        foreach ([
            ['musique', 'Concert'],
            ['spectacle', 'Spectacle'],
            ['famille', 'Famille'],
            ['ateliers', 'Atelier'],
        ] as [$slug, $name]) {
            $category = (new Category())->setSlug($slug)->setName($name);
            $categories[$slug] = $category;
            $manager->persist($category);
        }

        $organizer = (new User())
            ->setFirstName('Admin')
            ->setLastName('Olympia')
            ->setEmail('organisateur@ticketika.test')
            ->setPassword(password_hash('organisateur', PASSWORD_DEFAULT))
            ->setOrganizer(true);
        $manager->persist($organizer);

        $demoUser = (new User())
            ->setFirstName('Utilisateur')
            ->setLastName('Démo')
            ->setEmail('utilisateur@ticketika.test')
            ->setPassword(password_hash('utilisateur', PASSWORD_DEFAULT));
        $manager->persist($demoUser);

        $events = [
            ['Concert de jazz', 'musique', '+3 days 19:00', 'Place centrale', 'Populaire', 400, 312,
                'Une soirée jazz au cœur du centre commercial. Places limitées, réservation conseillée.',
                [['Standard', 25000, 400, 'Accès à la place centrale'], ['VIP', 45000, 100, 'Espace réservé avec boisson']]],
            ['Spectacle de magie', 'spectacle', '+4 days 19:30', 'Salle d’animation', 'Dernières places', 150, 138,
                'Un spectacle d’illusions pour toute la famille, à partir de 6 ans.',
                [['Enfant', 15000, 80, 'Moins de 12 ans'], ['Adulte', 25000, 70, 'Place standard']]],
            ['Soirée DJ', 'musique', '+10 days 21:00', 'Terrasse', 'Nouveau', 250, 40,
                'Une soirée électro en terrasse avec deux DJ résidents.',
                [['Entrée', 40000, 250, 'Accès terrasse']]],
            ['Atelier cuisine', 'ateliers', '+12 days 18:30', 'Espace restauration', 'Nouveau', 30, 12,
                'Apprenez à préparer un menu complet avec un chef local.',
                [['Place', 35000, 30, 'Ingrédients inclus']]],
            ['Animation de Noël', 'famille', '+17 days 10:00', 'Hall principal', 'Gratuit', 500, 210,
                'Chorale, maquillage et photos avec le Père Noël. Entrée libre sur inscription.',
                [['Entrée libre', 0, 500, 'Réservation recommandée']]],
        ];

        foreach ($events as [$title, $categorySlug, $when, $place, $tag, $capacity, $sold, $description, $types]) {
            $event = (new Event())
                ->setTitle($title)
                ->setSlug(Event::slugify($title))
                ->setCategory($categories[$categorySlug])
                ->setDescription($description)
                ->setStartsAt(new \DateTimeImmutable($when))
                ->setPlace($place)
                ->setTag($tag)
                ->setCapacity($capacity)
                ->setSoldTickets($sold);

            foreach ($types as [$name, $price, $quantity, $note]) {
                $event->addTicketType((new TicketType())
                    ->setName($name)
                    ->setPriceAr($price)
                    ->setQuantity($quantity)
                    ->setSold(0)
                    ->setNote($note));
            }

            $manager->persist($event);
        }

        $manager->flush();
    }
}
