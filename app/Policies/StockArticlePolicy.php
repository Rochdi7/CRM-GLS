<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StockArticle;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;

final class StockArticlePolicy extends ResourcePolicy
{
    protected string $module = 'stock';

    /**
     * Recording an Entrée/Sortie/Ajustement on this article.
     */
    public function move(User $user, StockArticle $article): bool
    {
        return $user->can('stock.move') && $this->withinCenter($user, $article);
    }
}
