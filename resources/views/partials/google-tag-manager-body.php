<?php
$gtmContainerId = \App\Services\GoogleTagManagerService::containerId();
?>
<?php if ($gtmContainerId !== null): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e(rawurlencode($gtmContainerId)) ?>"
    height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
<?php endif; ?>
