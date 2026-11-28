<?php

namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CategoryExtension extends AbstractExtension
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ticketika_categories', fn () => $this->categories->findActive()),
        ];
    }
}
