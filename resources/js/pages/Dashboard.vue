<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    FilePenLine,
    FolderKanban,
    KeyRound,
    ListTodo,
    ScrollText,
    StickyNote,
} from '@lucide/vue';
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
import { index as projectsIndex } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/tasks';
import { index as taskDraftsIndex, show as taskDraftShow } from '@/routes/task-drafts';
import { tokens as mcpTokensIndex, auditLog as auditLogIndex } from '@/routes/mcp';

const props = defineProps<{
    stats: {
        projects: number;
        active_projects: number;
        tasks: number;
        open_tasks: number;
        pending_drafts: number;
        notes: number;
        mcp_tokens: number;
    };
    recentDrafts: Array<{
        id: number;
        title: string;
        project: { id: number; name: string } | null;
        priority: string;
        updated_at: string | null;
    }>;
    recentTokens: Array<{
        id: number;
        name: string;
        token_prefix: string;
        expires_at: string | null;
        revoked_at: string | null;
        last_used_at: string | null;
    }>;
    recentAudit: Array<{
        id: number;
        tool_name: string;
        success: boolean;
        error_code: string | null;
        created_at: string | null;
        token: { id: number; name: string } | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const statCards: Array<{
    label: string;
    value: keyof (typeof props)['stats'];
    href: ReturnType<typeof projectsIndex>;
    icon: typeof FolderKanban;
}> = [
    {
        label: 'Projects',
        value: 'projects',
        href: projectsIndex(),
        icon: FolderKanban,
    },
    {
        label: 'Open tasks',
        value: 'open_tasks',
        href: tasksIndex(),
        icon: ListTodo,
    },
    {
        label: 'Pending drafts',
        value: 'pending_drafts',
        href: taskDraftsIndex(),
        icon: FilePenLine,
    },
    {
        label: 'Notes',
        value: 'notes',
        href: projectsIndex(),
        icon: StickyNote,
    },
    {
        label: 'MCP tokens',
        value: 'mcp_tokens',
        href: mcpTokensIndex(),
        icon: KeyRound,
    },
    {
        label: 'Recent audit',
        value: 'mcp_tokens',
        href: auditLogIndex(),
        icon: ScrollText,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
            <p class="text-muted-foreground text-sm">
                Overview of the MCP lab projects, tasks, drafts, and access.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="card in statCards"
                :key="card.label"
                :href="card.href"
                class="group"
            >
                <Card class="transition-colors group-hover:border-primary/40">
                    <CardContent class="flex items-center gap-4">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-lg">
                            <component :is="card.icon" class="size-5" />
                        </div>
                        <div class="flex-1 truncate">
                            <p class="muted-foreground text-xs">{{ card.label }}</p>
                            <p class="text-2xl font-semibold">{{ stats[card.value] }}</p>
                        </div>
                        <ArrowRight class="text-muted-foreground size-4 opacity-0 transition-opacity group-hover:opacity-100" />
                    </CardContent>
                </Card>
            </Link>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <div>
                        <CardTitle>Drafts awaiting approval</CardTitle>
                        <CardDescription>MCP-created drafts need a human review.</CardDescription>
                    </div>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="taskDraftsIndex()">View all</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <ul v-if="recentDrafts.length" class="divide-y">
                        <li v-for="draft in recentDrafts" :key="draft.id" class="flex items-center justify-between py-2">
                            <div class="min-w-0">
                                <Link :href="taskDraftShow(draft.id)" class="truncate font-medium hover:underline">
                                    {{ draft.title }}
                                </Link>
                                <p class="muted-foreground text-xs">{{ draft.project?.name }}</p>
                            </div>
                            <Badge variant="secondary">{{ draft.priority }}</Badge>
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground text-sm">No pending drafts.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <div>
                        <CardTitle>MCP tokens</CardTitle>
                        <CardDescription>Recently created bearer tokens.</CardDescription>
                    </div>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="mcpTokensIndex()">Manage</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <ul v-if="recentTokens.length" class="divide-y">
                        <li v-for="token in recentTokens" :key="token.id" class="flex items-center justify-between py-2">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ token.name }}</p>
                                <p class="muted-foreground text-xs font-mono">{{ token.token_prefix }}*****</p>
                            </div>
                            <Badge :variant="token.revoked_at ? 'destructive' : 'secondary'">
                                {{ token.revoked_at ? 'Revoked' : 'Active' }}
                            </Badge>
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground text-sm">No tokens yet.</p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader class="flex-row items-center justify-between">
                <div>
                    <CardTitle>Recent MCP calls</CardTitle>
                    <CardDescription>Audit trail for tools invoked over the MCP endpoint.</CardDescription>
                </div>
                <Button as-child variant="outline" size="sm">
                    <Link :href="auditLogIndex()">Full log</Link>
                </Button>
            </CardHeader>
            <CardContent>
                <ul v-if="recentAudit.length" class="divide-y">
                    <li v-for="entry in recentAudit" :key="entry.id" class="flex items-center justify-between gap-4 py-2">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm">{{ entry.tool_name }}</p>
                            <p class="muted-foreground text-xs">
                                {{ entry.token?.name ?? 'unknown token' }}
                            </p>
                        </div>
                        <Badge :variant="entry.success ? 'secondary' : 'destructive'">
                            {{ entry.success ? 'ok' : entry.error_code }}
                        </Badge>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">No MCP activity yet.</p>
            </CardContent>
        </Card>
    </div>
</template>