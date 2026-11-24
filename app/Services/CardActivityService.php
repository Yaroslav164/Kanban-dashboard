<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Repositories\BoardRepository;
use App\Repositories\CardActivityRepository;
use App\Repositories\CardRepository;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class CardActivityService
{
    private const MAX_ATTACHMENT_COUNT = 20;

    private const MAX_ATTACHMENT_BYTES = 50 * 1024 * 1024;

    public function __construct(
        private BoardRepository $boards,
        private CardRepository $cards,
        private CardActivityRepository $activity,
        private UnitOfWork $transactions,
    ) {}

    public function activity(int $actor, int $card): array
    {
        $this->cards->find($actor, $card);

        return $this->activity->activity($card);
    }

    public function comment(int $actor, int $card, string $body): array
    {
        return $this->write($actor, $card, 'cards.comment', fn () => $this->activity->addComment($card, $actor, $body));
    }

    public function deleteComment(int $actor, int $card, int $comment): void
    {
        $this->write($actor, $card, 'cards.comment', fn () => $this->activity->deleteComment($card, $comment, $actor));
    }

    public function upload(int $actor, int $card, UploadedFile $file): array
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $file->getClientOriginalName());
        $name = trim(str_replace(['/', '\\'], '', $name ?? ''));
        $name = mb_substr($name, 0, 255) ?: 'attachment';
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $bytes = $file->get();
        if ($bytes === '') {
            throw ValidationException::withMessages(['file' => 'Choose a non-empty file.']);
        }

        return $this->write($actor, $card, 'cards.attach', function () use ($card, $name, $mime, $bytes) {
            $usage = $this->activity->attachmentUsage($card);
            if ($usage['count'] >= self::MAX_ATTACHMENT_COUNT || $usage['size'] + strlen($bytes) > self::MAX_ATTACHMENT_BYTES) {
                throw ValidationException::withMessages(['file' => 'A card can hold up to 20 files totaling 50 MiB.']);
            }

            return $this->activity->addAttachment($card, $name, $mime, $bytes);
        });
    }

    public function attachment(int $actor, int $card, int $attachment): array
    {
        $this->cards->find($actor, $card);

        return $this->activity->attachment($card, $attachment);
    }

    public function deleteAttachment(int $actor, int $card, int $attachment): void
    {
        $this->write($actor, $card, 'cards.attach', fn () => $this->activity->deleteAttachment($card, $attachment));
    }

    private function write(int $actor, int $card, string $permission, Closure $change): mixed
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $card, $permission, $change) {
            $board = $this->boards->lock($actor, $this->cards->boardId($actor, $card), $permission);
            $this->cards->find($actor, $card);
            $result = $change();
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit($result);
        });
    }
}
