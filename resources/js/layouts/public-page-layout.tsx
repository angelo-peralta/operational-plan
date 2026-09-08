import type { PropsWithChildren } from 'react';

export default function PublicPageLayout({ children }: PropsWithChildren) {
    return (
        <div className="relative min-h-svh overflow-x-hidden bg-background text-foreground">
            <div
                aria-hidden="true"
                className="absolute inset-0 overflow-hidden"
            >
                <div className="absolute -top-32 -left-24 size-96 rounded-full bg-emerald-700/10 blur-3xl dark:bg-emerald-700/10" />
                <div className="absolute -right-32 -bottom-40 size-96 rounded-full bg-rose-200/30 blur-3xl dark:bg-rose-950/15" />
            </div>

            <div className="relative z-10 flex min-h-svh flex-col px-5 py-5 sm:px-8 lg:px-10">
                <header className="relative mx-auto h-14 w-full max-w-6xl shrink-0">
                    <img
                        src="/SPUP-final-logo.png"
                        alt="St. Paul University Philippines seal"
                        className="absolute top-1/2 left-1/2 size-10 -translate-x-1/2 -translate-y-1/2 object-contain sm:size-15"
                    />
                </header>

                <main className="flex flex-1 flex-col">{children}</main>

                <footer className="mx-auto w-full max-w-6xl border-t border-border/60 pt-4 text-center text-xs text-muted-foreground">
                    St. Paul University Philippines | Operational Plan System
                </footer>
            </div>
        </div>
    );
}
