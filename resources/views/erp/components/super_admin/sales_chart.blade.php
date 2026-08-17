<!-- 2. 6 Days Sales Graph Component -->
<div class="col-lg-5">
    <div class="sa-card h-100 mb-0" id="cardSalesChart">
        <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="sa-card-header">
            <h2 class="sa-card-title">
                <i class="fas fa-chart-area sa-icon-chart"></i>
                6 Days Sales Performance
            </h2>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn-chart-toggle active" id="toggleChartAmount" onclick="switchChartDataset('amount')">Sales Amount</button>
                <button type="button" class="btn-chart-toggle" id="toggleChartQty" onclick="switchChartDataset('qty')">Sales Quantity</button>
            </div>
        </div>

        <div style="position: relative; height: 310px; width: 100%;">
            <canvas id="salesSixDaysChart"></canvas>
        </div>
    </div>
</div>
