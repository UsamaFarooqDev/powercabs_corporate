
  window.initRidesDataTable = function () {
    if (window.ridesDataTable) {
      window.ridesDataTable.destroy();
      window.ridesDataTable = null;
    }

    window.ridesDataTable = $('.datatable').DataTable({
      "pageLength": 10,
      "lengthChange": false,
      "ordering": true,
      "order": [],          // preserve PHP-supplied row order (id.desc → latest first)
      "info": true,
      "language": {
        "search": "_INPUT_",
        "searchPlaceholder": "Search ..."
      }
    });

    const customSearch = document.getElementById('ridesSearch');
    if (customSearch) {
      $('.dataTables_filter').hide();
      customSearch.removeEventListener('input', window.__ridesSearchHandler || (() => {}));
      window.__ridesSearchHandler = function () {
        window.ridesDataTable.search(this.value).draw();
      };
      customSearch.addEventListener('input', function () {
        window.__ridesSearchHandler.call(this);
      });
    }

    // Optional: Remove manual pagination (if still in the DOM)
    document.getElementById('pagination-info')?.remove();
    document.getElementById('prev-page')?.remove();
    document.getElementById('next-page')?.remove();
    return window.ridesDataTable;
  };

  $(document).ready(function () {
    window.initRidesDataTable();
  });

