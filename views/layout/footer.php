  </main><!-- /main -->

  <footer class="footer">
    <div class="container">
      <p>© <?= date('Y') ?> <?= h(APP_NAME) ?> &mdash; <?= h(APP_TAGLINE) ?></p>
      <p class="mt-2">
        <a href="<?= url() ?>"><?= t('footer.home') ?></a> &middot;
        <a href="<?= url('leaderboard') ?>"><?= t('footer.leaderboard') ?></a> &middot;
        <a href="<?= url('register') ?>"><?= t('footer.register') ?></a>
      </p>
    </div>
  </footer>

</div><!-- /page-wrapper -->

<!-- Scripts -->
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
