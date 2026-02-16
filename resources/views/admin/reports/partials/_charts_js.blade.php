<script>
    (function() {
        console.log("ECC Charts: Bootstrapper phase initialized.");

        window.ECCCharts = window.ECCCharts || {};

        const chartConfigs = {
            sales: {
                orders: (data) => ({
                    series: [{ name: 'Orders', data: data.orders || [] }],
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    colors: ['#405189'],
                    plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '45%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: data.labels || [] },
                    grid: { borderColor: '#f1f1f1' }
                }),
                revenue: (data) => ({
                    series: [{ name: 'Revenue', data: data.revenue || [] }],
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    colors: ['#0ab39c'],
                    plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '45%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: data.labels || [] },
                    yaxis: { labels: { formatter: (val) => "₹" + val.toLocaleString() } },
                    tooltip: { y: { formatter: (val) => "₹" + val.toLocaleString() } },
                    grid: { borderColor: '#f1f1f1' }
                })
            },
            membership: {
                tier: (data) => ({
                    series: data.series || [],
                    labels: data.labels || [],
                    chart: { type: 'donut', height: 280 },
                    colors: ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                status: (data) => ({
                    series: data.series || [],
                    labels: data.labels || [],
                    chart: { type: 'pie', height: 280 },
                    colors: ['#0ab39c', '#f7b84b', '#f06548', '#405189'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                trend: (data) => ({
                    series: [{ name: 'New Members', data: data.series || [] }],
                    chart: { type: 'area', height: 280, toolbar: { show: false } },
                    colors: ['#405189'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: { categories: data.labels || [] },
                    grid: { borderColor: '#f1f1f1' }
                })
            },
            auction: {
                status: (data) => ({
                    series: data.series || [],
                    labels: data.labels || [],
                    chart: { type: 'donut', height: 280 },
                    colors: ['#405189', '#0ab39c', '#f7b84b', '#f06548'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                trend: (data) => ({
                    series: [{ name: 'Bids', data: data.series || [] }],
                    chart: { type: 'area', height: 280, toolbar: { show: false } },
                    colors: ['#f7b84b'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: { categories: data.labels || [] },
                    grid: { borderColor: '#f1f1f1' }
                })
            },
            vault: {
                statusDistribution: (data) => ({
                    series: data.series || [],
                    labels: data.labels || [],
                    chart: { type: 'donut', height: 280 },
                    colors: ['#405189', '#f06548'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }),
                netCombo: (data) => {
                    const series = [
                        { name: 'Locked', type: 'column', data: data.locked || [] },
                        { name: 'Removed', type: 'column', data: data.removed || [] },
                        { name: 'Net Position', type: 'area', data: data.net || [] }
                    ];

                    const focusNoteEl = document.getElementById('vaultFocusNote');
                    if (focusNoteEl) {
                        if (data.meta && data.meta.focused) {
                            focusNoteEl.innerHTML = `<span class="badge bg-soft-info text-info"><i class="ri-focus-2-line align-bottom me-1"></i> ${data.meta.focus_note}</span>`;
                        } else {
                            focusNoteEl.innerHTML = '';
                        }
                    }

                    return {
                        series: series,
                        chart: { height: 320, type: 'line', stacked: true, toolbar: { show: false } },
                        stroke: { width: [0, 0, 4], curve: 'smooth' },
                        plotOptions: { 
                            bar: { 
                                columnWidth: '45%',
                                borderRadius: 4
                            } 
                        },
                        fill: {
                            type: ['solid', 'solid', 'gradient'],
                            gradient: {
                                shadeIntensity: 1,
                                inverseColors: false,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [20, 100, 100, 100]
                            }
                        },
                        colors: ['#405189', '#f06548', '#0ab39c'],
                        labels: data.categories || [],
                        markers: { size: [0, 0, 4], strokeWidth: 2, hover: { size: 6 } },
                        xaxis: { type: 'category', axisBorder: { show: false } },
                        yaxis: { title: { text: 'Vault Items' }, min: 0 },
                        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
                        legend: { position: 'top', horizontalAlign: 'right' },
                        tooltip: { shared: true, intersect: false }
                    };
                }
            }
        };

        const renderChart = (reportType, chartKey, containerId, payload) => {
            const container = document.getElementById(containerId);
            if (!container) {
                console.warn(`ECC Charts: Container #${containerId} not found.`);
                return;
            }

            // Resolve data from either direct payload or nested 'charts' key
            let chartData = payload[chartKey] || (payload.charts ? payload.charts[chartKey] : null);
            
            if (!chartData) {
                console.warn(`ECC Charts: No data found for ${reportType}:${chartKey} in payload.`);
                return;
            }

            const configFn = chartConfigs[reportType][chartKey];
            if (!configFn) return;

            const options = configFn(chartData);
            
            // Toggle empty state
            const cardBody = container.closest('.card-body');
            const emptyState = cardBody ? cardBody.querySelector('.chart-empty-state') : null;
            
            const hasData = options.series && options.series.some(s => {
                if (typeof s === 'number') return s > 0;
                return s.data && s.data.some(v => v > 0);
            });

            if (emptyState) emptyState.classList.toggle('d-none', hasData);
            container.classList.toggle('d-none', !hasData);

            if (!hasData) {
                if (window.ECCCharts[containerId]) {
                    window.ECCCharts[containerId].destroy();
                    delete window.ECCCharts[containerId];
                }
                return;
            }

            if (typeof ApexCharts === 'undefined') {
                console.error("ECC Charts: ApexCharts is NOT defined. Ensure it is loaded in the layout.");
                return;
            }

            try {
                if (window.ECCCharts[containerId]) {
                    window.ECCCharts[containerId].updateOptions(options);
                } else {
                    window.ECCCharts[containerId] = new ApexCharts(container, options);
                    window.ECCCharts[containerId].render();
                }
            } catch (e) {
                console.error(`ECC Charts: Error rendering ${containerId}:`, e);
            }
        };

        const initAllCharts = (report, payload) => {
            console.log(`ECC Charts: Initializing ${report} charts with payload:`, payload);
            
            if (report === 'sales') {
                renderChart('sales', 'orders', 'orders_source_chart', payload);
                renderChart('sales', 'revenue', 'revenue_source_chart', payload);
            } else if (report === 'membership') {
                renderChart('membership', 'tier', 'membersByTierChart', payload);
                renderChart('membership', 'status', 'statusBreakdownChart', payload);
                renderChart('membership', 'trend', 'signupsTrendChart', payload);
            } else if (report === 'auction') {
                renderChart('auction', 'status', 'status_donut_chart', payload);
                renderChart('auction', 'trend', 'bids_trend_chart', payload);
            } else if (report === 'vault') {
                renderChart('vault', 'statusDistribution', 'vault_status_chart', payload);
                renderChart('vault', 'netCombo', 'vaultNetTrendChart', payload);
            }
        };

        const setupListeners = () => {
            console.log("ECC Charts: Setup Livewire listeners.");
            
            Livewire.on('reports:render-charts', (event) => {
                console.log("ECC Charts: Received reports:render-charts event.");
                const data = Array.isArray(event) ? event[0] : event;
                initAllCharts(data.report, data.payload);
            });

            Livewire.on('reports:charts', (event) => {
                console.log("ECC Charts: Received reports:charts event.");
                const data = Array.isArray(event) ? event[0] : event;
                initAllCharts(data.report, data.payload);
            });

            // Initial request if navigating to a report page
            if (document.querySelector('[id$="Chart"]')) {
                console.log("ECC Charts: Found chart containers on load. Requesting data.");
                Livewire.dispatch('reports:request-charts');
            }
        };

        if (window.Livewire) {
            setupListeners();
        } else {
            document.addEventListener('livewire:init', setupListeners);
        }

        document.addEventListener('livewire:navigated', () => {
            console.log("ECC Charts: Livewire navigated. Clearing registry.");
            Object.values(window.ECCCharts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') chart.destroy();
            });
            window.ECCCharts = {};

            setTimeout(() => {
                if (document.querySelector('[id$="Chart"]')) {
                    console.log("ECC Charts: Post-nav request.");
                    Livewire.dispatch('reports:request-charts');
                }
            }, 150);
        });

        console.log("ECC Charts: Bootstrapper phase complete. ApexCharts status: " + (typeof ApexCharts !== 'undefined' ? "Available" : "NOT FOUND"));
    })();
</script>
