<h1>Sign in</h1>
<form class="stack-form" method="post" action="<?= url('/login') ?>">
    <?= csrf_field() ?>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <button class="button" type="submit">Sign in</button>
</form>
<p class="muted">Need an account? <a href="<?= url('/register') ?>">Create one</a>.</p>

