import { Download, ExternalLink } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    download as downloadEvidence,
    show as showEvidence,
} from '@/routes/monitoring/accomplishments/evidence';

type EvidenceListItem = {
    id: number;
    evidenceType: string | null;
    title: string | null;
    description: string | null;
    originalFilename: string;
    mimeType: string;
    fileSize: number;
    checksum: string | null;
    uploadedBy: {
        id: number;
        name: string;
    };
    createdAt: string | null;
};

function formatFileSize(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toLocaleString(undefined, {
            maximumFractionDigits: 1,
        })} MB`;
    }

    return `${Math.max(1, Math.ceil(bytes / 1024)).toLocaleString()} KB`;
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(value));
}

export function EvidenceList({
    teamSlug,
    reportingPeriodId,
    planItemId,
    accomplishmentId,
    evidence,
}: {
    teamSlug: string;
    reportingPeriodId: number;
    planItemId: number;
    accomplishmentId: number;
    evidence: ReadonlyArray<EvidenceListItem>;
}) {
    if (evidence.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No documentary evidence uploaded.
            </p>
        );
    }

    return (
        <ul className="grid gap-2">
            {evidence.map((item) => {
                const routeParameters: [
                    string,
                    number,
                    number,
                    number,
                    number,
                ] = [
                    teamSlug,
                    reportingPeriodId,
                    planItemId,
                    accomplishmentId,
                    item.id,
                ];

                return (
                    <li
                        key={item.id}
                        id={`evidence-${item.id}`}
                        className="flex scroll-mt-6 flex-col justify-between gap-3 rounded-lg border bg-background p-3 sm:flex-row sm:items-start"
                    >
                        <div className="min-w-0 space-y-1.5">
                            <div className="flex min-w-0 flex-wrap items-center gap-2">
                                <p className="min-w-0 truncate text-sm font-medium">
                                    {item.title || item.originalFilename}
                                </p>
                                {item.evidenceType && (
                                    <Badge variant="secondary">
                                        {item.evidenceType}
                                    </Badge>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {item.originalFilename} ·{' '}
                                {formatFileSize(item.fileSize)} ·{' '}
                                {item.uploadedBy.name}
                                {item.createdAt
                                    ? ` · ${formatDate(item.createdAt)}`
                                    : ''}
                            </p>
                            {item.description && (
                                <p className="text-sm leading-5 whitespace-pre-wrap text-muted-foreground">
                                    {item.description}
                                </p>
                            )}
                            {item.checksum && (
                                <p
                                    className="font-mono text-[11px] break-all text-muted-foreground"
                                    title={item.checksum}
                                >
                                    SHA-256 {item.checksum}
                                </p>
                            )}
                        </div>
                        <div className="flex shrink-0 gap-1">
                            <Button asChild size="icon" variant="ghost">
                                <a
                                    href={showEvidence.url(routeParameters)}
                                    target="_blank"
                                    rel="noreferrer"
                                    aria-label={`View ${item.originalFilename}`}
                                >
                                    <ExternalLink />
                                </a>
                            </Button>
                            <Button asChild size="icon" variant="ghost">
                                <a
                                    href={downloadEvidence.url(routeParameters)}
                                    aria-label={`Download ${item.originalFilename}`}
                                >
                                    <Download />
                                </a>
                            </Button>
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
