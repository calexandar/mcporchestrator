<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\TaskDraftStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDraft;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application with demo data: users, projects, tasks, drafts,
     * and notes so every screen has something to show.
     */
    public function run(): void
    {
        $users = User::factory()->count(3)->create();

        $projects = [
            ['name' => 'Corporate Website', 'slug' => 'corporate-website', 'status' => ProjectStatus::Active],
            ['name' => 'Mobile App', 'slug' => 'mobile-app', 'status' => ProjectStatus::Active],
            ['name' => 'Legacy CRM', 'slug' => 'legacy-crm', 'status' => ProjectStatus::Archived],
        ];

        $seededProjects = [];

        foreach ($projects as $index => $project) {
            $seededProjects[] = Project::factory()->create([
                'name' => $project['name'],
                'slug' => $project['slug'],
                'status' => $project['status'],
                'created_by' => $users[$index]->id,
                'description' => null,
            ]);
        }

        $activeProjects = array_slice($seededProjects, 0, 2);

        foreach ($activeProjects as $projectIndex => $project) {
            foreach (range(1, 5) as $taskIndex) {
                Task::factory()->create([
                    'project_id' => $project->id,
                    'created_by' => $users[$projectIndex]->id,
                    'status' => match ($taskIndex) {
                        1 => TaskStatus::InProgress,
                        2 => TaskStatus::Review,
                        3 => TaskStatus::Done,
                        default => TaskStatus::Todo,
                    },
                    'priority' => match ($taskIndex % 3) {
                        0 => TaskPriority::High,
                        1 => TaskPriority::Low,
                        default => TaskPriority::Medium,
                    },
                ]);
            }
        }

        Note::factory()->count(5)->create([
            'project_id' => $activeProjects[0]->id,
            'created_by' => $users[0]->id,
        ]);

        foreach (['Add dark mode toggle', 'Fix checkout validation', 'Improve onboarding copy'] as $index => $title) {
            TaskDraft::factory()->create([
                'project_id' => $activeProjects[$index % 2]->id,
                'title' => $title,
                'status' => TaskDraftStatus::Draft,
                'created_by_user_id' => $users[$index]->id,
            ]);
        }
    }
}
