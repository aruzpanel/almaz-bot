<?php

declare(strict_types=1);

namespace Bot;

final class Update
{
    private function __construct(
        public readonly string $type,
        public readonly ?int $chatId,
        public readonly ?int $messageId,
        public readonly ?string $text,
        public readonly ?string $data,
        public readonly ?int $fromId,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $username,
        public readonly ?string $callbackId,
        public readonly ?string $contactPhone,
        public readonly ?int $contactUserId,
    ) {
    }

    public static function fromRawBody(string $rawBody): self
    {
        $decoded = json_decode($rawBody);
        if (!$decoded instanceof \stdClass) {
            return self::unknown();
        }

        return self::fromStdClass($decoded);
    }

    public static function fromStdClass(\stdClass $update): self
    {
        if (isset($update->message)) {
            $message = $update->message;
            $from = $message->from ?? null;
            $contact = $message->contact ?? null;

            return new self(
                type: 'message',
                chatId: isset($message->chat->id) ? (int) $message->chat->id : null,
                messageId: isset($message->message_id) ? (int) $message->message_id : null,
                text: $message->text ?? null,
                data: null,
                fromId: isset($from->id) ? (int) $from->id : null,
                firstName: $from->first_name ?? null,
                lastName: $from->last_name ?? null,
                username: $from->username ?? null,
                callbackId: null,
                contactPhone: $contact->phone_number ?? null,
                contactUserId: isset($contact->user_id) ? (int) $contact->user_id : null,
            );
        }

        if (isset($update->callback_query)) {
            $callback = $update->callback_query;
            $from = $callback->from ?? null;
            $message = $callback->message ?? null;

            return new self(
                type: 'callback_query',
                chatId: isset($message->chat->id) ? (int) $message->chat->id : null,
                messageId: isset($message->message_id) ? (int) $message->message_id : null,
                text: null,
                data: $callback->data ?? null,
                fromId: isset($from->id) ? (int) $from->id : null,
                firstName: $from->first_name ?? null,
                lastName: $from->last_name ?? null,
                username: $from->username ?? null,
                callbackId: $callback->id ?? null,
                contactPhone: null,
                contactUserId: null,
            );
        }

        return self::unknown();
    }

    private static function unknown(): self
    {
        return new self('unknown', null, null, null, null, null, null, null, null, null, null, null);
    }
}
