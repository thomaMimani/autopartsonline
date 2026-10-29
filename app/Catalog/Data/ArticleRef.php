<?php

namespace App\Catalog\Data;

/** One catalog article, in our own shape regardless of the API's field names. */
final readonly class ArticleRef
{
    public function __construct(
        public int $articleId,
        public int $supplierId,
        public string $articleNo,
        public ?string $brandName = null,
        public ?string $name = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?string $ean = null,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
