/**
 * DataTables server-side do SaaS (mesmo layout/dom de app-ecommerce-order-list.js).
 *
 * Prefixo data-saas-* para não colidir com as opções HTML5 do próprio DataTables
 * (data-order, data-columns...).
 *
 * <table class="table border-top saas-datatable"
 *        data-saas-url="..."                       endpoint JSON (App\Support\DataTable)
 *        data-saas-columns='["order","date",...]'  chaves de cada coluna (HTML pronto do servidor)
 *        data-saas-orderable='[0,1]'               índices ordenáveis (opcional)
 *        data-saas-order='[[1,"desc"]]'            ordenação inicial (opcional)
 *        data-saas-search="Buscar pedido"          placeholder da busca
 *        data-saas-filter="#filtros">              formulário cujos campos viram parâmetros extras
 */

'use strict';

$(function () {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  $('.saas-datatable').each(function () {
    const $table = $(this);
    const columns = $table.data('saasColumns') || [];
    const orderable = $table.data('saasOrderable') || [];
    const $filters = $($table.data('saasFilter') || []);

    const dt = $table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: $table.data('saasUrl'),
        data: function (d) {
          $filters.serializeArray().forEach(field => (d[field.name] = field.value));
        }
      },
      columns: columns.map(key => ({ data: key })),
      columnDefs: [
        {
          targets: '_all',
          orderable: false,
          render: data => (data === null || data === undefined ? '' : data)
        },
        ...(orderable.length ? [{ targets: orderable, orderable: true }] : [])
      ],
      order: $table.data('saasOrder') || (orderable.length ? [[orderable[0], 'desc']] : []),
      dom:
        '<"card-header py-0 d-flex flex-column flex-md-row align-items-center"<f><"d-flex align-items-center justify-content-md-end gap-2 justify-content-center"l<"dt-action-buttons"B>>' +
        '>t' +
        '<"row mx-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: $table.data('saasSearch') || 'Buscar',
        info: 'Exibindo _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Nenhum registro',
        infoFiltered: '(filtrado de _MAX_)',
        zeroRecords: 'Nenhum registro encontrado',
        emptyTable: 'Nenhum registro encontrado',
        processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>'
        }
      },
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle waves-effect waves-light',
          text: '<i class="ti ti-upload ti-xs me-2"></i>Exportar',
          buttons: [
            { extend: 'print', text: '<i class="ti ti-printer me-2"></i>Imprimir', className: 'dropdown-item', exportOptions: { stripHtml: true } },
            { extend: 'csv', text: '<i class="ti ti-file me-2"></i>CSV', className: 'dropdown-item', exportOptions: { stripHtml: true } },
            { extend: 'excel', text: '<i class="ti ti-file-export me-2"></i>Excel', className: 'dropdown-item', exportOptions: { stripHtml: true } }
          ]
        }
      ],
      responsive: true
    });

    $filters.on('change', 'select, input', () => dt.ajax.reload());

    // Remove o tamanho padrão do form-control na busca/length (igual ao template)
    setTimeout(() => {
      $('.dataTables_filter .form-control').removeClass('form-control-sm');
      $('.dataTables_length .form-select').removeClass('form-select-sm');
    }, 300);
  });
});

// Tabelas dentro de abas: recalcula colunas ao exibir a aba
document.addEventListener('shown.bs.tab', () => {
  $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
});
