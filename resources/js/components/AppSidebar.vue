<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FilePenLine,
    FolderGit2,
    FolderKanban,
    KeyRound,
    LayoutGrid,
    ListTodo,
    ScrollText,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/tasks';
import { index as taskDraftsIndex } from '@/routes/task-drafts';
import { tokens as mcpTokensIndex, auditLog as auditLogIndex } from '@/routes/mcp';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Projects',
        href: projectsIndex(),
        icon: FolderKanban,
    },
    {
        title: 'Tasks',
        href: tasksIndex(),
        icon: ListTodo,
    },
    {
        title: 'Task drafts',
        href: taskDraftsIndex(),
        icon: FilePenLine,
    },
    {
        title: 'MCP tokens',
        href: mcpTokensIndex(),
        icon: KeyRound,
    },
    {
        title: 'Audit log',
        href: auditLogIndex(),
        icon: ScrollText,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
