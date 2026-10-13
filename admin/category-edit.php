<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

$id = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : null;
$cat = ['name' => '', 'description' => '', 'icon' => ''];
if ($id !== null) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('err', 'Kategorie nicht gefunden.');
        redirect('categories.php');
    }
    $cat = array_merge($cat, $row);
}
if (!empty($_SESSION['old_input'])) {
    $cat = array_merge($cat, array_intersect_key($_SESSION['old_input'], ['name' => 1, 'description' => 1]));
    unset($_SESSION['old_input']);
}
$isNew = $id === null;
$hasIcon = is_managed_upload($cat['icon']);

admin_header($isNew ? 'Neue Kategorie' : 'Kategorie bearbeiten', $admin);
?>
<h1 class="admin-title"><?= $isNew ? 'Neue Kategorie' : 'Kategorie bearbeiten' ?></h1>
<form method="post" action="category-save.php" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="<?= $isNew ? 'create' : 'update' ?>">
  <input type="hidden" name="orig_id" value="<?= h($id ?? '') ?>">
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) config('upload_max_bytes') ?>">

  <fieldset class="task-card"><legend>Kategorie</legend>
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required maxlength="60" value="<?= h($cat['name']) ?>">
    <label for="description">Kurze Beschreibung</label>
    <textarea id="description" name="description" maxlength="300"><?= h($cat['description']) ?></textarea>
  </fieldset>

  <fieldset class="task-card"><legend>Symbol (Icon)</legend>
    <div class="admin-icon">
      <span class="ico" aria-hidden="true"><?= category_ico($cat) ?></span>
      <div>
        <input type="file" id="icon" name="icon" accept="image/jpeg,image/png" aria-label="Symbol hochladen">
        <p class="muted">JPEG/PNG, quadratisch empfohlen, max. <?= (int) (config('upload_max_bytes') / 1048576) ?> MB.
          <?= $isNew ? '' : 'Ein neues Bild ersetzt das bisherige.' ?></p>
        <?php if ($hasIcon): ?>
          <label><input type="checkbox" name="remove_icon" value="1"> Symbol entfernen</label>
        <?php endif; ?>
      </div>
    </div>
  </fieldset>

  <div class="admin-sticky"><button class="next-button">Speichern</button> <a class="btn secondary" href="categories.php">Abbrechen</a></div>
</form>
<?php admin_footer();
