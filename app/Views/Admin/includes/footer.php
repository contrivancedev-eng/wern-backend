<?php $assets = base_url('public/admin/assets'); ?>
    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /app -->
<script src="<?= $assets ?>/js/app.js"></script>
<script>if (window.lucide) lucide.createIcons();</script>
<?php if (!empty($pageScripts)) foreach ($pageScripts as $s) echo $s; ?>
</body>
</html>
