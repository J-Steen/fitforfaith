    </main><!-- /admin-main -->
  </div><!-- /admin-layout -->

</div><!-- /page-wrapper -->

<button class="admin-sidebar-toggle" id="adminToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>

<script src="<?= asset('js/app.js') ?>"></script>
<script>
const adminToggle = document.getElementById('adminToggle');
const adminSidebar = document.getElementById('adminSidebar');
if (adminToggle && adminSidebar) {
  adminToggle.addEventListener('click', () => adminSidebar.classList.toggle('open'));
}
</script>
</body>
</html>
