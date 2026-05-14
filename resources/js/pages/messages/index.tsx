import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, SendHorizonal } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface UserLite { id: number; name: string }
interface MessageItem { id: number; body: string; created_at: string; sender: UserLite }
interface Participant { id: number; name: string }
interface ConversationItem {
    id: number;
    title: string | null;
    type: string;
    last_message_at: string | null;
    participants: Participant[];
    messages?: MessageItem[];
    conversation_participants?: Array<{ user_id: number; last_read_at: string | null }>;
}
interface Paginated<T> { data: T[] }

interface MessagesProps {
    conversations: Paginated<ConversationItem>;
    activeConversation: ConversationItem | null;
    users: UserLite[];
}

export default function MessagesIndex({ conversations, activeConversation, users }: MessagesProps) {
    const { t } = useTranslation();
    const getInitials = useInitials();
    const [showComposer, setShowComposer] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.messages'), href: '/messages' }];

    const messageForm = useForm({ body: '' });
    const conversationForm = useForm<{ type: 'direct' | 'group'; title: string; participant_ids: number[] }>({
        type: 'direct',
        title: '',
        participant_ids: [],
    });

    const conversationTitle = (conversation: ConversationItem): string => {
        if (conversation.title) return conversation.title;
        return conversation.participants.map((p) => p.name).join(', ');
    };

    const selectedMessages = activeConversation?.messages ?? [];

    const participantChoices = useMemo(() => users, [users]);

    useEffect(() => {
        if (!activeConversation || !(window as any).Echo) return;

        const channel = (window as any).Echo.private(`conversation.${activeConversation.id}`);
        channel.listen('.message.sent', () => {
            router.reload({ only: ['activeConversation', 'conversations'] });
        });

        return () => {
            (window as any).Echo.leave(`private-conversation.${activeConversation.id}`);
        };
    }, [activeConversation]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('messages.title')} />
            <div className="flex h-full flex-1 overflow-hidden rounded-xl border">
                <div className="w-full flex-col border-e md:flex md:w-80 lg:w-96">
                    <div className="flex items-center justify-between gap-2 border-b px-3 py-3">
                        <span className="text-sm font-medium">{t('messages.title')}</span>
                        <Button type="button" size="sm" variant="outline" onClick={() => setShowComposer((v) => !v)}>
                            {t('common.create')}
                        </Button>
                    </div>

                    {showComposer ? (
                        <div className="space-y-2 border-b p-3">
                            <Input
                                value={conversationForm.data.title}
                                onChange={(e) => conversationForm.setData('title', e.target.value)}
                                placeholder={t('common.search')}
                            />
                            <select
                                multiple
                                className="h-24 w-full rounded-md border p-2 text-sm"
                                onChange={(e) => {
                                    const ids = Array.from(e.target.selectedOptions).map((opt) => Number(opt.value));
                                    conversationForm.setData('participant_ids', ids);
                                }}
                            >
                                {participantChoices.map((u) => (
                                    <option value={u.id} key={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </select>
                            <Button
                                type="button"
                                size="sm"
                                onClick={() => conversationForm.post(route('messages.conversations.store'), { onSuccess: () => setShowComposer(false) })}
                            >
                                {t('common.create')}
                            </Button>
                        </div>
                    ) : null}

                    <div className="flex-1 overflow-y-auto">
                        {conversations.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                                <MessageSquare className="text-muted-foreground/50 h-8 w-8" aria-hidden />
                                <p className="text-muted-foreground text-sm">{t('messages.empty')}</p>
                            </div>
                        ) : (
                            conversations.data.map((conv, idx) => (
                                <div key={conv.id}>
                                    <Link
                                        className={`hover:bg-muted/50 flex w-full items-start gap-3 px-3 py-3 text-start transition-colors ${activeConversation?.id === conv.id ? 'bg-muted' : ''}`}
                                        href={route('messages.show', conv.id)}
                                    >
                                        <Avatar className="h-10 w-10 shrink-0">
                                            <AvatarFallback className="bg-accent/10 text-accent text-xs">{getInitials(conversationTitle(conv))}</AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center justify-between gap-1">
                                                <span className="truncate text-sm font-medium">{conversationTitle(conv)}</span>
                                            </div>
                                        </div>
                                    </Link>
                                    {idx < conversations.data.length - 1 && <Separator />}
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="flex-1 flex-col md:flex">
                    {activeConversation ? (
                        <>
                            <div className="flex h-14 items-center gap-3 border-b px-4">
                                <Button type="button" variant="ghost" size="icon" className="h-8 w-8 shrink-0 md:hidden" onClick={() => router.visit(route('messages.index'))}>
                                    <ArrowLeft className="h-4 w-4 rtl:rotate-180" aria-hidden />
                                </Button>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">{conversationTitle(activeConversation)}</p>
                                </div>
                            </div>
                            <div className="flex-1 overflow-y-auto p-4">
                                <div className="flex h-full flex-col justify-end gap-3">
                                    {selectedMessages.map((message) => (
                                        <div key={message.id} className="bg-muted text-muted-foreground max-w-[85%] rounded-2xl px-3 py-2 text-sm">
                                            <span className="font-medium">{message.sender.name}: </span>
                                            {message.body}
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="border-t p-3">
                                <div className="bg-background focus-within:ring-ring flex items-end gap-2 rounded-xl border px-3 py-2 focus-within:ring-1">
                                    <Input
                                        className="flex-1 border-0 bg-transparent px-0 text-sm shadow-none focus-visible:ring-0"
                                        placeholder={t('messages.type_message')}
                                        value={messageForm.data.body}
                                        onChange={(e) => messageForm.setData('body', e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && !e.shiftKey) {
                                                e.preventDefault();
                                                messageForm.post(route('messages.messages.store', activeConversation.id), { preserveScroll: true, onSuccess: () => messageForm.reset() });
                                            }
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        size="icon"
                                        className="h-8 w-8 shrink-0"
                                        onClick={() => messageForm.post(route('messages.messages.store', activeConversation.id), { preserveScroll: true, onSuccess: () => messageForm.reset() })}
                                        disabled={!messageForm.data.body.trim()}
                                        aria-label={t('messages.send')}
                                    >
                                        <SendHorizonal className="h-4 w-4 rtl:rotate-180" aria-hidden />
                                    </Button>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="flex flex-1 flex-col items-center justify-center gap-3 text-center">
                            <MessageSquare className="text-muted-foreground/40 h-10 w-10" aria-hidden />
                            <p className="text-muted-foreground text-sm">{t('messages.no_conversation_selected')}</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
