<?php
$footerLocations = $db->query('SELECT unit_name, address_line, neighborhood, city, state FROM locations WHERE active=1 ORDER BY sort_order, unit_name LIMIT 2')->fetchAll();
$footerSocials = db_table_exists($db, 'social_links') ? $db->query("SELECT * FROM social_links WHERE active=1 ORDER BY sort_order,id LIMIT 6")->fetchAll() : [];
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <strong><?= e($siteSettings['school_name']) ?></strong>
            <p><?= e($siteSettings['footer_text']) ?></p>
            <div class="footer-socials"><?php foreach($footerSocials as $social): ?><a class="footer-link" href="<?= e($social['url']) ?>" target="_blank" rel="noopener"><?= e($social['title']) ?></a><?php endforeach; ?></div>
        </div>
        <div>
            <strong>Contato</strong>
            <p><?= e($siteSettings['phone']) ?><br><?= e($siteSettings['whatsapp']) ?><br><?= e($siteSettings['email']) ?></p>
        </div>
        <div>
            <strong>Unidades</strong>
            <?php if ($footerLocations): ?>
                <?php foreach ($footerLocations as $location): ?>
                    <p class="footer-location"><b><?= e($location['unit_name']) ?></b><br><?= e($location['address_line']) ?><?php if ($location['city']): ?><br><?= e(trim($location['city'] . ' - ' . $location['state'], ' -')) ?><?php endif; ?></p>
                <?php endforeach; ?>
                <a class="footer-link" href="index.php?page=contato">Ver todos os endereços</a>
            <?php elseif ($siteSettings['address']): ?>
                <p><?= e($siteSettings['address']) ?></p>
            <?php else: ?>
                <p>Endereços em atualização.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="container copyright">© <?= date('Y') ?> <?= e($siteSettings['school_name']) ?></div>
</footer>
<script src="assets/js/app.js"></script>
</body>
</html>
