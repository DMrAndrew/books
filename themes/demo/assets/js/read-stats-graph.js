function initGraph(container,labels,data) {
    const chart = document.getElementById(container);
    const ctx = chart.getContext('2d');


    const gradient = ctx.createLinearGradient(0, 0, 0, 450);
    gradient.addColorStop(0, 'rgba(206, 185, 253, 0.33)');
    gradient.addColorStop(1, 'rgba(105, 56, 221, 0)');

    new Chart(chart, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '',
                data: data,
                borderColor: '#6938DD',
                pointColor: '#6938DD',
                pointRadius: 4,
                pointBackgroundColor: '#F3EEFE',
                cubicInterpolationMode: 'monotone',
                fill: true,
                backgroundColor: gradient,
            }]
        },
        options: {

            scales: {
                y: {
                    beginAtZero: true,
                },
                x: {
                    ticks: {
                        autoSkip: false,
                        maxRotation: 90,
                        minRotation: 90
                    }
                }
            },
            plugins: {
                legend: {
                    display: false,
                },
            },
        }
    });
}
