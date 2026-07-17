<!-- End main-content -->
</main>

</div><!-- .app-wrapper -->

<!-- ── Toast Container ──────────────────────────────────────── -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- ── Scripts ──────────────────────────────────────────────── -->
<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php if (basename($_SERVER['PHP_SELF'], '.php') === 'calendario'): ?>
<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<!-- Cache-busting version keeps browsers from serving a stale calendar.js after deployments -->
<script src="assets/js/calendar.js?v=<?= $getAssetVersion('assets/js/calendar.js') ?>"></script>
<?php endif; ?>
<!-- Main JS -->
<!-- Cache-busting version keeps browsers from serving a stale main.js after deployments -->
<script src="assets/js/main.js?v=<?= $getAssetVersion('assets/js/main.js') ?>"></script>
</body>
</html>
