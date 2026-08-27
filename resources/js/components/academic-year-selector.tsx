import { router, usePage } from '@inertiajs/react';
import { CalendarRange } from 'lucide-react';
import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { select } from '@/routes/academic-years';

type Props = {
    className?: string;
};

export function AcademicYearSelector({ className }: Props) {
    const { academicYears, currentTeam, selectedAcademicYear } =
        usePage().props;
    const [processing, setProcessing] = useState(false);

    const selectAcademicYear = (value: string): void => {
        if (
            !currentTeam ||
            processing ||
            Number(value) === selectedAcademicYear?.id
        ) {
            return;
        }

        router.visit(select([currentTeam.slug, Number(value)]), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className={cn('min-w-0', className)}>
            <label htmlFor="academic-year-selector" className="sr-only">
                Active Academic Year
            </label>
            <Select
                value={
                    selectedAcademicYear
                        ? String(selectedAcademicYear.id)
                        : undefined
                }
                onValueChange={selectAcademicYear}
                disabled={
                    !currentTeam || academicYears.length === 0 || processing
                }
            >
                <SelectTrigger
                    id="academic-year-selector"
                    aria-label="Active Academic Year"
                    className="w-full min-w-0 bg-background shadow-none"
                    size="sm"
                >
                    <CalendarRange className="size-4 shrink-0 text-muted-foreground" />
                    <SelectValue placeholder="Select Academic Year" />
                </SelectTrigger>
                <SelectContent align="end" className="min-w-56">
                    {academicYears.map((academicYear) => (
                        <SelectItem
                            key={academicYear.id}
                            value={String(academicYear.id)}
                        >
                            <span className="flex min-w-0 items-center gap-2">
                                <span className="truncate">
                                    {academicYear.name}
                                </span>
                                {academicYear.isCurrent && (
                                    <span className="text-xs text-muted-foreground">
                                        Current
                                    </span>
                                )}
                            </span>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
