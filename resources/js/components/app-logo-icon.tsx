import type { ComponentProps } from 'react';

export default function AppLogoIcon({
    alt = '',
    ...props
}: ComponentProps<'img'>) {
    return <img {...props} src="/SPUP-final-logo.png" alt={alt} />;
}
