@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Memory & System Monitoring</h2>
                
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Memory Usage -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Memory Usage</h3>
                                <p class="text-2xl font-semibold text-gray-900" id="memory-usage">-</p>
                                <p class="text-sm text-gray-500" id="memory-percent">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CPU Usage -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6h2m7-6h2m2 6h2M5 9h14M5 15h14M12 3v2m0 14v2M9 9h6v6H9V9z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">CPU Usage</h3>
                                <p class="text-2xl font-semibold text-gray-900" id="cpu-usage">-</p>
                                <p class="text-sm text-gray-500">Average: <span id="cpu-avg">-</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Queue Workers -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Queue Workers</h3>
                                <p class="text-2xl font-semibold text-gray-900" id="worker-count">-</p>
                                <p class="text-sm text-gray-500">Queue: <span id="queue-size">-</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Disk Usage -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 1.1.9 2 2 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-4.93l-1.25-1.25A2 2 0 0010.07 5H6a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Disk Usage</h3>
                                <p class="text-2xl font-semibold text-gray-900" id="disk-usage">-</p>
                                <p class="text-sm text-gray-500">Free: <span id="disk-free">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Time Range Selector -->
                <div class="mb-6 flex justify-end">
                    <div class="inline-flex rounded-md shadow-sm">
                        <button onclick="updateCharts(1)" class="px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            1h
                        </button>
                        <button onclick="updateCharts(6)" class="px-4 py-2 -ml-px text-sm font-medium border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            6h
                        </button>
                        <button onclick="updateCharts(24)" class="px-4 py-2 -ml-px text-sm font-medium border border-gray-200 bg-blue-100 text-blue-700 hover:bg-blue-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            24h
                        </button>
                        <button onclick="updateCharts(72)" class="px-4 py-2 -ml-px text-sm font-medium rounded-r-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            3d
                        </button>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Memory Usage Chart -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Memory Usage (MB)</h3>
                        <canvas id="memoryChart" height="250"></canvas>
                    </div>
                    
                    <!-- CPU Usage Chart -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">CPU & Disk Usage (%)</h3>
                        <canvas id="cpuDiskChart" height="250"></canvas>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Queue Workers Chart -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Queue Workers & Jobs</h3>
                        <canvas id="queueChart" height="250"></canvas>
                    </div>
                    
                    <!-- System Stats -->
                    <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Statistics (24h)</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Memory Usage</span>
                                    <span id="memory-stats">-</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="memory-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>CPU Usage</span>
                                    <span id="cpu-stats">-</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="cpu-bar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Disk Usage</span>
                                    <span id="disk-stats">-</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="disk-bar" class="bg-purple-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-200">
                                <h4 class="font-medium text-gray-900 mb-2">Queue Statistics</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Current Queue Size</p>
                                        <p class="text-lg font-semibold" id="current-queue">-</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Active Workers</p>
                                        <p class="text-lg font-semibold" id="current-workers">-</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Max Queue (24h)</p>
                                        <p class="text-lg font-semibold" id="max-queue">-</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Max Workers (24h)</p>
                                        <p class="text-lg font-semibold" id="max-workers">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Last Updated -->
                <div class="mt-6 text-sm text-gray-500 text-right">
                    Last updated: <span id="last-updated">-</span>
                    <button onclick="fetchData()" class="ml-2 text-blue-600 hover:text-blue-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Global variables
    let memoryChart, cpuDiskChart, queueChart;
    let currentHours = 24; // Default to 24 hours
    
    // Initialize charts when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
        fetchData();
        
        // Refresh data every 30 seconds
        setInterval(fetchData, 30000);
    });
    
    // Initialize Chart.js instances
    function initializeCharts() {
        // Memory Usage Chart
        const memoryCtx = document.getElementById('memoryChart').getContext('2d');
        memoryChart = new Chart(memoryCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Memory Used (MB)',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Memory Peak (MB)',
                        data: [],
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'MB'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // CPU & Disk Usage Chart
        const cpuDiskCtx = document.getElementById('cpuDiskChart').getContext('2d');
        cpuDiskChart = new Chart(cpuDiskCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'CPU Usage (%)',
                        data: [],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Disk Usage (%)',
                        data: [],
                        borderColor: 'rgb(168, 85, 247)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Usage (%)'
                        },
                        min: 0,
                        max: 100
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Queue Workers Chart
        const queueCtx = document.getElementById('queueChart').getContext('2d');
        queueChart = new Chart(queueCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Queue Size',
                        data: [],
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Active Workers',
                        data: [],
                        type: 'line',
                        borderColor: 'rgba(220, 38, 38, 1)',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Queue Size'
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Active Workers'
                        },
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Update charts with new data
    function updateCharts(hours) {
        currentHours = hours;
        fetchData();
        
        // Update active button
        document.querySelectorAll('[onclick^="updateCharts"]').forEach(btn => {
            btn.classList.remove('bg-blue-100', 'text-blue-700');
            btn.classList.add('bg-white', 'text-gray-700');
        });
        
        // Highlight active button
        const activeBtn = document.querySelector(`[onclick="updateCharts(${hours})"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-gray-700');
            activeBtn.classList.add('bg-blue-100', 'text-blue-700');
        }
    }
    
    // Fetch data from the API
    function fetchData() {
        fetch(`/api/memory/status?hours=${currentHours}`)
            .then(response => response.json())
            .then(data => {
                updateDashboard(data);
                updateChartsData(data);
                updateStats(data);
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }
    
    // Update dashboard stats
    function updateDashboard(data) {
        // Memory Usage
        document.getElementById('memory-usage').textContent = data.memory.current_formatted;
        document.getElementById('memory-percent').textContent = `${data.memory.current_percent}% of ${data.memory.limit_formatted}`;
        
        // CPU Usage
        document.getElementById('cpu-usage').textContent = `${data.memory.cpu_usage || 0}%`;
        document.getElementById('cpu-avg').textContent = `${(data.stats.averages.cpu_usage || 0).toFixed(1)}%`;
        
        // Queue Workers
        document.getElementById('worker-count').textContent = data.workers.count;
        document.getElementById('queue-size').textContent = data.memory.queue_size || 0;
        
        // Disk Usage
        document.getElementById('disk-usage').textContent = `${data.memory.disk_usage || 0}%`;
        
        // Last Updated
        document.getElementById('last-updated').textContent = new Date().toLocaleString();
    }
    
    // Update chart data
    function updateChartsData(data) {
        const historical = data.historical || { labels: [] };
        
        // Update Memory Chart
        memoryChart.data.labels = historical.labels || [];
        memoryChart.data.datasets[0].data = historical.memory_used || [];
        memoryChart.data.datasets[1].data = historical.memory_peak || [];
        memoryChart.update();
        
        // Update CPU & Disk Chart
        cpuDiskChart.data.labels = historical.labels || [];
        cpuDiskChart.data.datasets[0].data = historical.cpu_usage || [];
        cpuDiskChart.data.datasets[1].data = historical.disk_usage || [];
        cpuDiskChart.update();
        
        // Update Queue Chart
        queueChart.data.labels = historical.labels || [];
        queueChart.data.datasets[0].data = historical.queue_size || [];
        queueChart.data.datasets[1].data = historical.active_workers || [];
        queueChart.update();
    }
    
    // Update statistics
    function updateStats(data) {
        const stats = data.stats || {};
        const current = stats.current || {};
        const averages = stats.averages || {};
        const max = stats.max || {};
        const min = stats.min || {};
        
        // Update memory stats
        document.getElementById('memory-stats').textContent = 
            `${current.memory_used ? current.memory_used.toFixed(1) : 0}MB (Avg: ${averages.memory_used ? averages.memory_used.toFixed(1) : 0}MB, Max: ${max.memory_used ? max.memory_used.toFixed(1) : 0}MB)`;
        document.getElementById('memory-bar').style.width = 
            `${Math.min(100, (current.memory_used / (data.memory.limit / (1024 * 1024)) * 100) || 0)}%`;
        
        // Update CPU stats
        document.getElementById('cpu-stats').textContent = 
            `${current.cpu_usage ? current.cpu_usage.toFixed(1) : 0}% (Avg: ${averages.cpu_usage ? averages.cpu_usage.toFixed(1) : 0}%, Max: ${max.cpu_usage ? max.cpu_usage.toFixed(1) : 0}%)`;
        document.getElementById('cpu-bar').style.width = 
            `${current.cpu_usage || 0}%`;
        
        // Update disk stats
        document.getElementById('disk-stats').textContent = 
            `${current.disk_usage ? current.disk_usage.toFixed(1) : 0}% (Avg: ${averages.disk_usage ? averages.disk_usage.toFixed(1) : 0}%, Max: ${max.disk_usage ? max.disk_usage.toFixed(1) : 0}%)`;
        document.getElementById('disk-bar').style.width = 
            `${current.disk_usage || 0}%`;
        
        // Update queue stats
        document.getElementById('current-queue').textContent = current.queue_size || 0;
        document.getElementById('current-workers').textContent = current.active_workers || 0;
        document.getElementById('max-queue').textContent = max.queue_size || 0;
        document.getElementById('max-workers').textContent = max.active_workers || 0;
    }
</script>
@endpush
@endsection
