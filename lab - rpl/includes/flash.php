<?php
// includes/flash.php
if (isset($_SESSION['flash'])):
  $f = $_SESSION['flash']; unset($_SESSION['flash']);
  $icons = ['success'=>'✅','danger'=>'❌','warning'=>'⚠️','info'=>'ℹ️'];
?>
<div class="alert alert-<?= $f['type'] ?>">
  <span><?= $icons[$f['type']]??'ℹ️' ?></span>
  <span><?= $f['message'] ?></span>
</div>
<?php endif; ?>
