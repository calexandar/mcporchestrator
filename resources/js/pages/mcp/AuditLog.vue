<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { auditLog as auditLogIndex } from '@/routes/mcp';

defineProps<{
    entries: Array<{
        id: number;
        tool_name: string;
        success: boolean;
        error_code: string | null;
        input_hash: string | null;
        metadata: Record<string, unknown> | null;
        created_at: string | null;
        token: { id: number; name: string } | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Audit log', href: auditLogIndex() },
        ],
    },
});
</script>

<template>
    <Head title="Audit log" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Audit log</h1>
            <p class="text-muted-foreground text-sm">
                Every tool invocation made through the MCP endpoint, newest first.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">MCP tool calls</CardTitle>
                <CardDescription>{{ entries.length }} shown of the latest activity.</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="entries.length" class="divide-y">
                    <div
                        v-for="entry in entries"
                        :key="entry.id"
                        class="flex flex-wrap items-center justify-between gap-3 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm">{{ entry.tool_name }}</p>
                            <p class="text-muted-foreground text-xs">
                                {{ entry.token?.name ?? 'unknown token' }}
                                <template v-if="entry.input_hash"> · sha256 {{ entry.input_hash.slice(0, 12) }}…</template>
                            </p>
                            <p v-if="entry.metadata" class="text-muted-foreground text-xs">
                                {{ entry.metadata.duration_ms ?? '–' }} ms
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :variant="entry.success ? 'secondary' : 'destructive'">
                                {{ entry.success ? 'ok' : entry.error_code }}
                            </Badge>
                            <span class="text-muted-foreground whitespace-nowrap text-xs">
                                {{ entry.created_at }}
                            </span>
                        </div>
                    </div>
                </div>
                <p v-else class="text-muted-foreground text-sm">No MCP activity recorded yet.</p>
            </CardContent>
        </Card>
    </div>
</template>