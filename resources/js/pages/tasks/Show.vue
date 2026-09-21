<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
import { index as tasksIndex } from '@/routes/tasks';

defineProps<{
    task: {
        id: number;
        title: string;
        description: string | null;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high';
        created_at: string | null;
        project: { id: number; name: string } | null;
        creator: { id: number; name: string } | null;
        approver: { id: number; name: string } | null;
    };
    notes: Array<{
        id: number;
        title: string;
        body: string;
        created_at: string | null;
        creator: { id: number; name: string } | null;
    }>;
    drafts: Array<{
        id: number;
        title: string;
        status: string;
        created_at: string | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Tasks', href: tasksIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="task.title" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ task.title }}
                </h1>
                <Badge variant="secondary">{{ task.status }}</Badge>
                <Badge variant="outline">{{ task.priority }}</Badge>
            </div>
            <p class="mt-1 text-sm">
                <Link
                    v-if="task.project"
                    :href="projectsIndex()"
                    class="text-primary hover:underline"
                >
                    {{ task.project.name }}
                </Link>
                <span v-if="task.creator" class="text-muted-foreground">
                    · created by {{ task.creator.name }}</span
                >
                <span v-if="task.approver" class="text-muted-foreground">
                    · approved by {{ task.approver.name }}</span
                >
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Description</CardTitle>
            </CardHeader>
            <CardContent>
                <p v-if="task.description" class="text-sm whitespace-pre-wrap">
                    {{ task.description }}
                </p>
                <p v-else class="text-muted-foreground text-sm">
                    No description.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Notes</CardTitle>
                <CardDescription>{{ notes.length }} total.</CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="notes.length" class="divide-y">
                    <li v-for="note in notes" :key="note.id" class="py-2">
                        <p class="font-medium">{{ note.title }}</p>
                        <p class="text-muted-foreground text-sm">
                            {{ note.body }}
                        </p>
                        <p
                            v-if="note.creator"
                            class="text-muted-foreground mt-1 text-xs"
                        >
                            {{ note.creator.name }}
                        </p>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">No notes.</p>
            </CardContent>
        </Card>
    </div>
</template>
