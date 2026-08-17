<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    let salesChartInstance = null;
    let chartLabels = @json($sixDaysSalesChart['labels']);
    let chartAmounts = @json($sixDaysSalesChart['amounts']);
    let chartQuantities = @json($sixDaysSalesChart['quantities']);
    let currentDatasetType = 'amount';

    document.addEventListener('DOMContentLoaded', function () {
        initSixDaysChart();
    });

    function initSixDaysChart() {
        const ctx = document.getElementById('salesSixDaysChart').getContext('2d');
        const amountGradient = ctx.createLinearGradient(0, 0, 0, 300);
        amountGradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
        amountGradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

        salesChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Sales Amount (৳)',
                    data: chartAmounts,
                    borderColor: '#2563eb',
                    backgroundColor: amountGradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    if (label.includes('Amount')) {
                                        label += '৳' + context.parsed.y.toLocaleString();
                                    } else {
                                        label += context.parsed.y.toLocaleString() + ' pcs';
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { weight: '600', size: 11 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11 },
                            callback: function(value) {
                                return currentDatasetType === 'amount' ? ('৳' + value.toLocaleString()) : (value.toLocaleString() + ' pcs');
                            }
                        }
                    }
                }
            }
        });
    }

    function switchChartDataset(type) {
        currentDatasetType = type;
        const ctx = document.getElementById('salesSixDaysChart').getContext('2d');
        const btnAmount = document.getElementById('toggleChartAmount');
        const btnQty = document.getElementById('toggleChartQty');

        if (type === 'amount') {
            btnAmount.classList.add('active');
            btnQty.classList.remove('active');

            const amountGradient = ctx.createLinearGradient(0, 0, 0, 300);
            amountGradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
            amountGradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

            salesChartInstance.data.datasets[0] = {
                label: 'Sales Amount (৳)',
                data: chartAmounts,
                borderColor: '#2563eb',
                backgroundColor: amountGradient,
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8
            };
        } else {
            btnQty.classList.add('active');
            btnAmount.classList.remove('active');

            const qtyGradient = ctx.createLinearGradient(0, 0, 0, 300);
            qtyGradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            qtyGradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

            salesChartInstance.data.datasets[0] = {
                label: 'Sales Quantity (pcs)',
                data: chartQuantities,
                borderColor: '#10b981',
                backgroundColor: qtyGradient,
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#10b981',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8
            };
        }

        salesChartInstance.update();
    }

    function selectBranchFilter(branchId) {
        document.getElementById('branchSelect').value = branchId;
        triggerAjaxFilter();
    }

    function toggleOverlays(show) {
        document.querySelectorAll('.sa-card-overlay').forEach(el => {
            if (show) el.classList.add('active');
            else el.classList.remove('active');
        });
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    function triggerAjaxFilter() {
        const branchId = document.getElementById('branchSelect').value;
        const range = document.getElementById('rangeSelect').value;

        const branchSelectText = document.getElementById('branchSelect').options[document.getElementById('branchSelect').selectedIndex].text;
        document.getElementById('branchFilterLabel').innerHTML = `<i class="fas fa-filter me-1"></i> ${branchSelectText}`;

        toggleOverlays(true);

        const dataUrl = `{{ route('erp.super_admin.dashboard.data') }}?branch_id=${branchId}&range=${range}`;

        fetch(dataUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data) {
                updateDashboardDOM(res.data);
            }
        })
        .catch(err => {
            console.error('Failed to update dashboard data via AJAX', err);
        })
        .finally(() => {
            toggleOverlays(false);
        });
    }

    function updateDashboardDOM(data) {
        // 1. Today's Sales Branch Wise
        if (data.todaySalesBranchWise) {
            let tbodyHtml = '';
            data.todaySalesBranchWise.branches.forEach(bw => {
                tbodyHtml += `
                <tr>
                    <td class="fw-bold text-dark">
                        <i class="fas fa-building text-primary me-2 opacity-75"></i>${bw.branch_name}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border px-2 py-1">${formatNumber(bw.today_qty)} pcs</span>
                    </td>
                    <td class="text-end fw-bold text-dark">৳${formatNumber(bw.today_amount)}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border px-2 py-1">${formatNumber(bw.month_qty)} pcs</span>
                    </td>
                    <td class="text-end fw-bold text-success">৳${formatNumber(bw.month_amount)}</td>
                </tr>`;
            });
            document.getElementById('todaySalesTableBody').innerHTML = tbodyHtml;

            const t = data.todaySalesBranchWise.total;
            document.getElementById('todaySalesTableFoot').innerHTML = `
            <tr>
                <td>TOTAL</td>
                <td class="text-center">${formatNumber(t.today_qty)} pcs</td>
                <td class="text-end text-primary">৳${formatNumber(t.today_amount)}</td>
                <td class="text-center">${formatNumber(t.month_qty)} pcs</td>
                <td class="text-end text-success">৳${formatNumber(t.month_amount)}</td>
            </tr>`;
        }

        // 2. 6 Days Sales Chart
        if (data.sixDaysSalesChart) {
            chartLabels = data.sixDaysSalesChart.labels;
            chartAmounts = data.sixDaysSalesChart.amounts;
            chartQuantities = data.sixDaysSalesChart.quantities;

            salesChartInstance.data.labels = chartLabels;
            switchChartDataset(currentDatasetType);
        }

        // 3. Top Selling Products
        if (data.topSellingProducts) {
            let tpBody = '';
            data.topSellingProducts.forEach(tp => {
                const rankClass = tp.rank == 1 ? 'rank-1' : (tp.rank == 2 ? 'rank-2' : (tp.rank == 3 ? 'rank-3' : 'rank-other'));
                tpBody += `
                <tr>
                    <td class="text-center">
                        <span class="rank-badge ${rankClass}">${tp.rank}</span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="${tp.product}">${tp.product}</div>
                        <div class="small text-muted"><i class="fas fa-code-branch me-1"></i>${tp.branch}</div>
                    </td>
                    <td class="text-center fw-semibold">${formatNumber(tp.sold_qty)} pcs</td>
                    <td class="text-end fw-bold text-dark">৳${formatNumber(tp.sales_amount)}</td>
                </tr>`;
            });
            document.getElementById('topSellingTableBody').innerHTML = tpBody;
        }

        // 4. Branch Sales Statement
        if (data.branchSalesStatement) {
            let mHead = '<th>Branch</th>';
            data.branchSalesStatement.months.forEach(m => {
                mHead += `<th class="text-end">${m}</th>`;
            });
            mHead += '<th class="text-end">Year Total</th>';
            document.getElementById('branchStatementHead').innerHTML = mHead;

            let mBody = '';
            data.branchSalesStatement.rows.forEach(r => {
                mBody += `<tr><td class="fw-bold text-dark">${r.branch}</td>`;
                r.months.forEach(mv => {
                    mBody += `<td class="text-end">৳${formatNumber(mv)}</td>`;
                });
                mBody += `<td class="text-end fw-bold text-primary">৳${formatNumber(r.year_total)}</td></tr>`;
            });
            document.getElementById('branchStatementBody').innerHTML = mBody;

            let mFoot = '<tr><td>TOTAL</td>';
            data.branchSalesStatement.totals.months.forEach(tv => {
                mFoot += `<td class="text-end text-dark">৳${formatNumber(tv)}</td>`;
            });
            mFoot += `<td class="text-end text-success fs-6">৳${formatNumber(data.branchSalesStatement.totals.year_total)}</td></tr>`;
            document.getElementById('branchStatementFoot').innerHTML = mFoot;
        }

        // 5. Gross Sales Statement
        if (data.grossSalesStatement) {
            let gHead = '<th>Financial Metric</th>';
            data.grossSalesStatement.months.forEach(m => {
                gHead += `<th class="text-end">${m}</th>`;
            });
            gHead += '<th class="text-end">Year Total</th>';
            document.getElementById('grossStatementHead').innerHTML = gHead;

            let gBody = '';
            Object.keys(data.grossSalesStatement.rows).forEach(key => {
                const row = data.grossSalesStatement.rows[key];
                const trClass = key === 'gross_profit' ? 'table-light fw-bold' : '';
                const tdClass = key === 'gross_profit' ? 'text-primary' : 'text-dark';

                gBody += `<tr class="${trClass}"><td class="fw-bold ${tdClass}">${row.label}</td>`;

                row.values.forEach(val => {
                    if (row.format === 'qty') {
                        gBody += `<td class="text-end"><span class="fw-semibold text-secondary">${formatNumber(val)} pcs</span></td>`;
                    } else if (row.format === 'currency') {
                        gBody += `<td class="text-end">৳${formatNumber(val)}</td>`;
                    } else if (row.format === 'currency_highlight') {
                        gBody += `<td class="text-end"><span class="amount-positive">৳${formatNumber(val)}</span></td>`;
                    } else if (row.format === 'percent') {
                        const pillClass = val >= 40 ? 'profit-pill-high' : (val >= 25 ? 'profit-pill-mid' : 'profit-pill-low');
                        gBody += `<td class="text-end"><span class="profit-pill ${pillClass}">${val}%</span></td>`;
                    }
                });

                if (row.format === 'qty') {
                    gBody += `<td class="text-end fw-bold text-dark">${formatNumber(row.year_total)} pcs</td>`;
                } else if (row.format === 'currency') {
                    gBody += `<td class="text-end fw-bold">৳${formatNumber(row.year_total)}</td>`;
                } else if (row.format === 'currency_highlight') {
                    gBody += `<td class="text-end fw-bold"><span class="amount-positive fs-6">৳${formatNumber(row.year_total)}</span></td>`;
                } else if (row.format === 'percent') {
                    gBody += `<td class="text-end fw-bold"><span class="profit-pill profit-pill-high fs-6">${row.year_total}%</span></td>`;
                }
                gBody += '</tr>';
            });
            document.getElementById('grossStatementBody').innerHTML = gBody;
        }

        // 6. Expense Statement
        if (data.expenseStatement) {
            let eHead = '<th>Expense Category</th>';
            data.expenseStatement.months.forEach(m => {
                eHead += `<th class="text-end">${m}</th>`;
            });
            eHead += '<th class="text-end">Year Total</th>';
            document.getElementById('expenseStatementHead').innerHTML = eHead;

            let eBody = '';
            data.expenseStatement.categories.forEach(cat => {
                eBody += `<tr><td class="fw-bold text-dark"><i class="fas fa-tag text-muted me-2 opacity-50"></i>${cat.category}</td>`;
                cat.months.forEach(mv => {
                    eBody += `<td class="text-end">৳${formatNumber(mv)}</td>`;
                });
                eBody += `<td class="text-end fw-bold text-danger">৳${formatNumber(cat.year_total)}</td></tr>`;
            });
            document.getElementById('expenseStatementBody').innerHTML = eBody;

            let eFoot = '<tr><td>TOTAL EXPENSE</td>';
            data.expenseStatement.total.months.forEach(tv => {
                eFoot += `<td class="text-end text-danger fw-bold">৳${formatNumber(tv)}</td>`;
            });
            eFoot += `<td class="text-end text-danger fs-6 fw-extrabold">৳${formatNumber(data.expenseStatement.total.year_total)}</td></tr>`;
            document.getElementById('expenseStatementFoot').innerHTML = eFoot;
        }
    }
</script>
