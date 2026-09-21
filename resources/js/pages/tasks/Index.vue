<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ListTodo } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as tasksIndex, show } from '@/routes/tasks';

defineProps<{
    tasks: Array<{
        id: number;
        title: string;
        description: string | null;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        priority: 'low' | 'medium' | 'high';
        project: { id: number; name: string } | null;
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
    <Head title="Tasks" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Tasks</h1>
            <p class="text-muted-foreground text-sm">
                Tasks published from approved MCP drafts and seeded work.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Card v-for="task in tasks" :key="task.id">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ListTodo class="text-primary size-5" />
                        <Link :href="show(task.id)" class="hover:underline">{{
                            task.title
                        }}</Link>
                    </CardTitle>
                    <CardDescription
                        v-if="task.description"
                        class="line-clamp-2"
                    >
                        {{ task.description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary">{{ task.status }}</Badge>
                        <Badge variant="outline">{{ task.priority }}</Badge>
                    </div>
                    <span class="muted-foreground truncate text-sm">{{
                        task.project?.name
                    }}</span>
                </CardContent>
            </Card>
        </div>

        <p v-if="!tasks.length" class="text-muted-foreground text-sm">
            No tasks yet.
        </p>
    </div>
</template>
