<?php
/** @var string $status */
if ($status === 'dibatalkan') { echo '<p>' . status_badge($status) . '</p>'; return; }
$idx = array_search($status, Order::FLOW, true);
?>
<ol class="timeline">
    <?php foreach (Order::FLOW as $i => $s): ?>
        <li class="<?= $i < $idx ? 'done' : ($i === $idx ? 'current' : '') ?>"><?= e(Order::label($s)) ?></li>
    <?php endforeach; ?>
</ol>
