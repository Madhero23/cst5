<?php
// Ensure this file is being included by the main index.php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// Fetch dashboard data
$sales_query = "SELECT DATE(order_date) as date, SUM(total_amount) as daily_sales
                FROM orders 
                GROUP BY DATE(order_date)
                ORDER BY date DESC 
                LIMIT 7";
$sales_result = mysqli_query($conn, $sales_query);
?>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard Overview</h2>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Today's Sales</h5>
                    <h3 id="today-sales">₱0.00</h3>
                </div>
            </div>
        </div>
        // ...existing cards...
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sales Trend</h5>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        // ...existing charts...
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            // ...existing table...
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ...existing JavaScript for charts and data loading...
</script>
