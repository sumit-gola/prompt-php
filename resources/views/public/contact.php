<section class="text-page">
    <p class="eyebrow">Contact</p>
    <h1>Contact MyPromptArt</h1>
    <p>Use this contact channel for editorial corrections, prompt-source questions, licensing concerns, privacy requests, or account support.</p>
    <?php if ($contactEmail !== ''): ?>
        <p>Email <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>. Include the prompt page URL when your message concerns a specific library entry.</p>
    <?php else: ?>
        <p>Contact details are being configured. Until they are published here, use the support channel associated with the site owner.</p>
    <?php endif; ?>
</section>
