<?php

namespace App\Services\Photos;

/** A stock photo API the importer can pull from. */
interface PhotoSource
{
    /** "pexels" | "unsplash" — stored on every photo. */
    public function name(): string;

    /**
     * @return array{photos: array<int, array>, total: int, remaining: ?int} photos in StockPhoto column form;
     *                                                                     remaining = requests left this hour, if known
     * @throws \RuntimeException on HTTP failure (message contains "HTTP <status>")
     */
    public function search(string $query, int $perPage = 20, int $page = 1, ?string $orientation = null): array;
}
