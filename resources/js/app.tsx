import { initMonitoring } from '@/lib/monitoring';
import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import SiteLayout from '@/layouts/site-layout';

initMonitoring();

const appName = import.meta.env.VITE_APP_NAME || 'Shared Reading Lists';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Every page renders inside the app shell (SiteLayout: header, main, footer).
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return SiteLayout;
            case name.startsWith('auth/'):
                return [SiteLayout, AuthLayout];
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
