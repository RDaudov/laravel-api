<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    private $botToken;

    public function __construct()
    {
        $this->botToken = env('TG_BOT_TOKEN');
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();
        \Log::info('Webhook received: ' . json_encode($data)); // Логирование входящих данных

        if (isset($data['message'])) {
            $chatId = $data['message']['chat']['id'];
            $text = $data['message']['text'];

            if ($text === '/start') {
                $this->sendMessage($chatId, 'Привет! Я ваш бот. Используйте /help для списка команд.');
            } elseif ($text === '/help') {
                $this->sendMessage($chatId, 'Доступные команды: /start, /help');
            } else {
                $this->sendMessage($chatId, 'Я не понимаю эту команду.');
            }
        }

        return response('O123K', 200);
    }

    private function sendMessage($chatId, $text)
    {
        $url = "https://api.telegram.org/bot" . $this->botToken . "/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        $response = Http::withOptions(['verify' => false])->post($url, $data);

        // Проверка на успешность отправки сообщения
        if ($response->failed()) {
            // Логирование ошибки или обработка
            \Log::error('Ошибка отправки сообщения в Telegram: ' . $response->body());
        }
    }
}
