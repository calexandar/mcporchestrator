<?php

declare(strict_types=1);

namespace App\Application\Notes;

use App\Models\Note;
use Illuminate\Support\Collection;

final class ListNotes
{
    /**
     * @param  array{project_id?: int|null, limit?: int|null}  $input
     * @return array{notes: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function handle(array $input): array
    {
        $limit = max(1, min($input['limit'] ?? 20, 100));

        $query = Note::query()->with('creator:id,name');

        if (($input['project_id'] ?? null) !== null) {
            $query->where('project_id', $input['project_id']);
        }

        /** @var Collection<int, Note> $notes */
        $notes = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $summaries = [];

        foreach ($notes as $note) {
            $summaries[] = $this->summarize($note);
        }

        return [
            'notes' => $summaries,
            'meta' => [
                'total' => $notes->count(),
                'limit' => $limit,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Note $note): array
    {
        return [
            'id' => $note->id,
            'project_id' => $note->project_id,
            'task_id' => $note->task_id,
            'title' => $note->title,
            'body' => $note->body,
            'created_by' => $note->creator?->name,
            'created_at' => $note->created_at?->toIso8601String(),
        ];
    }
}
