/* Dashboard dinâmico de projetos */
(function () {
  const data = window.projetosDashboard || {};

  function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach((el) => {
      if (el.dataset.counterAnimated === 'true') return;

      const target = Number(el.dataset.counter || 0);
      const type = el.dataset.counterType || 'money';
      const duration = 1200;
      const startTime = performance.now();

      const formatter = (value) => {
        if (type === 'money') {
          return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
        }
        return Math.round(value).toLocaleString('pt-BR');
      };

      const frame = (now) => {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = formatter(target * eased);

        if (progress < 1) {
          requestAnimationFrame(frame);
        } else {
          el.textContent = formatter(target);
          el.dataset.counterAnimated = 'true';
        }
      };

      requestAnimationFrame(frame);
    });
  }

  function renderPaymentDistribution() {
    if (typeof ApexCharts === 'undefined') return;
    const container = document.querySelector('#chartPaymentDistribution');
    if (!container) return;

    const statuses = ['Pago', 'Pendente', 'Parcial', 'Cancelado'];
    const values = statuses.map((status) => {
      const bucket = data.paymentBreakdown?.[status];
      return bucket ? (Number(bucket.total_valor) || Number(bucket.total_pago) || 0) : 0;
    });

    const chart = new ApexCharts(container, {
      chart: { type: 'donut', height: 280 },
      series: values,
      labels: statuses,
      colors: ['#D2EB17', '#F97316', '#60A5FA', '#9CA3AF'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: false },
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              total: {
                show: true,
                label: 'Total',
                formatter: () => {
                  const total = values.reduce((acc, val) => acc + val, 0);
                  return total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                },
              },
            },
          },
        },
      },
    });
    chart.render();
  }

  function renderServiceRevenue() {
    if (typeof ApexCharts === 'undefined') return;
    const container = document.querySelector('#chartServiceRevenue');
    if (!container) return;

    const entries = Object.entries(data.revenueByService || {});
    const labels = entries.map(([label]) => label);
    const series = entries.map(([, value]) => Number(value));

    const chart = new ApexCharts(container, {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      series: [{ name: 'Valor', data: series }],
      xaxis: { categories: labels },
      plotOptions: { bar: { horizontal: false, columnWidth: '50%' } },
      dataLabels: { enabled: false },
      colors: ['#0A0316'],
      tooltip: { y: { formatter: (value) => value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) } },
    });
    chart.render();
  }

  function renderMonthlyEvolution() {
    if (typeof ApexCharts === 'undefined') return;
    const container = document.querySelector('#chartMonthlyEvolution');
    if (!container) return;

    const labels = data.evolution?.labels || [];
    const series = data.evolution?.series || [];

    const chart = new ApexCharts(container, {
      chart: { type: 'line', height: 280, toolbar: { show: false } },
      series: [{ name: 'Projetos', data: series }],
      xaxis: { categories: labels },
      stroke: { curve: 'smooth', width: 3, colors: ['#D4EB29'] },
      markers: { size: 4, colors: ['#0A0316'] },
      dataLabels: { enabled: false },
      yaxis: { min: 0, tickAmount: 4 },
    });
    chart.render();
  }

  document.addEventListener('DOMContentLoaded', () => {
    animateCounters();
    renderPaymentDistribution();
    renderServiceRevenue();
    renderMonthlyEvolution();
  });
})();
