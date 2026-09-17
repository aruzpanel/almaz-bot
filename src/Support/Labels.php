<?php

declare(strict_types=1);

namespace Bot\Support;

use Bot\Defaults;
use Bot\Repository\ButtonRepository;

final class Labels
{
    public readonly string $earn;
    public readonly string $solve;
    public readonly string $cabinet;
    public readonly string $tolov;
    public readonly string $support;
    public readonly string $manual;
    public readonly string $back;
    public readonly string $getPhone;
    public readonly string $check;
    public readonly string $contiune;
    public readonly string $share;
    public readonly string $cancellation;
    public readonly string $confirm;
    public readonly string $transition;

    public function __construct(ButtonRepository $buttons)
    {
        $defaults = Defaults::buttons();
        $all = $buttons->all();

        $this->earn = $all['earn'] ?? $defaults['earn'];
        $this->solve = $all['solve'] ?? $defaults['solve'];
        $this->cabinet = $all['cabinet'] ?? $defaults['cabinet'];
        $this->tolov = $all['tolov'] ?? $defaults['tolov'];
        $this->support = $all['support'] ?? $defaults['support'];
        $this->manual = $all['manual'] ?? $defaults['manual'];
        $this->back = $all['back'] ?? $defaults['back'];
        $this->getPhone = $all['getPhone'] ?? $defaults['getPhone'];
        $this->check = $all['check'] ?? $defaults['check'];
        $this->contiune = $all['contiune'] ?? $defaults['contiune'];
        $this->share = $all['share'] ?? $defaults['share'];
        $this->cancellation = $all['cancellation'] ?? $defaults['cancellation'];
        $this->confirm = $all['confirm'] ?? $defaults['confirm'];
        $this->transition = $all['transition'] ?? $defaults['transition'];
    }
}
