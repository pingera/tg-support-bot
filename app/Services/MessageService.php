<?php

namespace App\Services;

use App\DTOs\TelegramAnswerDto;
use App\DTOs\TelegramUpdateDto;
use App\DTOs\TGTextMessageDto;
use App\Models\BotUser;
use phpDocumentor\Reflection\Exception;

abstract class MessageService
{
    protected string $typeMessage = '';
    protected string $source = 'telegram';
    protected TelegramUpdateDto $update;
    protected ?BotUser $botUser;
    protected TGTextMessageDto $messageParamsDTO;
    protected TgTopicService $tgTopicService;

    public function __construct(TelegramUpdateDto $update) {
        $this->update = $update;
        $this->tgTopicService = new TgTopicService();
        $this->botUser = BotUser::getTelegramUserData($this->update);

        if (empty($this->botUser)) {
            // Log a helpful message to understand what's happening.
            \Illuminate\Support\Facades\Log::warning(
                'Received update from an unknown user/topic. Stopping processing.',
                // --- THIS IS THE FIX ---
                // Pass the DTO object directly to the logger.
                // Laravel's logger will handle serializing it.
                ['update' => $this->update]
            );
            // Using die() is a simple and effective way to terminate a webhook script.
            die();
        }

        switch ($update->typeSource) {
            case 'private':
                $this->typeMessage = 'incoming';
                $queryParams = [
                    'chat_id' => env('TELEGRAM_GROUP_ID'),
                    'message_thread_id' => $this->botUser->topic_id,
                ];
                break;

            case 'supergroup':
                $this->typeMessage = 'outgoing';
                $queryParams = [
                    'chat_id' => $this->botUser->chat_id,
                ];
                break;

            default:
                throw new Exception('Данный тип запроса не поддерживается!');
        }

        $queryParams['methodQuery'] = 'sendMessage';
        $queryParams['typeSource'] = $update->typeSource;
        $this->messageParamsDTO = TGTextMessageDto::from($queryParams);
    }

    // ... rest of the abstract methods ...
    abstract public function handleUpdate(): void;
    abstract protected function sendPhoto(): TelegramAnswerDto;
    abstract protected function sendDocument(): TelegramAnswerDto;
    abstract protected function sendLocation(): TelegramAnswerDto;
    abstract protected function sendVoice(): TelegramAnswerDto;
    abstract protected function sendSticker(): TelegramAnswerDto;
    abstract protected function sendVideoNote(): TelegramAnswerDto;
    abstract protected function sendContact(): TelegramAnswerDto;
    abstract protected function sendMessage(): TelegramAnswerDto;
    abstract protected function saveMessage(TelegramAnswerDto $resultQuery): void;
}

