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
import { index as projectsIndex, show } from '@/routes/projects';

defineProps<{
    projects: Array<{
        id: number;
        name: string;
        slug: string;
        status: 'active' | 'archived';
        description: string | null;
        tasks_count: number;
        notes_count: number;
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
    <Head title="Projects" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
            <p class="text-muted-foreground text-sm">
                Projects organize tasks, drafts, and notes exposed through the MCP lab.
            </p>
        </div>

        <div v-if="projects.length" class="grid gap-4 md:grid-cols-2">
            <Card v-for="project in projects" :key="project.id">
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <CardTitle class="flex items-center gap-2">
                            <FolderKanban class="text-primary size-5" />
                            {{ project.name }}
                        </CardTitle>
                        <Badge :variant="project.status === 'archived' ? 'destructive' : 'secondary'">
                            {{ project.status }}
                        </Badge>
                    </div>
                    <CardDescription class="font-mono">{{ project.slug }}</CardDescription>
                    <CardDescription v-if="project.description" class="line-clamp-2">
                        {{ project.description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex items-center justify-between gap-4">
                    <p class="text-muted-foreground text-sm">
                        {{ project.tasks_count }} tasks · {{ project.notes_count }} notes
                    </p>
                    <Link
                        :href="show(project.id)"
                        class="text-primary text-sm font-medium hover:underline"
                    >
                        View
                    </Link>
                </CardContent>
            </Card>
        </div>

        <p v-else class="text-muted-foreground text-sm">No projects yet.</p>
    </div>
</template>