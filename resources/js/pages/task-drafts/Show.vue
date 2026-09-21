<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { show as projectShow } from '@/routes/projects';
import { index as taskDraftsIndex } from '@/routes/task-drafts';
import {
    approve as approveDraft,
    reject as rejectDraft,
} from '@/routes/task-drafts';

const props = defineProps<{
    draft: {
        id: number;
        title: string;
        description: string | null;
        external_ref: string | null;
        source: string | null;
        rejection_reason: string | null;
        status: 'draft' | 'approved' | 'rejected' | 'published';
        created_at: string | null;
        updated_at: string | null;
        project: { id: number; name: string } | null;
        creatorToken: { id: number; name: string } | null;
        creatorUser: { id: number; name: string } | null;
        approver: { id: number; name: string } | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Task drafts', href: taskDraftsIndex() },
        ],
    },
});

const approveForm = useForm({});
const rejectForm = useForm({ reason: '' });

function approve(): void {
    approveForm.patch(approveDraft(props.draft.id).url, {
        preserveScroll: true,
    });
}

function reject(): void {
    rejectForm.patch(rejectDraft(props.draft.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="draft.title" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ draft.title }}
                </h1>
                <Badge variant="secondary">{{ draft.status }}</Badge>
            </div>
            <p class="text-muted-foreground mt-1 text-sm">
                <Link
                    v-if="draft.project"
                    :href="projectShow(draft.project.id)"
                    class="text-primary hover:underline"
                >
                    {{ draft.project.name }}
                </Link>
                <span v-if="draft.creatorToken" class="text-muted-foreground">
                    · created via MCP token {{ draft.creatorToken.name }}
                </span>
                <span v-if="draft.creatorUser" class="text-muted-foreground">
                    · created by {{ draft.creatorUser.name }}
                </span>
                <span v-if="draft.approver" class="text-muted-foreground">
                    ·
                    {{ draft.status === 'rejected' ? 'rejected' : 'approved' }}
                    by {{ draft.approver.name }}
                </span>
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Proposed task</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <div>
                    <p class="text-muted-foreground text-xs uppercase">
                        Description
                    </p>
                    <p v-if="draft.description" class="whitespace-pre-wrap">
                        {{ draft.description }}
                    </p>
                    <p v-else class="text-muted-foreground">No description.</p>
                </div>
                <div v-if="draft.external_ref">
                    <p class="text-muted-foreground text-xs uppercase">
                        External reference
                    </p>
                    <p class="font-mono">{{ draft.external_ref }}</p>
                </div>
                <div v-if="draft.rejection_reason">
                    <p class="text-muted-foreground text-xs uppercase">
                        Rejection reason
                    </p>
                    <p>{{ draft.rejection_reason }}</p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="draft.status === 'draft'">
            <CardHeader>
                <CardTitle class="text-base">Review</CardTitle>
                <CardDescription>
                    Approving publishes this draft as a task. Rejecting requires
                    a reason.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <Button :disabled="approveForm.processing" @click="approve">
                    Approve and publish
                </Button>

                <div class="space-y-3">
                    <Label for="rejection_reason">Rejection reason</Label>
                    <textarea
                        id="rejection_reason"
                        v-model="rejectForm.reason"
                        :disabled="rejectForm.processing"
                        rows="3"
                        placeholder="Explain why this draft is rejected."
                        class="border-input placeholder:text-muted-foreground focus-visible:ring-ring/50 rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50"
                    />
                    <p
                        v-if="rejectForm.errors.reason"
                        class="text-destructive text-sm"
                    >
                        {{ rejectForm.errors.reason }}
                    </p>
                    <Button
                        variant="destructive"
                        :disabled="rejectForm.processing"
                        @click="reject"
                    >
                        Reject draft
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
