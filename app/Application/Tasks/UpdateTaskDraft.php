<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Enums\TaskDraftStatus;
use App\Mcp\Auth\McpContext;
use App\Mcp\Exceptions\McpOperationConflict;
use App\Mcp\Exceptions\McpResourceNotFound;
use App\Mcp\Exceptions\StaleResourceConflict;
use App\Mcp\Operations\McpOperationService;
use App\Models\TaskDraft;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class UpdateTaskDraft
{
    public function __construct(private readonly McpOperationService $operations) {}

    /**
     * @param  array{
     *     operation_id: string,
     *     draft_id: int,
     *     expected_updated_at: string,
     *     title?: string|null,
     *     description?: string|null
     * }  $input
     * @return array<string, mixed>
     */
    public function handle(McpContext $context, array $input): array
    {
        return $this->operations->execute(
            context: $context,
            operationId: $input['operation_id'],
            operation: 'update-task-draft',
            callback: fn (): array => $this->update($input),
        );
    }

    /**
     * @param  array{
     *     operation_id: string,
     *     draft_id: int,
     *     expected_updated_at: string,
     *     title?: string|null,
     *     description?: string|null
     * }  $input
     * @return array{draft: array<string, mixed>}
     */
    private function update(array $input): array
    {
        return DB::transaction(function () use ($input): array {
            /** @var TaskDraft|null $draft */
            $draft = TaskDraft::query()->lockForUpdate()->find($input['draft_id']);

            if ($draft === null) {
                throw new McpResourceNotFound("Draft [{$input['draft_id']}] does not exist.");
            }

            if ($draft->status !== TaskDraftStatus::Draft) {
                throw new McpOperationConflict('Draft ['.$draft->id.'] is not editable in its current state ('.$draft->status->value.').');
            }

            $expected = Carbon::parse($input['expected_updated_at']);

            if (! $draft->updated_at->equalTo($expected)) {
                throw new StaleResourceConflict(
                    'The draft changed since it was retrieved. Reload the draft and retry with its latest updated_at value.',
                );
            }

            $draft->fill([
                'title' => $input['title'] ?? $draft->title,
                'description' => $input['description'] ?? $draft->description,
            ])->save();

            return ['draft' => TaskDraftSerializer::forMcp($draft)];
        });
    }
}
