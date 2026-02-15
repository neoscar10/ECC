<script>
    (function() {
        window.ECCCharts = window.ECCCharts || {};

        const chartConfigs = {
            sales: {
                orders: (data) => ({
                    series: [{ name: 'Orders', data: data.orders }],
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    colors: ['#405189'],
                    plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '45%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: data.labels },
                    grid: { borderColor: '#f1f1f1' }
                }),
                revenue: (data) => ({
                    series: [{ name: 'Revenue', data: data.revenue }],
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    colors: ['#0ab39c'],
                    plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '45%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: data.labels },
                    yaxis: { labels: { formatter: (val) => "₹" + val.toLocaleString() } },
                    tooltip: { y: { formatter: (val) => "₹" + val.toLocaleString() } },
                    grid: { borderColor: '#f1f1f1' }
                })
            },
            membership: {
                tier: (data) => ({
                    series: data.tier.series,
                    labels: data.tier.labels,
                    chart: { type: 'donut', height: 280 },
                    colors: ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                status: (data) => ({
                    series: data.status.series,
                    labels: data.status.labels,
                    chart: { type: 'pie', height: 280 },
                    colors: ['#0ab39c', '#f7b84b', '#f06548', '#405189'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                trend: (data) => ({
                    series: [{ name: 'New Members', data: data.trend.series }],
                    chart: { type: 'area', height: 280, toolbar: { show: false } },
                    colors: ['#405189'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: { categories: data.trend.labels },
                    grid: { borderColor: '#f1f1f1' }
                })
            },
            auction: {
                status: (data) => ({
                    series: data.status.series,
                    labels: data.status.labels,
                    chart: { type: 'donut', height: 280 },
                    colors: ['#405189', '#0ab39c', '#f7b84b', '#f06548'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                trend: (data) => ({
                    series: [{ name: 'Bids', data: data.trend.series }],
                    chart: { type: 'area', height: 280, toolbar: { show: false } },
                    colors: ['#f7b84b'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: { categories: data.trend.labels },
                    grid: { borderColor: '#f1f1f1' }
                })
            },
            vault: {
                status: (data) => ({
                    series: data.status.series,
                    labels: data.status.labels,
                    chart: { type: 'donut', height: 280 },
                    colors: ['#405189', '#f06548'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                trend: (data) => ({
                    series: [{ name: 'Items', data: data.trend.series }],
                    chart: { type: 'area', height: 280, toolbar: { show: false } },
                    colors: ['#299cdb'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: { categories: data.trend.labels },
                    grid: { borderColor: '#f1f1f1' }
                })
            }
        };

        const renderChart = (type, id, data) => {
            const container = document.getElementById(id);
            if (!container) return;

            const reportType = type.split(':')[0];
            const chartKey = type.split(':')[1];
            
            if (!chartConfigs[reportType] || !chartConfigs[reportType][chartKey]) return;

            const options = chartConfigs[reportType][chartKey](data);
            
            // Toggle empty state
            const emptyState = container.closest('.card-body')?.querySelector('.chart-empty-state');
            const hasData = options.series.some(s => {
                if (typeof s === 'number') return s > 0;
                return s.data && s.data.some(v => v > 0);
            });

            if (emptyState) emptyState.classList.toggle('d-none', hasData);
            container.classList.toggle('d-none', !hasData);

            if (!hasData) return;

            if (window.ECCCharts[id]) {
                window.ECCCharts[id].updateOptions(options);
            } else {
                window.ECCCharts[id] = new ApexCharts(container, options);
                window.ECCCharts[id].render();
            }
        };

        const initAllCharts = (report, payload) => {
            if (!payload) return;
            
            if (report === 'sales') {
                renderChart('sales:orders', 'orders_source_chart', payload);
                renderChart('sales:revenue', 'revenue_source_chart', payload);
            } else if (report === 'membership') {
                renderChart('membership:tier', 'tier_distribution_chart', payload);
                renderChart('membership:status', 'status_distribution_chart', payload);
                renderChart('membership:trend', 'membership_trend_chart', payload);
            } else if (report === 'auction') {
                renderChart('auction:status', 'status_donut_chart', payload);
                renderChart('auction:trend', 'bids_trend_chart', payload);
            } else if (report === 'vault') {
                renderChart('vault:status', 'vault_status_chart', payload);
                renderChart('vault:trend', 'vault_activity_chart', payload);
            }
        };

        // Listen for standardized Livewire event
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('reports:render-charts', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                initAllCharts(data.report, data.payload);
            });
        });

        // Re-init on navigation
        document.addEventListener('livewire:navigated', () => {
            // Cleanup existing instances to prevent memory leaks/zombies
            Object.values(window.ECCCharts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') chart.destroy();
            });
            window.ECCCharts = {};

            // Trigger a refresh after a short delay to ensure DOM is ready
            setTimeout(() => {
                Livewire.dispatch('reports:request-charts');
            }, 100);
        });
    })();
</script>
