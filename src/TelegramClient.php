<?php

declare(strict_types=1);

namespace Bot;

final class TelegramClient
{
    public function __construct(private readonly string $token)
    {
    }

    public function call(string $method, array $params = []): ?\stdClass
    {
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$method}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            error_log("Telegram API cURL error [$method]: " . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $decoded = json_decode($response);
        if (!$decoded instanceof \stdClass || !($decoded->ok ?? false)) {
            error_log("Telegram API failure [$method]: " . $response);
        }

        return $decoded instanceof \stdClass ? $decoded : null;
    }

    /**
     * Runs several Telegram API calls in parallel instead of one-by-one.
     *
     * @param array<string, array{method: string, params?: array}> $requests keyed by an arbitrary label
     * @return array<string, ?\stdClass> results keyed by the same labels
     */
    public function callMulti(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $multi = curl_multi_init();
        $handles = [];

        foreach ($requests as $key => $request) {
            $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$request['method']}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $request['params'] ?? [],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi);
        } while ($running > 0);

        $results = [];
        foreach ($handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $decoded = $response !== null ? json_decode($response) : null;
            $results[$key] = $decoded instanceof \stdClass ? $decoded : null;
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);

        return $results;
    }

    public function sendMessage(int|string $chatId, string $text, array $extra = []): ?\stdClass
    {
        return $this->call('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
        ], $extra));
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $extra = []): ?\stdClass
    {
        return $this->call('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'html',
        ], $extra));
    }

    public function deleteMessage(int|string $chatId, int $messageId): ?\stdClass
    {
        return $this->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    public function answerCallbackQuery(string $callbackId, array $extra = []): ?\stdClass
    {
        return $this->call('answerCallbackQuery', array_merge(['callback_query_id' => $callbackId], $extra));
    }

    public function forwardMessage(int|string $chatId, int|string $fromChatId, int $messageId): ?\stdClass
    {
        return $this->call('forwardMessage', [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
        ]);
    }

    public function sendPhoto(int|string $chatId, string $photo, array $extra = []): ?\stdClass
    {
        return $this->call('sendPhoto', array_merge([
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => 'html',
        ], $extra));
    }

    public function getChat(string $chatId): ?\stdClass
    {
        return $this->call('getChat', ['chat_id' => $chatId]);
    }

    public function getChatMember(string $chatId, int $userId): ?\stdClass
    {
        return $this->call('getChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
    }

    public function isChannelAdmin(string $channelUsername): bool
    {
        $result = $this->call('getChatAdministrators', ['chat_id' => '@' . ltrim($channelUsername, '@')]);

        return (bool) ($result->ok ?? false);
    }

    public function getMe(): ?\stdClass
    {
        return $this->call('getMe');
    }
}
