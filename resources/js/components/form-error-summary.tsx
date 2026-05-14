import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export function FormErrorSummary({ errors }: { errors: Record<string, string | undefined> }) {
    const messages = Object.values(errors).filter(Boolean) as string[];

    if (messages.length === 0) {
        return null;
    }

    return (
        <Alert className="border-rose-200 bg-rose-50 text-rose-700">
            <AlertTitle>Please fix the highlighted fields</AlertTitle>
            <AlertDescription>
                <ul className="mt-2 space-y-1 text-sm">
                    {messages.map((message, index) => (
                        <li key={`${message}-${index}`}>{message}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}
