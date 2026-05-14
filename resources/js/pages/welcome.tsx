import { Head, Link } from '@inertiajs/react';

// Route '/' now uses public/landing — this page is no longer routed.
export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen items-center justify-center">
                <Link href="/login">Go to login</Link>
            </div>
        </>
    );
}
