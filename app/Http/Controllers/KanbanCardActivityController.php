<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CardActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

final class KanbanCardActivityController extends KanbanController
{
    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(private CardActivityService $activity) {}

    public function show(Request $request): JsonResponse
    {
        return $this->data($this->activity->activity($request->user()->id, $this->id($request, 'card')));
    }

    public function comment(Request $request): JsonResponse
    {
        $input = $this->input($request, ['body' => 'required|string|max:10000']);

        return $this->data($this->activity->comment($request->user()->id, $this->id($request, 'card'), trim($input['body'])), 201);
    }

    public function deleteComment(Request $request): Response
    {
        $this->activity->deleteComment($request->user()->id, $this->id($request, 'card'), $this->id($request, 'comment'));

        return response()->noContent();
    }

    public function upload(Request $request): JsonResponse
    {
        $input = $request->validate(['file' => 'required|file|max:10240']);

        return $this->data($this->activity->upload($request->user()->id, $this->id($request, 'card'), $input['file']), 201);
    }

    public function download(Request $request): Response
    {
        $file = $this->activity->attachment($request->user()->id, $this->id($request, 'card'), $this->id($request, 'attachment'));
        $inline = $request->boolean('inline') && in_array($file['mime_type'], self::IMAGE_TYPES, true);
        $fallback = str_replace('%', '', Str::ascii($file['name'])) ?: 'attachment';

        return response($file['content'], 200, [
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $file['name'], $fallback),
            'Content-Length' => (string) $file['size'],
            'Content-Type' => $inline ? $file['mime_type'] : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteAttachment(Request $request): Response
    {
        $this->activity->deleteAttachment($request->user()->id, $this->id($request, 'card'), $this->id($request, 'attachment'));

        return response()->noContent();
    }
}
