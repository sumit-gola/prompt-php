<?php
$success = flash('success');
$error = flash('error');
$errors = flash('errors', []);
?>
<?php if ($success): ?>
    <div class="flash flash-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if (is_array($errors) && $errors !== []): ?>
    <div class="flash flash-error">
        <strong>Check the form</strong>
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

