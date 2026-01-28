/**
 * Reports Page - Chart Initialization and Management
 * EnduraCore Gym Management System
 */

// Chart instances storage
let paymentChart = null;
let membershipChart = null;
let equipmentChart = null;

/**
 * Initialize Payment Method Chart
 * @param {Array} labels - Payment method names
 * @param {Array} data - Revenue amounts per method
 */
function initPaymentMethodChart(labels, data) {
    const canvas = document.getElementById('paymentMethodChart');
    if (!canvas) {
        console.error('Payment method canvas not found');
        return;
    }
    
    if (!labels || labels.length === 0) {
        console.log('No payment method data to display');
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get canvas context');
        return;
    }
    
    // Destroy existing chart if it exists
    if (paymentChart) {
        paymentChart.destroy();
    }

    try {
        paymentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#11998e',
                        '#38ef7d',
                        '#fa709a',
                        '#fee140'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '$' + context.parsed.toFixed(2);
                                
                                // Calculate percentage
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                label += ' (' + percentage + '%)';
                                
                                return label;
                            }
                        }
                    }
                }
            }
        });
        console.log('Payment method chart created successfully');
    } catch (error) {
        console.error('Error creating payment method chart:', error);
    }
}

/**
 * Initialize Membership Distribution Chart
 * @param {Array} labels - Membership type names
 * @param {Array} data - Member counts per type
 */
function initMembershipChart(labels, data) {
    const canvas = document.getElementById('membershipChart');
    if (!canvas) {
        console.error('Membership canvas not found');
        return;
    }
    
    if (!labels || labels.length === 0) {
        console.log('No membership data to display');
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get canvas context');
        return;
    }
    
    // Destroy existing chart if it exists
    if (membershipChart) {
        membershipChart.destroy();
    }

    try {
        membershipChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#36a2eb',
                        '#ff6384',
                        '#ffce56',
                        '#4bc0c0',
                        '#9966ff',
                        '#ff9f40'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' members';
                                
                                // Calculate percentage
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                label += ' (' + percentage + '%)';
                                
                                return label;
                            }
                        }
                    }
                }
            }
        });
        console.log('Membership chart created successfully');
    } catch (error) {
        console.error('Error creating membership chart:', error);
    }
}

/**
 * Initialize Equipment Status Chart
 * @param {Array} labels - Equipment condition labels
 * @param {Array} data - Equipment counts per condition
 */
function initEquipmentChart(labels, data) {
    const canvas = document.getElementById('equipmentChart');
    if (!canvas) {
        console.error('Equipment canvas not found');
        return;
    }
    
    if (!labels || labels.length === 0) {
        console.log('No equipment data to display');
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get canvas context');
        return;
    }
    
    // Destroy existing chart if it exists
    if (equipmentChart) {
        equipmentChart.destroy();
    }

    // Define colors based on condition
    const backgroundColors = labels.map(label => {
        const lowerLabel = label.toLowerCase();
        if (lowerLabel.includes('good') || lowerLabel.includes('excellent')) {
            return '#28a745'; // Green
        } else if (lowerLabel.includes('fair') || lowerLabel.includes('average')) {
            return '#ffc107'; // Yellow
        } else if (lowerLabel.includes('poor') || lowerLabel.includes('bad') || lowerLabel.includes('needs')) {
            return '#dc3545'; // Red
        }
        return '#6c757d'; // Default gray
    });

    try {
        equipmentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Equipment Count',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' equipment';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Condition'
                        }
                    }
                }
            }
        });
        console.log('Equipment chart created successfully');
    } catch (error) {
        console.error('Error creating equipment chart:', error);
    }
}

/**
 * Initialize all charts when DOM is ready
 */
function initializeCharts() {
    console.log('Initializing charts...');
    console.log('Chart data:', chartData);
    
    // Check if chart data is available in the global scope
    if (typeof chartData === 'undefined') {
        console.error('chartData is not defined!');
        return;
    }
    
    // Payment Method Chart
    if (chartData.paymentMethod && chartData.paymentMethod.labels && chartData.paymentMethod.labels.length > 0) {
        console.log('Initializing Payment Method Chart');
        initPaymentMethodChart(
            chartData.paymentMethod.labels,
            chartData.paymentMethod.data
        );
    } else {
        console.log('No payment method data available');
    }

    // Membership Chart
    if (chartData.membership && chartData.membership.labels && chartData.membership.labels.length > 0) {
        console.log('Initializing Membership Chart');
        initMembershipChart(
            chartData.membership.labels,
            chartData.membership.data
        );
    } else {
        console.log('No membership data available');
    }

    // Equipment Chart
    if (chartData.equipment && chartData.equipment.labels && chartData.equipment.labels.length > 0) {
        console.log('Initializing Equipment Chart');
        initEquipmentChart(
            chartData.equipment.labels,
            chartData.equipment.data
        );
    } else {
        console.log('No equipment data available');
    }
}

/**
 * Clean up charts before page unload
 */
function destroyCharts() {
    if (paymentChart) paymentChart.destroy();
    if (membershipChart) membershipChart.destroy();
    if (equipmentChart) equipmentChart.destroy();
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeCharts);

// Clean up on page unload
window.addEventListener('beforeunload', destroyCharts);