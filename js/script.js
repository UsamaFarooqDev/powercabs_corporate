
  $(document).ready(function () {
    $('.datatable').DataTable({
      "pageLength": 10,
      "lengthChange": false,
      "ordering": true,
      "info": true,
      "language": {
        "search": "_INPUT_",
        "searchPlaceholder": "Search ..."
      }
    });

    // Optional: Remove manual pagination (if still in the DOM)
    document.getElementById('pagination-info')?.remove();
    document.getElementById('prev-page')?.remove();
    document.getElementById('next-page')?.remove();
  });

