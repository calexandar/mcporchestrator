<?php

use App\Enums\TaskDraftStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDraft;
use App\Models\User;

test('guests are redirected from drafts to login', function () {
    $draft = TaskDraft::factory()->create();

    $this->patch(route('task-drafts.approve', $draft))->assertRedirect(route('login'));
});

test('approving a draft publishes it as a task', function () {
    $approver = User::factory()->create();
    $draft = TaskDraft::factory()->create();

    $response = $this->actingAs($approver)
        ->from(route('task-drafts.show', $draft))
        ->patch(route('task-drafts.approve', $draft));

    $response->assertRedirect();

    $draft->refresh();

    expect($draft->status)->toBe(TaskDraftStatus::Published)
        ->and($draft->approved_by)->toBe($approver->id)
        ->and($draft->approved_at)->not->toBeNull()
        ->and($draft->task)->not->toBeNull();

    $this->assertDatabaseHas('tasks', [
        'id' => $draft->task_id,
        'project_id' => $draft->project_id,
        'title' => $draft->title,
    ]);
});

test('approving an already published draft does not duplicate the task', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $draft = TaskDraft::factory()->published()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'title' => $draft->title]);
    $draft->forceFill(['task_id' => $task->id])->save();

    $this->actingAs($approver)
        ->from(route('task-drafts.show', $draft))
        ->patch(route('task-drafts.approve', $draft));

    expect($draft->refresh()->status)->toBe(TaskDraftStatus::Published);
    $this->assertDatabaseCount('tasks', 1);
});

test('rejecting a draft stores the reason and does not publish', function () {
    $approver = User::factory()->create();
    $draft = TaskDraft::factory()->create();

    $response = $this->actingAs($approver)
        ->from(route('task-drafts.show', $draft))
        ->patch(route('task-drafts.reject', $draft), ['reason' => 'Out of scope for this milestone.']);

    $response->assertRedirect(route('task-drafts.show', $draft));

    $draft->refresh();

    expect($draft->status)->toBe(TaskDraftStatus::Rejected)
        ->and($draft->rejection_reason)->toBe('Out of scope for this milestone.')
        ->and($draft->approved_by)->toBe($approver->id)
        ->and($draft->task_id)->toBeNull();

    $this->assertDatabaseCount('tasks', 0);
});

test('rejecting a draft requires a reason', function () {
    $approver = User::factory()->create();
    $draft = TaskDraft::factory()->create();

    $this->actingAs($approver)
        ->patch(route('task-drafts.reject', $draft), [])
        ->assertSessionHasErrors('reason');

    expect($draft->refresh()->status)->toBe(TaskDraftStatus::Draft);
});

test('a rejected draft cannot be approved again', function () {
    $approver = User::factory()->create();
    $draft = TaskDraft::factory()->rejected()->create();

    $this->actingAs($approver)
        ->from(route('task-drafts.show', $draft))
        ->patch(route('task-drafts.approve', $draft));

    expect($draft->refresh()->status)->toBe(TaskDraftStatus::Rejected);
    $this->assertDatabaseCount('tasks', 0);
});

test('draft list pages render for authenticated users', function () {
    $user = User::factory()->create();
    TaskDraft::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('task-drafts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('task-drafts/Index'));
});

test('draft detail page renders the draft and can be filtered on the list', function () {
    $user = User::factory()->create();
    $draft = TaskDraft::factory()->create();

    $this->actingAs($user)
        ->get(route('task-drafts.show', $draft))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('task-drafts/Show'));

    $this->actingAs($user)
        ->get(route('task-drafts.index', ['status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('statusFilter', 'draft'));
});
