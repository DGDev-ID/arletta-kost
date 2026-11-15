<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BedDouble, Building, Database, FolderTree, LayoutGrid, Shield, Tag, Users, Wallet, FileSignature, HandCoins, Receipt, LogOut } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage<SharedData>();

const isSuperadmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.is_superadmin) return true;
    if (Array.isArray(user.roles)) {
        return user.roles.includes('superadmin');
    }
    return true;
});

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const masterNavItems: NavItem[] = [
    {
        title: 'Kost',
        href: '/master/kosts',
        icon: Building,
    },
    {
        title: 'Room Category',
        href: '/master/room-categories',
        icon: FolderTree,
    },
    {
        title: 'Room',
        href: '/master/rooms',
        icon: BedDouble,
    },
    {
        title: 'Promo',
        href: '/master/promos',
        icon: Tag,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Users',
        href: '/management/users',
        icon: Shield,
    },
    {
        title: 'Penyewa',
        href: '/management/tenants',
        icon: Users,
    },
    {
        title: 'Check Out',
        href: '/management/checkouts',
        icon: LogOut,
    },
];

const transactionNavItems: NavItem[] = [
    {
        title: 'Bill Approval',
        href: '/transactions/bill-approval',
        icon: Receipt,
    },
    {
        title: 'History Transactions',
        href: '/transactions',
        icon: Wallet,
        exact: true,
    },
    {
        title: 'Refund Request',
        href: '/transactions/refund-requests',
        icon: HandCoins,
    },
    {
        title: 'Signature',
        href: '/transactions/signatures',
        icon: FileSignature,
    },
];

const systemNavItems: NavItem[] = [
    {
        title: 'Backup Database',
        href: '/management/backup-databases',
        icon: Database,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="masterNavItems" label="Master Data" />
            <NavMain :items="managementNavItems" label="Management" />
            <NavMain :items="transactionNavItems" label="Transactions" />
            <NavMain v-if="isSuperadmin" :items="systemNavItems" label="System" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
