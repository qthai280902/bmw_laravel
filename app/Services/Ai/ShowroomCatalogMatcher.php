<?php

namespace App\Services\Ai;

use App\Enums\VehicleType;
use App\Models\Article;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ShowroomCatalogMatcher
{
    /**
     * @var array<int, list<string>>
     */
    private const SYNONYM_GROUPS = [
        ['suv', 'sav', 'gamcao'],
        ['xedien', 'ev', 'electric', 'bmwi', 'i4', 'ix', 'i5', 'i7'],
        ['xemay', 'moto', 'motorrad', 'motorbike', 'pkl', 'phankhoilon'],
        ['phukien', 'accessory', 'accessories'],
    ];

    /**
     * @var list<string>
     */
    private const GENERIC_TOKENS = [
        'bmw',
        'xe',
        'mau',
        'dong',
        'loai',
        'chiec',
        'gia',
        'ban',
        'tim',
        'cho',
        'toi',
        'minh',
        'va',
        'voi',
        'cac',
        'nhung',
        'duoi',
        'tam',
        'khoang',
        'tu',
        'van',
    ];

    public function normalizeSearchText(string $value): string
    {
        return preg_replace(
            '/[^a-z0-9]+/',
            '',
            Str::ascii(Str::lower($value))
        ) ?? '';
    }

    /**
     * @return array<int, string>
     */
    public function tokenizeText(string $value): array
    {
        $rawTokens = preg_split(
            '/[^a-z0-9]+/',
            Str::ascii(Str::lower($value)),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $tokens = $rawTokens;
        $count = count($rawTokens);

        for ($i = 0; $i < $count - 1; $i++) {
            $tokens[] = $rawTokens[$i].$rawTokens[$i + 1];

            if ($i < $count - 2) {
                $tokens[] = $rawTokens[$i].$rawTokens[$i + 1].$rawTokens[$i + 2];
            }
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    public function productLine(Product $product): string
    {
        if ($product->type === VehicleType::MOTORBIKE) {
            return 'BMW Motorrad';
        }

        if ($product->isAccessory()) {
            return 'BMW phụ kiện chính hãng';
        }

        return 'BMW ô tô';
    }

    /**
     * @return array<string, string>
     */
    public function specContext(Product $product, ?int $limit = 6): array
    {
        $specs = collect($product->translated_specs);

        if ($limit !== null) {
            $specs = $specs->take($limit);
        }

        return $specs
            ->map(fn (mixed $value): string => is_array($value) ? implode(', ', $value) : (string) $value)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function productAliases(Product $product): array
    {
        $values = [
            $product->name,
            $product->slug,
            $product->category?->name,
            $product->category?->slug,
            $this->productLine($product),
        ];

        if (Str::startsWith(Str::lower($product->name), 'bmw ')) {
            $values[] = Str::after($product->name, 'BMW ');
        }

        $tokenValues = collect([$product->name, $product->slug, $product->category?->name])
            ->filter()
            ->flatMap(fn (string $value): array => preg_split('/[\s\-\/]+/', Str::ascii(Str::lower($value))) ?: [])
            ->map(fn (string $value): string => $this->normalizeSearchText($value))
            ->filter(fn (string $value): bool => $this->isUsefulProductAliasToken($value))
            ->all();

        $semanticTags = $this->productSemanticTags($product);

        return collect(array_merge($values, $tokenValues, $semanticTags))
            ->filter()
            ->map(fn (string $value): string => $this->normalizeSearchText($value))
            ->filter(fn (string $value): bool => strlen($value) >= 4 || $this->isUsefulProductAliasToken($value) || in_array($value, ['sav', 'suv', 'pkl', 'ev', 'ix', 'i4', 'i5', 'i7'], true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function productSearchTokens(Product $product): array
    {
        $searchableParts = implode(' ', array_filter([
            $product->name,
            $product->slug,
            $product->category?->name,
            $product->category?->slug,
            $this->productLine($product),
            $product->description ? strip_tags($product->description) : null,
            implode(' ', $this->specContext($product, null)),
        ]));

        return array_values(array_unique(array_merge(
            $this->tokenizeText($searchableParts),
            $this->productAliases($product),
            $this->productSemanticTags($product),
        )));
    }

    public function scoreProductQuery(
        Product $product,
        string $query,
        bool $strictMultiTerm = true,
        bool $useSynonyms = true
    ): int {
        $compactQuery = $this->normalizeSearchText($query);

        if ($compactQuery === '') {
            return 0;
        }

        $compactSlug = $this->normalizeSearchText((string) $product->slug);
        $compactName = $this->normalizeSearchText((string) $product->name);
        $strippedBmwName = $this->normalizeSearchText(
            Str::startsWith(Str::lower($product->name), 'bmw ')
                ? Str::after($product->name, 'BMW ')
                : $product->name
        );

        if ($compactQuery === $compactSlug) {
            return 1000;
        }

        if ($compactQuery === $compactName || ($strippedBmwName !== '' && $compactQuery === $strippedBmwName)) {
            return 900;
        }

        $score = 0;
        $aliases = $useSynonyms
            ? $this->productAliases($product)
            : $this->directProductAliases($product);
        $productTokens = $useSynonyms
            ? $this->productSearchTokens($product)
            : $this->directProductSearchTokens($product);

        if (strlen($compactQuery) >= 3 && Str::contains($compactName, $compactQuery)) {
            $score += 550;
        } elseif (strlen($compactQuery) >= 3 && Str::contains($compactSlug, $compactQuery)) {
            $score += 500;
        } elseif (
            strlen($compactQuery) >= 4
            && collect($aliases)->contains(
                fn (string $alias): bool => strlen($alias) >= 4
                    && (Str::contains($alias, $compactQuery) || Str::contains($compactQuery, $alias))
            )
        ) {
            $score += 350;
        }

        $termGroups = $this->buildQueryTermGroups($query, $useSynonyms);

        if ($termGroups !== []) {
            $matchedGroups = 0;

            foreach ($termGroups as $variants) {
                if ($this->productMatchesAnyVariant($variants, $productTokens, $aliases)) {
                    $matchedGroups++;
                }
            }

            if ($strictMultiTerm && $matchedGroups < count($termGroups) && $score === 0) {
                return 0;
            }

            if ($matchedGroups > 0) {
                $score += $matchedGroups * 180;

                if ($matchedGroups === count($termGroups)) {
                    $score += 140;
                }
            }
        }

        return $score;
    }

    public function scoreArticleQuery(Article $article, string $keyword): int
    {
        $compactKeyword = $this->normalizeSearchText($keyword);

        if ($compactKeyword === '') {
            return 0;
        }

        $haystack = implode(' ', array_filter([
            $article->title,
            $article->slug,
            $article->categoryLabel(),
            $article->excerpt,
            $article->body ? strip_tags($article->body) : null,
        ]));

        $compactHaystack = $this->normalizeSearchText($haystack);

        if ($compactKeyword === $this->normalizeSearchText((string) $article->slug)) {
            return 1000;
        }

        if (str_contains($this->normalizeSearchText((string) $article->title), $compactKeyword)) {
            return 700;
        }

        if (str_contains($compactHaystack, $compactKeyword)) {
            return 450;
        }

        $queryTokens = array_values(array_diff(
            $this->tokenizeText($keyword),
            ['bmw', 'uu', 'dai', 'chuong', 'trinh', 'bai', 'viet', 'tin', 'tuc']
        ));

        if ($queryTokens === []) {
            $queryTokens = $this->tokenizeText($keyword);
        }

        $articleTokens = $this->tokenizeText($haystack);
        $matchedCount = count(array_intersect($queryTokens, $articleTokens));

        return $matchedCount > 0 ? $matchedCount * 150 : 0;
    }

    /**
     * @return array<int, string>
     */
    private function directProductAliases(Product $product): array
    {
        $values = [
            $product->name,
            $product->slug,
            $product->category?->name,
        ];

        if (Str::startsWith(Str::lower($product->name), 'bmw ')) {
            $values[] = Str::after($product->name, 'BMW ');
        }

        $tokenValues = collect([$product->name, $product->slug])
            ->filter()
            ->flatMap(fn (string $value): array => preg_split('/[\s\-\/]+/', Str::ascii(Str::lower($value))) ?: [])
            ->map(fn (string $value): string => $this->normalizeSearchText($value))
            ->filter(fn (string $value): bool => $this->isUsefulProductAliasToken($value))
            ->all();

        return collect(array_merge($values, $tokenValues))
            ->filter()
            ->map(fn (string $value): string => $this->normalizeSearchText($value))
            ->filter(fn (string $value): bool => strlen($value) >= 4 || $this->isUsefulProductAliasToken($value) || in_array($value, ['i4', 'ix', 'i5', 'i7', 'x1', 'x3', 'x4', 'x5', 'x6', 'x7', 'z4', 'm2', 'm3', 'm4', 'm5', 'm8'], true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function directProductSearchTokens(Product $product): array
    {
        return array_values(array_unique(array_merge(
            $this->tokenizeText((string) ($product->name.' '.$product->slug.' '.$product->category?->name)),
            $this->directProductAliases($product),
        )));
    }

    public function scoreMessageRelevance(Product $product, string $normalizedMessage): int
    {
        if ($normalizedMessage === '') {
            return 0;
        }

        return collect($this->productAliases($product))
            ->contains(fn (string $alias): bool => strlen($alias) >= 2 && Str::contains($normalizedMessage, $alias))
            ? 100
            : 0;
    }

    /**
     * @return Collection<int, Product>
     */
    public function searchCatalog(
        ?string $keyword = null,
        ?string $type = null,
        ?string $category = null,
        ?int $maxPrice = null,
        int $limit = 8
    ): Collection {
        $query = Product::query()
            ->active()
            ->with('category')
            ->select([
                'id',
                'category_id',
                'name',
                'slug',
                'type',
                'price',
                'deposit_amount',
                'specifications',
                'description',
            ]);

        $resolvedType = $this->resolveProductType($type);
        if ($resolvedType !== null) {
            $query->where('type', $resolvedType);
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $query->where('price', '<=', $maxPrice);
        }

        $products = $query->orderBy('id')->get();

        if (filled($category)) {
            $categoryQuery = (string) $category;
            $normalizedCategory = $this->normalizeSearchText($categoryQuery);
            $categoryGroups = $this->buildQueryTermGroups($categoryQuery);

            $products = $products->filter(function (Product $product) use ($normalizedCategory, $categoryGroups): bool {
                $catName = $this->normalizeSearchText((string) ($product->category?->name ?? ''));
                $catSlug = $this->normalizeSearchText((string) ($product->category?->slug ?? ''));
                $lineName = $this->normalizeSearchText($this->productLine($product));
                $combinedTokens = array_values(array_unique(array_merge(
                    $this->tokenizeText((string) ($product->category?->name.' '.$product->category?->slug.' '.$this->productLine($product))),
                    $this->productSemanticTags($product),
                    array_filter([$catName, $catSlug, $lineName]),
                )));

                if ($normalizedCategory !== '' && (
                    Str::contains($catName, $normalizedCategory)
                    || Str::contains($catSlug, $normalizedCategory)
                    || Str::contains($lineName, $normalizedCategory)
                )) {
                    return true;
                }

                return collect($categoryGroups)->contains(
                    fn (array $variants): bool => $this->productMatchesAnyVariant($variants, $combinedTokens, $combinedTokens)
                );
            })->values();
        }

        if (filled($keyword)) {
            $keywordString = trim((string) $keyword);

            return $products
                ->map(fn (Product $product): array => [
                    'product' => $product,
                    'score' => $this->scoreProductQuery($product, $keywordString, true),
                ])
                ->filter(fn (array $entry): bool => $entry['score'] > 0)
                ->sortByDesc('score')
                ->pluck('product')
                ->take($limit)
                ->values();
        }

        return $products->take($limit)->values();
    }

    public function resolveProductType(?string $type): ?string
    {
        $normalized = $this->normalizeSearchText((string) $type);

        return match ($normalized) {
            'car', 'oto', 'automobile' => VehicleType::CAR->value,
            'motorrad', 'motorbike', 'xemay', 'moto', 'pkl' => VehicleType::MOTORBIKE->value,
            'accessory', 'accessories', 'phukien' => VehicleType::ACCESSORY->value,
            default => null,
        };
    }

    public function compactText(?string $text, int $limit = 220): ?string
    {
        return filled($text)
            ? Str::limit(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '', $limit)
            : null;
    }

    public function money(?int $amount): ?string
    {
        return $amount !== null && $amount > 0
            ? number_format($amount, 0, ',', '.').' VND'
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatProductForTool(Product $product, ?int $specsLimit = 5): array
    {
        $isAccessory = $product->isAccessory();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type instanceof VehicleType ? $product->type->label() : (string) $product->type,
            'product_line' => $this->productLine($product),
            'category' => $product->category?->name,
            'price_vnd' => $this->money($product->price),
            'price_raw' => (int) $product->price,
            'deposit_vnd' => $this->money($product->deposit_amount),
            'description' => $this->compactText($product->description),
            'specs' => $this->specContext($product, $specsLimit),
            'url' => route('products.show', $product->slug, false),
            'compare_url' => $product->canCompare() ? route('products.compare', ['ids' => $product->id], false) : null,
            'booking_test_drive_url' => $product->canTestDrive()
                ? route('appointments.create', ['type' => 'test_drive', 'product_id' => $product->id], false)
                : null,
            'quote_url' => $product->isVehicle()
                ? route('appointments.create', ['type' => 'quote', 'product_id' => $product->id], false)
                : null,
            'accessory_order_url' => $isAccessory
                ? route('accessory-orders.create', $product->slug, false)
                : null,
            'accessories_url' => $isAccessory
                ? route('accessories.index', [], false)
                : null,
        ];
    }

    private function isUsefulProductAliasToken(string $value): bool
    {
        if ($value === 'bmw') {
            return false;
        }

        return strlen($value) >= 2 && preg_match('/\d/', $value) === 1;
    }

    /**
     * @return array<int, string>
     */
    private function productSemanticTags(Product $product): array
    {
        $tags = [];
        $haystack = Str::ascii(Str::lower(implode(' ', array_filter([
            $product->name,
            $product->slug,
            $product->category?->name,
            $product->category?->slug,
            $product->description ? strip_tags($product->description) : null,
            implode(' ', $this->specContext($product, null)),
        ]))));

        if ($product->type === VehicleType::MOTORBIKE) {
            array_push($tags, 'motorrad', 'motorbike', 'xemay', 'moto', 'pkl', 'phankhoilon');
        }

        if ($product->isAccessory()) {
            array_push($tags, 'phukien', 'accessory', 'accessories');
        }

        $tokens = preg_split('/[^a-z0-9]+/', $haystack, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count(array_intersect($tokens, ['sav', 'suv'])) > 0 || Str::contains($haystack, ['gam cao', 'sport activity vehicle'])) {
            array_push($tags, 'suv', 'sav', 'gamcao');
        }

        if (count(array_intersect($tokens, ['i4', 'ix', 'i5', 'i7', 'ev', 'electric', 'dien'])) > 0 || Str::contains($haystack, ['xe dien', 'thuan dien'])) {
            array_push($tags, 'xedien', 'ev', 'electric', 'bmwi');
        }

        return array_values(array_unique($tags));
    }

    /**
     * @return array<int, list<string>>
     */
    private function buildQueryTermGroups(string $query, bool $useSynonyms = true): array
    {
        $rawTokens = preg_split(
            '/[^a-z0-9]+/',
            Str::ascii(Str::lower($query)),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        if ($rawTokens === []) {
            return [];
        }

        $groups = [];
        $count = count($rawTokens);
        $consumed = [];

        for ($i = 0; $i < $count; $i++) {
            if (isset($consumed[$i])) {
                continue;
            }

            if ($useSynonyms && $i < $count - 2) {
                $trigram = $rawTokens[$i].$rawTokens[$i + 1].$rawTokens[$i + 2];
                if ($expanded = $this->expandSynonymVariant($trigram)) {
                    $groups[] = $expanded;
                    $consumed[$i] = $consumed[$i + 1] = $consumed[$i + 2] = true;

                    continue;
                }
            }

            if ($useSynonyms && $i < $count - 1) {
                $bigram = $rawTokens[$i].$rawTokens[$i + 1];
                if ($expanded = $this->expandSynonymVariant($bigram)) {
                    $groups[] = $expanded;
                    $consumed[$i] = $consumed[$i + 1] = true;

                    continue;
                }
            }

            $token = $rawTokens[$i];
            if (in_array($token, self::GENERIC_TOKENS, true)) {
                continue;
            }

            $groups[] = $useSynonyms ? ($this->expandSynonymVariant($token) ?? [$token]) : [$token];
        }

        if ($groups === []) {
            return [array_values(array_unique($rawTokens))];
        }

        return $groups;
    }

    /**
     * @return list<string>|null
     */
    private function expandSynonymVariant(string $term): ?array
    {
        foreach (self::SYNONYM_GROUPS as $group) {
            if (in_array($term, $group, true)) {
                // Specific BMW i model tokens match themselves plus EV terms without matching other i-series numbers directly.
                if (in_array($term, ['i4', 'ix', 'i5', 'i7'], true)) {
                    return [$term, 'xedien', 'ev', 'electric', 'bmwi'];
                }

                return $group;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $variants
     * @param  array<int, string>  $productTokens
     * @param  array<int, string>  $productAliases
     */
    private function productMatchesAnyVariant(array $variants, array $productTokens, array $productAliases): bool
    {
        foreach ($variants as $variant) {
            if (in_array($variant, $productTokens, true) || in_array($variant, $productAliases, true)) {
                return true;
            }

            if (strlen($variant) >= 4) {
                foreach ($productAliases as $alias) {
                    if (strlen($alias) >= 4 && Str::contains($alias, $variant)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
