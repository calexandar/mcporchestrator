<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FilePenLine } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as taskDraftsIndex, show } from '@/routes/task-drafts';

defineProps<{
    drafts: Array<{
        id: number;
        title: string;
        description: string | null;
        status: 'draft' | 'approved' | 'rejected' | 'published';
        source: string | null;
        updated_at: string | null;
        project: { id: number; name: string } | null;
        creatorToken: { id: number; name: string } | null;
    }>;
    statusFilter: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Task drafts', href: taskDraftsIndex() },
        ],
    },
});

const filters: Array<{ label: string; value: string | undefined }> = [
    { label: 'All', value: undefined },
    { label: 'Draft', value: 'draft' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Published', value: 'published' },
];

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'rejected') return 'destructive';
    if (status === 'draft') return 'default';
    return 'secondary';
}
</script>

<template>
    <Head title="Task drafts" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Task drafts</h1>
            <p class="text-muted-foreground text-sm">
                Drafts created over MCP must be reviewed and approved or
                rejected by a human.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button
                v-for="filter in filters"
                :key="filter.label"
                as-child
                :size="'sm'"
                :variant="
                    statusFilter === (filter.value ?? null)
                        ? 'default'
                        : 'outline'
                "
            >
                <Link
                    :href="
                        taskDraftsIndex({
                            query: filter.value
                                ? { status: filter.value }
                                : undefined,
                        })
                    "
                >
                    {{ filter.label }}
                </Link>
            </Button>
        </div>

        <Card v-for="draft in drafts" :key="draft.id">
            <CardHeader>
                <div class="flex items-start justify-between gap-4">
                    <CardTitle class="flex items-center gap-2">
                        <FilePenLine class="text-primary size-5" />
                        <Link :href="show(draft.id)" class="hover:underline">{{
                            draft.title
                        }}</Link>
                    </CardTitle>
                    <Badge :variant="statusVariant(draft.status)">{{
                        draft.status
                    }}</Badge>
                </div>
                <CardDescription v-if="draft.description" class="line-clamp-2">
                    {{ draft.description }}
                </CardDescription>
            </CardHeader>
            <CardContent
                class="flex items-center justify-between gap-4 text-sm"
            >
                <span class="muted-foreground truncate">{{
                    draft.project?.name
                }}</span>
                <span class="muted-foreground shrink-0">{{
                    draft.creatorToken?.name ?? 'web'
                }}</span>
            </CardContent>
        </Card>

        <p v-if="!drafts.length" class="text-muted-foreground text-sm">
            No drafts match this filter.
        </p>
    </div>
</template>
