<?php

declare(strict_types=1);

namespace Bot\Support;

use Bot\Repository\TemplateRepository;

final class Templates
{
    public function __construct(private readonly TemplateRepository $repo)
    {
    }

    /**
     * @param array<string, string|int> $placeholders keys WITHOUT the surrounding %...%
     */
    public function render(string $code, array $placeholders = []): string
    {
        $content = $this->repo->get($code) ?? '';

        $search = [];
        $replace = [];
        foreach ($placeholders as $key => $value) {
            $search[] = '%' . $key . '%';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $content);
    }
}
