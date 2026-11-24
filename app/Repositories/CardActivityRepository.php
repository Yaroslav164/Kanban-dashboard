<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

final class CardActivityRepository
{
    private const ATTACHMENT_FIELDS = ['id', 'name', 'mime_type', 'size', 'created_at'];

    public function activity(int $card): array
    {
        return [
            'comments' => $this->comments($card)->orderBy('card_comments.id')->get()
                ->map($this->commentData(...))->all(),
            'attachments' => $this->attachments($card)->select(self::ATTACHMENT_FIELDS)->orderBy('id')->get()
                ->map($this->attachmentData(...))->all(),
        ];
    }

    public function addComment(int $card, int $user, string $body): array
    {
        $id = DB::table('card_comments')->insertGetId([
            'card_id' => $card,
            'user_id' => $user,
            'body' => $body,
            'created_at' => now(),
        ]);

        return $this->commentData($this->comments($card)->where('card_comments.id', $id)->first());
    }

    public function deleteComment(int $card, int $comment, int $user): void
    {
        $row = DB::table('card_comments')->where('card_id', $card)->find($comment);
        abort_if($row === null, 404);
        abort_if((int) $row->user_id !== $user, 403);

        DB::table('card_comments')->where('id', $comment)->delete();
    }

    public function addAttachment(int $card, string $name, string $mime, string $bytes): array
    {
        $id = DB::table('card_attachments')->insertGetId([
            'card_id' => $card,
            'name' => $name,
            'mime_type' => $mime,
            'size' => strlen($bytes),
            'content' => '\\x'.bin2hex($bytes),
            'created_at' => now(),
        ]);

        return $this->attachmentData($this->attachments($card)->select(self::ATTACHMENT_FIELDS)->find($id));
    }

    /** @return array{count: int, size: int} */
    public function attachmentUsage(int $card): array
    {
        $usage = $this->attachments($card)->selectRaw('count(*) as file_count, coalesce(sum(size), 0) as total_size')->first();

        return ['count' => (int) $usage->file_count, 'size' => (int) $usage->total_size];
    }

    public function attachment(int $card, int $attachment): array
    {
        $row = $this->attachments($card)->select(self::ATTACHMENT_FIELDS)
            ->selectRaw("encode(content, 'hex') as encoded_content")->find($attachment);
        abort_if($row === null, 404);

        return $this->attachmentData($row) + ['content' => hex2bin($row->encoded_content)];
    }

    public function deleteAttachment(int $card, int $attachment): void
    {
        abort_if($this->attachments($card)->where('id', $attachment)->delete() === 0, 404);
    }

    private function comments(int $card): Builder
    {
        return DB::table('card_comments')->where('card_comments.card_id', $card)
            ->leftJoin('users', 'users.id', '=', 'card_comments.user_id')
            ->select(['card_comments.id', 'card_comments.user_id', 'card_comments.body', 'card_comments.created_at', 'users.name']);
    }

    private function attachments(int $card): Builder
    {
        return DB::table('card_attachments')->where('card_id', $card);
    }

    private function commentData(stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'body' => $row->body,
            'author' => $row->user_id === null ? null : ['id' => (int) $row->user_id, 'name' => $row->name],
            'created_at' => Carbon::parse($row->created_at)->toIso8601String(),
        ];
    }

    private function attachmentData(stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'mime_type' => $row->mime_type,
            'size' => (int) $row->size,
            'created_at' => Carbon::parse($row->created_at)->toIso8601String(),
        ];
    }
}
