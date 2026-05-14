import { AiAssistantPanel } from '@/components/ai-assistant-panel';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashAlert } from '@/components/flash-alert';
import { useHtmlDir } from '@/i18n/use-html-dir';
import { type BreadcrumbItem } from '@/types';
import { useState } from 'react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    useHtmlDir();
    const [aiPanelOpen, setAiPanelOpen] = useState(false);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} onOpenAiPanel={() => setAiPanelOpen(true)} />
                <div className="app-page pt-4">
                    <FlashAlert />
                </div>
                <div className="pb-6">{children}</div>
            </AppContent>
            <AiAssistantPanel open={aiPanelOpen} onOpenChange={setAiPanelOpen} />
        </AppShell>
    );
}
