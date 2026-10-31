// Call the dataTables jQuery plugin
$(document).ready(function() {
  $('#dataTable').DataTable({
    language: {
      search: 'Cari data:', searchPlaceholder: 'Ketik kata pencarian…',
      lengthMenu: 'Tampilkan _MENU_ baris', info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
      infoEmpty: 'Belum ada data', infoFiltered: '(disaring dari _MAX_ data)',
      emptyTable: 'Belum ada data. Gunakan tindakan tambah jika tersedia.',
      zeroRecords: 'Tidak ada hasil. Coba kata pencarian lain.',
      paginate: {first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya'},
      aria: {sortAscending: ': urutkan naik', sortDescending: ': urutkan turun'}
    }
  });
});
