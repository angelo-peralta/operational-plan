import { Form, Head } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import PublicPageLayout from '@/layouts/public-page-layout';
import { register } from '@/routes';
import { redirect as microsoftRedirect } from '@/routes/auth/microsoft';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { TeamInvitationContext } from '@/types';

type Props = {
    status?: string;
    canResetPassword: boolean;
    teamInvitation?: TeamInvitationContext | null;
    errors?: Partial<Record<'email' | 'microsoft' | 'password', string>>;
};

export default function Login({
    status,
    canResetPassword,
    teamInvitation,
    errors: pageErrors,
}: Props) {
    const shouldOpenLocalAccess = Boolean(
        teamInvitation || pageErrors?.email || pageErrors?.password,
    );

    return (
        <>
            <Head title="Sign in">
                <meta
                    name="description"
                    content="Sign in to the St. Paul University Philippines Operational Plan System."
                />
            </Head>

            <PublicPageLayout>
                <section className="flex flex-1 items-center justify-center py-8">
                    <div className="w-full max-w-[370px] space-y-5 text-center sm:-translate-y-6 sm:p-6">
                        <div className="space-y-3">
                            <h1 className="text-2xl leading-tight font-medium">
                                Welcome to SPUP
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Use your Microsoft account to access the
                                Operational Plan System.
                            </p>
                        </div>

                        <Button
                            asChild
                            className="h-10 w-full bg-emerald-700 text-sm text-white hover:bg-emerald-800 focus-visible:ring-emerald-600 dark:bg-emerald-800 dark:hover:bg-emerald-700"
                        >
                            <a
                                href={microsoftRedirect.url()}
                                data-test="microsoft-sign-in"
                            >
                                <MicrosoftMark />
                                Sign in with Microsoft
                            </a>
                        </Button>

                        <InputError
                            message={pageErrors?.microsoft}
                            className="rounded-md bg-destructive/10 px-3 py-2 text-center"
                        />

                        {status ? (
                            <p className="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                                {status}
                            </p>
                        ) : null}

                        <p className="text-xs text-muted-foreground">
                            Having trouble signing in?{' '}
                            <a
                                href="mailto:support@spup.edu.ph"
                                className="font-medium text-emerald-700 underline underline-offset-4 hover:text-emerald-800 focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:text-emerald-500 dark:hover:text-emerald-400"
                            >
                                Contact support
                            </a>
                        </p>

                        <details
                            className="group text-left"
                            open={shouldOpenLocalAccess || undefined}
                        >
                            <summary className="mx-auto flex w-fit cursor-pointer list-none items-center gap-1 rounded-sm text-xs text-muted-foreground focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none [&::-webkit-details-marker]:hidden">
                                Local account access
                                <ChevronDown className="size-3.5 transition-transform group-open:rotate-180" />
                            </summary>

                            <div className="mt-4 rounded-xl border bg-card/80 p-4 shadow-sm backdrop-blur-sm">
                                {teamInvitation ? (
                                    <TeamInvitationAlert
                                        invitation={teamInvitation}
                                        action="Log in"
                                    />
                                ) : null}

                                <div className={teamInvitation ? 'mt-5' : ''}>
                                    <PasskeyVerify separator="Or use email and password" />
                                </div>

                                <Form
                                    {...store.form()}
                                    resetOnSuccess={['password']}
                                    className="mt-5 flex flex-col gap-5"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="email">
                                                    Email address
                                                </Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoComplete="email"
                                                    placeholder="email@example.com"
                                                />
                                                <InputError
                                                    message={errors.email}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <div className="flex items-center">
                                                    <Label htmlFor="password">
                                                        Password
                                                    </Label>
                                                    {canResetPassword ? (
                                                        <TextLink
                                                            href={request()}
                                                            className="ml-auto text-xs"
                                                        >
                                                            Forgot password?
                                                        </TextLink>
                                                    ) : null}
                                                </div>
                                                <PasswordInput
                                                    id="password"
                                                    name="password"
                                                    required
                                                    autoComplete="current-password"
                                                    placeholder="Password"
                                                />
                                                <InputError
                                                    message={errors.password}
                                                />
                                            </div>

                                            <div className="flex items-center gap-3">
                                                <Checkbox
                                                    id="remember"
                                                    name="remember"
                                                />
                                                <Label htmlFor="remember">
                                                    Remember me
                                                </Label>
                                            </div>

                                            <Button
                                                type="submit"
                                                variant="outline"
                                                className="w-full"
                                                disabled={processing}
                                                data-test="login-button"
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : null}
                                                Log in locally
                                            </Button>

                                            <p className="text-center text-xs text-muted-foreground">
                                                Need a local account?{' '}
                                                <TextLink
                                                    href={register({
                                                        query: {
                                                            invitation:
                                                                teamInvitation?.code,
                                                        },
                                                    })}
                                                    data-test="register-link"
                                                >
                                                    Sign up
                                                </TextLink>
                                            </p>
                                        </>
                                    )}
                                </Form>
                            </div>
                        </details>
                    </div>
                </section>
            </PublicPageLayout>
        </>
    );
}

function MicrosoftMark() {
    return (
        <span
            aria-hidden="true"
            className="grid size-4 shrink-0 grid-cols-2 gap-0.5"
        >
            <span className="bg-[#f25022]" />
            <span className="bg-[#7fba00]" />
            <span className="bg-[#00a4ef]" />
            <span className="bg-[#ffb900]" />
        </span>
    );
}
