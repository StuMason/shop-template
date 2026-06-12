import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes/admin';

type Metrics = {
    revenue: string;
    orders: number;
    aov: string;
    abandonment_rate: number | null;
    repeat_rate: number | null;
};

function MetricCard({
    label,
    month,
    week,
}: {
    label: string;
    month: string | number;
    week: string | number;
}) {
    return (
        <div className="rounded-xl border p-4">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold tracking-tight">
                {month}
            </p>
            <p className="text-xs text-muted-foreground">{week} this week</p>
        </div>
    );
}

export default function AdminDashboard({
    month,
    week,
}: {
    month: Metrics;
    week: Metrics;
}) {
    const rate = (value: number | null) => (value !== null ? `${value}%` : '—');

    return (
        <>
            <Head title="Admin" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Last 30 days
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        The five numbers that matter. A weekly digest of these
                        lands in your inbox every Monday.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <MetricCard
                        label="Revenue"
                        month={month.revenue}
                        week={week.revenue}
                    />
                    <MetricCard
                        label="Orders"
                        month={month.orders}
                        week={week.orders}
                    />
                    <MetricCard
                        label="Average order"
                        month={month.aov}
                        week={week.aov}
                    />
                    <MetricCard
                        label="Basket abandonment"
                        month={rate(month.abandonment_rate)}
                        week={rate(week.abandonment_rate)}
                    />
                    <MetricCard
                        label="Repeat customers"
                        month={rate(month.repeat_rate)}
                        week={rate(week.repeat_rate)}
                    />
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
    ],
};
