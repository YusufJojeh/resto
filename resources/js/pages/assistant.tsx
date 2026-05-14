import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';

interface AssistantMessage {
    id: number;
    role: 'user' | 'assistant' | 'system';
    content: string;
}

interface AssistantConversation {
    id: number;
    title: string | null;
    messages?: AssistantMessage[];
}

interface Paginated<T> {
    data: T[];
}

interface Props {
    conversations: Paginated<AssistantConversation>;
    activeConversation: AssistantConversation | null;
}

interface EchoChannel {
    listen: (event: string, callback: (...args: unknown[]) => void) => EchoChannel;
    stopListening?: (event: string) => EchoChannel;
}

interface EchoInstance {
    private: (channel: string) => EchoChannel;
    leave: (channel: string) => void;
}

type WindowWithEcho = Window & {
    Echo?: EchoInstance;
};

function getCsrfToken(): string {
    const token = document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!token) {
        throw new Error('Missing CSRF token meta tag.');
    }

    return token;
}

export default function AssistantPage({ conversations, activeConversation }: Props) {
    const { t } = useTranslation();
    const page = usePage<SharedData>();
    const assistantMeta = page.props.assistantMeta;
    const [submitError, setSubmitError] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const activeConversationId = activeConversation?.id ?? null;
    const activeMessages = activeConversation?.messages ?? [];

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ai.title'),
            href: route('assistant.index'),
        },
    ];

    const createForm = useForm({
        title: '',
    });

    const messageForm = useForm({
        content: '',
    });

    useEffect(() => {
        if (!activeConversationId) {
            return;
        }

        const echo = (window as WindowWithEcho).Echo;

        if (!echo) {
            return;
        }

        const channelName = `assistant.${activeConversationId}`;
        const eventName = '.assistant.reply';

        const channel = echo.private(channelName);

        channel.listen(eventName, () => {
            router.reload({
                only: ['activeConversation', 'conversations'],

            });
        });

        return () => {
            channel.stopListening?.(eventName);
            echo.leave(channelName);
        };
    }, [activeConversationId]);

    const createConversation = (): void => {
        if (createForm.processing) {
            return;
        }

        createForm.post(route('assistant.conversations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
            },
        });
    };

    const sendMessage = async (): Promise<void> => {
        if (!activeConversationId || isSubmitting) {
            return;
        }

        const content = messageForm.data.content.trim();

        if (!content) {
            return;
        }

        messageForm.clearErrors();
        setSubmitError(null);
        setIsSubmitting(true);

        try {
            const response = await fetch(
                route('assistant.messages.store', {
                    conversation: activeConversationId,
                }),
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({
                        content,
                        current_path: window.location.pathname,
                        client_message_id:
                            typeof window.crypto?.randomUUID === 'function'
                                ? window.crypto.randomUUID()
                                : String(Date.now()),
                    }),
                },
            );

            const data = (await response.json().catch(() => null)) as
                | {
                      errors?: Record<string, string[] | string>;
                      message?: string;
                      error?: string;
                  }
                | null;

            if (!response.ok) {
                if (response.status === 422) {
                    const contentError =
                        typeof data?.errors?.content === 'string'
                            ? data.errors.content
                            : Array.isArray(data?.errors?.content)
                              ? data.errors.content[0]
                              : assistantMeta?.errors?.validation;

                    if (contentError) {
                        messageForm.setError('content', contentError);
                    }

                    setSubmitError(contentError || assistantMeta?.errors?.validation || t('common.error'));

                    return;
                }

                const backendMessage = data?.error || data?.message;
                const friendlyError =
                    response.status === 403
                        ? assistantMeta?.errors?.forbidden
                        : response.status === 429
                          ? assistantMeta?.errors?.throttled
                          : assistantMeta?.errors?.unavailable;

                throw new Error(backendMessage || friendlyError || `Assistant request failed with status ${response.status}`);
            }

            messageForm.reset('content');

            router.reload({
                only: ['activeConversation', 'conversations'],
            });
        } catch (error) {
            const errorMessage =
                error instanceof Error && error.message
                    ? error.message
                    : assistantMeta?.errors?.network || t('common.error');

            setSubmitError(errorMessage);
            console.error(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        sendMessage();
    };

    const handleMessageKeyDown = (event: KeyboardEvent<HTMLInputElement>): void => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        sendMessage();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('ai.title')} />

            <div className="app-page">
                <PageHeader
                    title={t('ai.title')}
                    description={assistantMeta?.permission_notice || t('ai.empty_state')}
                    actions={
                        <Button
                            type="button"
                            variant="outline"
                            onClick={createConversation}
                            disabled={createForm.processing}
                        >
                            {t('common.create')}
                        </Button>
                    }
                />

                <div className="mb-4 rounded-2xl border border-dashed px-4 py-3 text-sm text-muted-foreground">
                    <p>{assistantMeta?.permission_notice}</p>
                    <p className="mt-1">{assistantMeta?.module_notice}</p>
                </div>

                <div className="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
                    <Card>
                        <CardContent className="space-y-2 p-4">
                            {conversations.data.length > 0 ? (
                                conversations.data.map((conversation) => {
                                    const isActive = activeConversationId === conversation.id;

                                    return (
                                        <Link
                                            key={conversation.id}
                                            href={route('assistant.show', {
                                                conversation: conversation.id,
                                            })}
                                            preserveScroll
                                            className={[
                                                'block rounded-lg border p-3 text-sm transition',
                                                'hover:border-primary/60 hover:bg-muted/50',
                                                isActive ? 'border-primary bg-primary/5 text-primary' : 'border-border',
                                            ].join(' ')}
                                        >
                                            {conversation.title || `${t('ai.title')} #${conversation.id}`}
                                        </Link>
                                    );
                                })
                            ) : (
                                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                    {t('ai.empty_state')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="flex min-h-[70vh] overflow-hidden">
                        <CardContent className="flex min-h-0 flex-1 flex-col gap-5 p-6">
                            {activeConversation ? (
                                <>
                                    <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                                        {activeMessages.length > 0 ? (
                                            activeMessages.map((message) => {
                                                const isUser = message.role === 'user';

                                                return (
                                                    <div
                                                        key={message.id}
                                                        className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}
                                                    >
                                                        <div
                                                            className={[
                                                                'max-w-[85%] whitespace-pre-wrap break-words rounded-3xl px-4 py-3 text-sm leading-6',
                                                                isUser
                                                                    ? 'bg-primary text-primary-foreground'
                                                                    : 'border border-border/70 bg-card/70 text-foreground',
                                                            ].join(' ')}
                                                        >
                                                            {message.content}
                                                        </div>
                                                    </div>
                                                );
                                            })
                                        ) : (
                                        <div className="flex h-full items-center justify-center rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                                {assistantMeta?.module_notice || t('ai.empty_state')}
                                        </div>
                                    )}
                                    </div>

                                    <form
                                        onSubmit={handleSubmit}
                                        className="space-y-3 border-t border-border/60 pt-4"
                                    >
                                        {submitError ? <p className="text-sm text-destructive">{submitError}</p> : null}

                                        <div className="flex items-center gap-2">
                                            <Input
                                                value={messageForm.data.content}
                                                onChange={(event) => {
                                                    messageForm.setData('content', event.target.value);
                                                    messageForm.clearErrors('content');
                                                    setSubmitError(null);
                                                }}
                                                onKeyDown={handleMessageKeyDown}
                                                placeholder={t('ai.placeholder')}
                                                className="h-11"
                                                autoComplete="off"
                                            />

                                            <Button
                                                type="submit"
                                                className="h-11"
                                                disabled={isSubmitting || !messageForm.data.content.trim()}
                                            >
                                                {t('ai.send')}
                                            </Button>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            {(assistantMeta?.starter_prompts || []).map((prompt) => (
                                                <Button
                                                    key={prompt}
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => messageForm.setData('content', prompt)}
                                                >
                                                    {prompt}
                                                </Button>
                                            ))}
                                        </div>
                                    </form>
                                </>
                            ) : (
                                <div className="flex flex-1 items-center justify-center rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                    {t('ai.empty_state')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
