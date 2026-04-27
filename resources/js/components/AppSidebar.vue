<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BedDouble, Building, FolderTree, LayoutGrid, Shield, Users, Wallet, FileSignature, HandCoins, Receipt, LogOut } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

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
];

const managementNavItems: NavItem[] = [
    {
        title: 'Users',
        href: '/management/users',
        icon: Shield,
    },
    {
        title: 'Tenants',
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
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
