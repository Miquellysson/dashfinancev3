/* Lógica de visualização do dashboard (ApexCharts + animações leves) */
(function () {
  const data = window.dashboardData || {};
  const defaultPalette = ['#D2EB17', '#D4EB29', '#A4B61D', '#141414', '#222222', '#0A0316'];
  const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

  const ensureArray = (value) => (Array.isArray(value) ? value : []);
  const months = ensureArray(data.months);
  const values = ensureArray(data.values).map((v) => Number(v) || 0);
  const statusLabels = ensureArray(data.statusLabels);
  const statusCounts = ensureArray(data.statusCounts).map((v) => Number(v) || 0);
  const statusColors =
    ensureArray(data.statusColors).filter(Boolean).length > 0
      ? data.statusColors
      : defaultPalette;
  const goals = ensureArray(data.goals);

  const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

  function animateCounters() {
    const items = document.querySelectorAll('[data-counter]');
    if (!items.length) return;

    const formatValue = (value, type) => {
      if (type === 'money') {
        return currency.format(value);
      }
      return Math.round(value).toLocaleString('pt-BR');
    };

    const runAnimation = (el) => {
      if (el.dataset.counterAnimated === 'true') return;
      el.dataset.counterAnimated = 'true';

      const target = Number(el.dataset.counter || 0);
      const type = el.dataset.counterType || 'int';
      const duration = 1200;
      const start = type === 'money' ? 0 : 0;

      if (target === 0) {
        el.textContent = formatValue(0, type);
        return;
      }

      let startTime = null;
      const step = (timestamp) => {
        if (!startTime) startTime = timestamp;
        const progress = Math.min((timestamp - startTime) / duration, 1);
        const eased = easeOutCubic(progress);
        const current = start + (target - start) * eased;
        el.textContent = formatValue(current, type);
        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          el.textContent = formatValue(target, type);
        }
      };
      requestAnimationFrame(step);
    };

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              runAnimation(entry.target);
              observer.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.4 }
      );
      items.forEach((el) => observer.observe(el));
    } else {
      items.forEach((el) => runAnimation(el));
    }
  }

  function renderRevenueChart() {
    const el = document.querySelector('#revenueChart');
    if (!el || typeof ApexCharts === 'undefined') return;

    const options = {
      chart: {
        type: 'area',
        height: 320,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
      },
      series: [
        {
          name: 'Receita',
          data: values,
        },
      ],
      colors: ['#D2EB17'],
      stroke: {
        curve: 'smooth',
        width: 3,
      },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo: 0.05,
          stops: [0, 90, 100],
        },
      },
      dataLabels: { enabled: false },
      markers: {
        size: 4,
        strokeWidth: 2,
        hover: { sizeOffset: 2 },
      },
      grid: {
        borderColor: '#D1D5DB',
        strokeDashArray: 6,
      },
      xaxis: {
        categories: months,
        labels: {
          style: { colors: '#5E6F13' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: {
        labels: {
          formatter: (value) => currency.format(value).replace('R$', 'R$ '),
          style: { colors: '#5E6F13' },
        },
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: (value) => currency.format(value),
        },
      },
    };

    const chart = new ApexCharts(el, options);
    chart.render();
  }

  function renderStatusChart() {
    const el = document.querySelector('#statusChart');
    if (!el || typeof ApexCharts === 'undefined') return;

    const options = {
      chart: {
        type: 'donut',
        height: 320,
        fontFamily: 'Inter, sans-serif',
      },
      labels: statusLabels,
      series: statusCounts.length ? statusCounts : [0],
      colors: statusColors,
      stroke: { width: 0 },
      dataLabels: { enabled: false },
      legend: {
        position: 'bottom',
        fontSize: '13px',
        markers: {
          width: 10,
          height: 10,
          radius: 12,
        },
      },
      plotOptions: {
        pie: {
          donut: {
            size: '72%',
            labels: {
              show: true,
              name: { show: true, fontSize: '14px', offsetY: 8 },
              value: {
                show: true,
                fontSize: '22px',
                formatter: (value) => Math.round(Number(value) || 0),
              },
              total: {
                show: true,
                label: 'Projetos',
                formatter: () =>
                  statusCounts.reduce((sum, val) => sum + (Number(val) || 0), 0),
              },
            },
          },
        },
      },
      tooltip: {
        y: {
          formatter: (value) => `${value} projeto(s)`,
        },
      },
    };

    const chart = new ApexCharts(el, options);
    chart.render();
  }

  function goalColor(progress) {
    if (progress >= 100) return '#D2EB17';
    if (progress >= 60) return '#D4EB29';
    return '#141414';
  }

  function renderGoalCharts() {
    if (typeof ApexCharts === 'undefined' || !goals.length) return;

    goals.forEach((goal) => {
      const el = document.querySelector(`#${goal.id}`);
      if (!el) return;

      const target = Number(goal.target) || 0;
      const current = Number(goal.current) || 0;
      const safeTarget = target > 0 ? target : current > 0 ? current : 1;
      const percent = safeTarget > 0 ? Math.min(100, (current / safeTarget) * 100) : 0;
      const rounded = Math.round(percent);

      const chart = new ApexCharts(el, {
        chart: {
          type: 'radialBar',
          height: 200,
          sparkline: { enabled: true },
        },
        series: [rounded],
        colors: [goalColor(rounded)],
        plotOptions: {
          radialBar: {
            startAngle: -140,
            endAngle: 140,
            hollow: {
              size: '62%',
            },
            track: {
              background: '#D1D5DB',
            },
            dataLabels: {
              name: { show: false },
              value: {
                fontSize: '26px',
                formatter: (value) => `${Math.round(value)}%`,
              },
            },
          },
        },
        stroke: { lineCap: 'round' },
        labels: [goal.label],
      });

      chart.render();
    });
  }

  function initDashboard() {
    animateCounters();
    renderRevenueChart();
    renderStatusChart();
    renderGoalCharts();
  }

  if (typeof ApexCharts === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.45.2';
    script.onload = initDashboard;
    document.head.appendChild(script);
  } else {
    initDashboard();
  }
})();
