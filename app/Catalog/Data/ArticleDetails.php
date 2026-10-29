<?php

namespace App\Catalog\Data;

final readonly class ArticleDetails
{
    /**
     * @param  list<string>  $oeNumbers
     * @param  list<array{name: string, value: string}>  $criteria
     */
    public function __construct(
        public ArticleRef $article,
        public array $oeNumbers = [],
        public array $criteria = [],
    ) {}
}
