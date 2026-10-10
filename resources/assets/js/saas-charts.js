/**
 * Gráficos ApexCharts com as cores do tema (padrão de dashboards-*.js).
 * <div data-apex-area='{"labels":[...],"series":[...],"name":"Vendas"}'></div>
 */

'use strict';

(function () {
  if (typeof ApexCharts === 'undefined') return;

  const colors = window.isDarkStyle ? config.colors_dark : config.colors;
  const money = v => 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

  document.querySelectorAll('[data-apex-area]').forEach(el => {
    const data = JSON.parse(el.dataset.apexArea);
    new ApexCharts(el, {
      chart: { height: 300, type: 'area', parentHeightOffset: 0, toolbar: { show: false }, fontFamily: 'Public Sans' },
      series: [{ name: data.name || 'Vendas', data: data.series }],
      xaxis: {
        categories: data.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: colors.textMuted, fontSize: '12px' } },
        tickAmount: 10
      },
      yaxis: { labels: { formatter: v => money(v), style: { colors: colors.textMuted } } },
      dataLabels: { enabled: false },
      stroke: { width: 3, curve: 'smooth' },
      colors: [config.colors.primary],
      fill: { type: 'gradient', gradient: { shadeIntensity: 0.6, opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 95, 100] } },
      grid: { borderColor: colors.borderColor, strokeDashArray: 6, padding: { top: -10, bottom: -5 } },
      tooltip: { y: { formatter: v => money(v) } }
    }).render();
  });
})();
