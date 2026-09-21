<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import { tokens as mcpTokensIndex } from '@/routes/mcp';
import {
    store as mcpTokensStore,
    revoke as revokeToken,
} from '@/routes/mcp/tokens';

const props = defineProps<{
    tokens: Array<{
        id: number;
        name: string;
        token_prefix: string;
        scopes: string[];
        expires_at: string | null;
        revoked_at: string | null;
        last_used_at: string | null;
        operations_count: number;
        audit_logs_count: number;
        creator: { id: number; name: string } | null;
    }>;
    scopes: string[];
    newToken: string | null;
    newTokenPrefix: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'MCP tokens', href: mcpTokensIndex() },
        ],
    },
});

const form = useForm({
    name: '',
    scopes: [...props.scopes],
    days: '90',
});

const revokeForm = useForm({});

function setScope(scope: string, checked: boolean): void {
    if (checked) {
        if (!form.scopes.includes(scope)) {
            form.scopes = [...form.scopes, scope];
        }
    } else {
        form.scopes = form.scopes.filter((value) => value !== scope);
    }
}

function toggleScope(scope: string): void {
    setScope(scope, !form.scopes.includes(scope));
}

function create(): void {
    form.post(mcpTokensStore().url, { preserveScroll: true });
}

function revoke(tokenId: number): void {
    if (!window.confirm('Revoke this token? Existing invocations will fail.')) {
        return;
    }

    revokeForm.patch(revokeToken(tokenId).url, { preserveScroll: true });
}

function copyNewToken(): void {
    if (props.newToken) {
        window.navigator.clipboard?.writeText(props.newToken);
    }
}
</script>

<template>
    <Head title="MCP tokens" />

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">MCP tokens</h1>
            <p class="text-muted-foreground text-sm">
                Bearer tokens that authenticate calls to the MCP endpoint.
            </p>
        </div>

        <Card v-if="newToken" class="border-primary/50">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <KeyRound class="text-primary size-5" />
                    Token created
                </CardTitle>
                <CardDescription>
                    Copy this token now — it is shown only once and cannot be
                    recovered.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
                <code
                    class="bg-muted block overflow-x-auto rounded-md p-3 font-mono text-sm"
                >
                    {{ newToken }}
                </code>
                <Button variant="outline" size="sm" @click="copyNewToken">
                    Copy token
                </Button>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-4">
                <Card v-for="token in tokens" :key="token.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <CardTitle class="text-base">{{
                                token.name
                            }}</CardTitle>
                            <Badge
                                :variant="
                                    token.revoked_at
                                        ? 'destructive'
                                        : 'secondary'
                                "
                            >
                                {{ token.revoked_at ? 'Revoked' : 'Active' }}
                            </Badge>
                        </div>
                        <CardDescription class="font-mono"
                            >{{ token.token_prefix }}**********</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex flex-wrap gap-1.5">
                            <Badge
                                v-for="scope in token.scopes"
                                :key="scope"
                                variant="outline"
                            >
                                {{ scope }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            {{ token.operations_count }} operations ·
                            {{ token.audit_logs_count }} log entries
                            <template v-if="token.creator">
                                · created by {{ token.creator.name }}</template
                            >
                        </p>
                        <div
                            v-if="!token.revoked_at"
                            class="flex items-center justify-between pt-2 text-xs"
                        >
                            <span class="text-muted-foreground">
                                Expires {{ token.expires_at ?? 'never' }}
                            </span>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                :disabled="revokeForm.processing"
                                @click="revoke(token.id)"
                            >
                                Revoke
                            </Button>
                        </div>
                    </CardContent>
                </Card>
                <p v-if="!tokens.length" class="text-muted-foreground text-sm">
                    No tokens yet.
                </p>
            </div>

            <Card class="h-fit">
                <CardHeader>
                    <CardTitle class="text-base">Create token</CardTitle>
                    <CardDescription
                        >Grant scopes for the MCP tools it may
                        call.</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="token_name">Name</Label>
                        <Input
                            id="token_name"
                            v-model="form.name"
                            placeholder="e.g. staging-client"
                        />
                        <p
                            v-if="form.errors.name"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label>Scopes</Label>
                        <div
                            v-for="scope in scopes"
                            :key="scope"
                            class="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                :checked="form.scopes.includes(scope)"
                                :disabled="form.processing"
                                @update:checked="
                                    (checked: boolean) =>
                                        setScope(scope, checked)
                                "
                            />
                            <label
                                class="cursor-pointer select-none"
                                @click="toggleScope(scope)"
                            >
                                <code class="font-mono text-xs">{{
                                    scope
                                }}</code>
                            </label>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            All scopes are selected by default — uncheck any you
                            do not want to grant.
                        </p>
                        <p
                            v-if="form.errors.scopes"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.scopes }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="token_days">Expires in (days)</Label>
                        <Select v-model="form.days">
                            <SelectTrigger id="token_days" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="30">30 days</SelectItem>
                                <SelectItem value="90">90 days</SelectItem>
                                <SelectItem value="365">1 year</SelectItem>
                                <SelectItem value="0">Never</SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors.days"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.days }}
                        </p>
                    </div>

                    <Button
                        class="w-full"
                        :disabled="form.processing"
                        @click="create"
                    >
                        Create
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
