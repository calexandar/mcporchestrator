<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import { show as taskShow } from '@/routes/tasks';

defineProps<{
    project: {
        id: number;
        name: string;
        slug: string;
        status: 'active' | 'archived';
        description: string | null;
        created_at: string | null;
        tasks_count: number;
        notes_count: number;
    };
    tasks: Array<{
        id: number;
        title: string;
        description: string | null;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high';
        created_at: string | null;
    }>;
    notes: Array<{
        id: number;
        title: string;
        body: string;
        created_at: string | null;
    }>;
    drafts: Array<{
        id: number;
        title: string;
        status: string;
        updated_at: string | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Projects', href: projectsIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="project.name" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1
                    class="flex items-center gap-2 text-2xl font-semibold tracking-tight"
                >
                    <FolderKanban class="text-primary size-6" />
                    {{ project.name }}
                </h1>
                <p class="text-muted-foreground font-mono text-sm">
                    {{ project.slug }}
                </p>
                <p
                    v-if="project.description"
                    class="text-muted-foreground mt-2 text-sm"
                >
                    {{ project.description }}
                </p>
            </div>
            <Badge
                :variant="
                    project.status === 'archived' ? 'destructive' : 'secondary'
                "
            >
                {{ project.status }}
            </Badge>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Tasks</CardTitle>
                    <CardDescription
                        >{{ project.tasks_count }} total.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <ul v-if="tasks.length" class="divide-y">
                        <li v-for="task in tasks" :key="task.id">
                            <Link
                                :href="taskShow(task.id)"
                                class="flex items-center justify-between gap-4 py-2 hover:underline"
                            >
                                <span class="min-w-0 truncate">{{
                                    task.title
                                }}</span>
                                <span class="inline-flex items-center gap-2">
                                    <Badge variant="outline">{{
                                        task.priority
                                    }}</Badge>
                                    <Badge variant="secondary">{{
                                        task.status
                                    }}</Badge>
                                </span>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground text-sm">
                        No tasks.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notes</CardTitle>
                    <CardDescription
                        >{{ project.notes_count }} total.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <ul v-if="notes.length" class="divide-y">
                        <li v-for="note in notes" :key="note.id" class="py-2">
                            <p class="font-medium">{{ note.title }}</p>
                            <p
                                class="text-muted-foreground line-clamp-2 text-sm"
                            >
                                {{ note.body }}
                            </p>
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground text-sm">
                        No notes.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
