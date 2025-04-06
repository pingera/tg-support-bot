<?php

namespace App\Actions\Telegram;

use App\DTOs\TelegramAnswerDto;
use App\DTOs\TGTextMessageDto;
use App\TelegramBot\TelegramMethods;

/**
 * Delete message action
 */
class DeleteMessage
{
    /**
     * Deleting message
     * @param TGTextMessageDto $queryParams
     * @return TelegramAnswerDto|null
     */
    public static function execute(TGTextMessageDto $queryParams): ?TelegramAnswerDto
    {
        try {
            $dataQuery = $queryParams->toArray();
            return TelegramMethods::sendQueryTelegram('deleteMessage', $dataQuery);
        }catch (\Exception $e){
            return null;
        }
    }
}
