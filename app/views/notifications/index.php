<div class="toolbar">
    <h1>Notifikasi</h1>
    <?php if (unread_notifications()): ?>
    <form method="post" action="<?= e(url('/notifikasi/baca')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline" type="submit">Tandai semua dibaca</button>
    </form>
    <?php endif; ?>
</div>
<?php if (!$items): ?>
    <p class="muted">Belum ada notifikasi.</p>
<?php else: ?>
<ul class="notif-list">
    <?php foreach ($items as $n): ?>
        <li class="<?= $n['dibaca'] ? '' : 'unread' ?>">
            <?= e($n['pesan']) ?>
            <small class="muted"><?= e(date('d M Y H:i', strtotime($n['created_at']))) ?></small>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
