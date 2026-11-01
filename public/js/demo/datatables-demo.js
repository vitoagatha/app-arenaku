// Call the dataTables jQuery plugin
$(document).ready(function() {
  $('#dataTable').DataTable({
    // Scroll only the table: keep search, page length and pagination reachable.
    dom: '<"akd-table-controls"lf><"akd-table-scroll"t><"akd-table-footer"ip>',
    initComplete: function() {
      const wrapper = $(this.api().table().container());
      const caption = wrapper.find('caption').text().trim() || 'Data pengelolaan';
      wrapper.closest('.table-responsive').removeClass('table-responsive').removeAttr('role aria-label tabindex');
      wrapper.find('.akd-table-scroll').attr({role: 'region', 'aria-label': caption, tabindex: '0'});
    },
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
