<?php
$_flashSuccess = flash('success');
$_flashError   = flash('error');
$_flashInfo    = flash('info');
$_flashWarn    = flash('warning');
?>
<?php if ($_flashSuccess || $_flashError || $_flashInfo || $_flashWarn): ?>
<div class="toast-container" id="toastContainer">
    <?php if ($_flashSuccess): ?>
    <div class="toast toast-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= e($_flashSuccess) ?></span>
        <button onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>
    <?php if ($_flashError): ?>
    <div class="toast toast-error">
        <i class="fa-solid fa-circle-xmark"></i>
        <span><?= e($_flashError) ?></span>
        <button onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>
    <?php if ($_flashInfo): ?>
    <div class="toast toast-info">
        <i class="fa-solid fa-circle-info"></i>
        <span><?= e($_flashInfo) ?></span>
        <button onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>
    <?php if ($_flashWarn): ?>
    <div class="toast toast-warning">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?= e($_flashWarn) ?></span>
        <button onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>
</div>
<script>
    setTimeout(() => {
        document.querySelectorAll('.toast').forEach(t => {
            t.style.opacity = '0';
            t.style.transform = 'translateY(-12px)';
            setTimeout(() => t.remove(), 400);
        });
    }, 4000);
</script>
<?php endif; ?>
