import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTranslation } from '@/i18n/use-translation';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { SendHorizontal, Sparkles, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';

interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
}

interface AiAssistantPanelProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

function createMessageId(): number {
    return Date.now() + Math.floor(Math.random() * 1000);
}

function getCsrfToken(): string {
    const token = document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!token) {
        throw new Error('Missing CSRF token meta tag.');
    }

    return token;
}

export function AiAssistantPanel({ open, onOpenChange }: AiAssistantPanelProps) {
    const { t, dir } = useTranslation();
    const page = usePage<SharedData>();
    const assistantMeta = page.props.assistantMeta;

    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [processing, setProcessing] = useState(false);
    const [loadingIndex, setLoadingIndex] = useState(0);

    const endRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        endRef.current?.scrollIntoView({
            behavior: 'smooth',
            block: 'end',
        });
    }, [messages]);

    useEffect(() => {
        if (!processing || !assistantMeta?.loading_messages?.length) {
            return;
        }

        const interval = window.setInterval(() => {
            setLoadingIndex((current) => (current + 1) % assistantMeta.loading_messages.length);
        }, 1200);

        return () => window.clearInterval(interval);
    }, [assistantMeta?.loading_messages, processing]);

    useEffect(() => {
        if (!processing || !assistantMeta?.loading_messages?.length) {
            return;
        }

        setMessages((previousMessages) => {
            const nextMessages = [...previousMessages];
            const lastAssistantIndex = [...nextMessages].reverse().findIndex((message) => message.role === 'assistant');

            if (lastAssistantIndex === -1) {
                return previousMessages;
            }

            const resolvedIndex = nextMessages.length - 1 - lastAssistantIndex;
            nextMessages[resolvedIndex] = {
                ...nextMessages[resolvedIndex],
                content: assistantMeta.loading_messages[loadingIndex] || nextMessages[resolvedIndex].content,
            };

            return nextMessages;
        });
    }, [assistantMeta?.loading_messages, loadingIndex, processing]);

    const sendMessage = async (text: string): Promise<void> => {
        const content = text.trim();

        if (!content || processing) {
            return;
        }

        const userMessage: Message = {
            id: createMessageId(),
            role: 'user',
            content,
        };

        const temporaryAssistantMessage: Message = {
            id: createMessageId(),
            role: 'assistant',
            content: assistantMeta?.loading_messages?.[0] || t('ai.thinking'),
        };

        setMessages((previousMessages) => [
            ...previousMessages,
            userMessage,
            temporaryAssistantMessage,
        ]);

        setInput('');
        setProcessing(true);

        try {
            const csrfToken = getCsrfToken();

            const response = await fetch(route('assistant.panel.messages.store'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    content,
                    current_path: window.location.pathname,
                    client_message_id:
                        typeof window.crypto?.randomUUID === 'function'
                            ? window.crypto.randomUUID()
                            : String(createMessageId()),
                }),
            });

            const data = (await response.json().catch(() => null)) as
                | {
                      message?: {
                          role?: 'assistant';
                          content?: string;
                      };
                      errors?: Record<string, string[] | string>;
                      message_text?: string;
                      messageText?: string;
                      error?: string;
                  }
                | null;

            if (!response.ok) {
                const backendMessage =
                    data?.error ||
                    data?.message_text ||
                    data?.messageText ||
                    (typeof data?.errors?.content === 'string'
                        ? data.errors.content
                        : Array.isArray(data?.errors?.content)
                          ? data.errors.content[0]
                          : null);

                const friendlyError =
                    response.status === 403
                        ? assistantMeta?.errors?.forbidden
                        : response.status === 429
                          ? assistantMeta?.errors?.throttled
                          : response.status === 422
                            ? assistantMeta?.errors?.validation
                            : assistantMeta?.errors?.unavailable;

                throw new Error(backendMessage || friendlyError || `Assistant request failed with status ${response.status}`);
            }

            const assistantContent =
                data?.message?.content?.trim() || t('common.error');

            setMessages((previousMessages) =>
                previousMessages.map((message) =>
                    message.id === temporaryAssistantMessage.id
                        ? {
                              ...message,
                              content: assistantContent,
                          }
                        : message,
                ),
            );
        } catch (error) {
            const errorMessage =
                error instanceof Error && error.message
                    ? error.message
                    : assistantMeta?.errors?.network || t('common.error');

            setMessages((previousMessages) =>
                previousMessages.map((message) =>
                    message.id === temporaryAssistantMessage.id
                        ? {
                              ...message,
                              content: errorMessage,
                          }
                        : message,
                ),
            );

            console.error(error);
        } finally {
            setProcessing(false);
        }
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>): void => {
        if (event.key !== 'Enter' || event.shiftKey) {
            return;
        }

        event.preventDefault();
        void sendMessage(input);
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side={dir === 'rtl' ? 'left' : 'right'}
                className="flex w-full max-w-sm flex-col p-0 sm:max-w-md"
            >
                <SheetHeader className="flex flex-row items-center justify-between border-b px-4 py-3">
                    <div className="flex items-center gap-2">
                        <Sparkles className="h-5 w-5 text-accent" aria-hidden="true" />

                        <div>
                            <SheetTitle className="text-base">{t('ai.title')}</SheetTitle>
                            <SheetDescription className="sr-only">
                                {t('ai.placeholder')}
                            </SheetDescription>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => onOpenChange(false)}
                        aria-label={t('common.close')}
                    >
                        <X className="h-4 w-4" aria-hidden="true" />
                    </Button>
                </SheetHeader>

                <div className="flex min-h-0 flex-1 flex-col overflow-hidden">
                    {messages.length === 0 ? (
                        <div className="flex flex-1 flex-col items-center justify-center gap-6 px-6 py-8 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-accent/10">
                                <Sparkles className="h-8 w-8 text-accent" aria-hidden="true" />
                            </div>

                            <div>
                                <p className="text-sm font-medium">{t('ai.empty_state')}</p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {assistantMeta?.permission_notice || t('ai.placeholder')}
                                </p>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {assistantMeta?.module_notice}
                                </p>
                            </div>

                            <div className="flex flex-wrap justify-center gap-2">
                                {(assistantMeta?.starter_prompts?.length
                                    ? assistantMeta.starter_prompts
                                    : [t('ai.suggestion_orders'), t('ai.suggestion_stock'), t('ai.suggestion_revenue')]).map((suggestion) => (
                                    <button
                                        key={suggestion}
                                        type="button"
                                        onClick={() => void sendMessage(suggestion)}
                                        disabled={processing}
                                        className="rounded-full border px-3 py-1.5 text-xs transition-colors hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {suggestion}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                            <div className="flex flex-col gap-3">
                                {messages.map((message) => {
                                    const isUser = message.role === 'user';

                                    return (
                                        <div
                                            key={message.id}
                                            className={cn(
                                                'max-w-[85%] whitespace-pre-wrap break-words rounded-2xl px-3 py-2 text-sm leading-6',
                                                isUser
                                                    ? 'ms-auto bg-accent text-accent-foreground'
                                                    : 'me-auto bg-muted text-muted-foreground',
                                            )}
                                        >
                                            {message.content}
                                        </div>
                                    );
                                })}

                                <div ref={endRef} />
                            </div>
                        </div>
                    )}

                    <div className="border-t px-3 py-3">
                        <div className="mb-2 rounded-lg border border-dashed px-3 py-2 text-xs text-muted-foreground">
                            {(assistantMeta?.read_only ? 'Read-only. ' : '') + (assistantMeta?.permission_notice || '')}
                        </div>
                        <div className="flex items-end gap-2 rounded-xl border bg-background px-3 py-2 focus-within:ring-1 focus-within:ring-ring">
                            <textarea
                                className="max-h-32 min-h-8 flex-1 resize-none bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                                placeholder={t('ai.placeholder')}
                                rows={1}
                                value={input}
                                onChange={(event) => setInput(event.target.value)}
                                onKeyDown={handleKeyDown}
                                disabled={processing}
                                aria-label={t('ai.placeholder')}
                            />

                            <Button
                                type="button"
                                size="icon"
                                className="h-8 w-8 shrink-0"
                                onClick={() => void sendMessage(input)}
                                disabled={processing || !input.trim()}
                                aria-label={t('ai.send')}
                            >
                                <SendHorizontal className="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                            </Button>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
