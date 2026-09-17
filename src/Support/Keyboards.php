<?php

declare(strict_types=1);

namespace Bot\Support;

final class Keyboards
{
    public static function reply(array $rows, bool $resize = true): string
    {
        return json_encode(['resize_keyboard' => $resize, 'keyboard' => $rows], JSON_UNESCAPED_UNICODE);
    }

    public static function inline(array $rows): string
    {
        return json_encode(['inline_keyboard' => $rows], JSON_UNESCAPED_UNICODE);
    }

    public static function remove(): string
    {
        return json_encode(['remove_keyboard' => true]);
    }

    public static function button(string $text, array $extra = []): array
    {
        return array_merge(['text' => $text], $extra);
    }

    public static function requestContactButton(string $text): array
    {
        return ['text' => $text, 'request_contact' => true];
    }
}
